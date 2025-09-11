<?php

/**
 * The admin settings functionality of the plugin.
 *
 * @link       https://photobooster.ai
 * @since      1.0.0
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin
 */

/**
 * The admin settings functionality of the plugin.
 *
 * Defines the settings page, handles form validation, and manages API configuration.
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin
 * @author     PhotoBooster AI <info@photobooster.ai>
 */
class Photobooster_Ai_Settings
{

    /**
     * The option name for storing settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $option_name    The option name.
     */
    private $option_name = 'photobooster_ai_settings';

    /**
     * The crypto instance for encryption/decryption.
     *
     * @since    1.0.0
     * @access   private
     * @var      Photobooster_Ai_Crypto    $crypto    The crypto instance.
     */
    private $crypto;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->crypto = new Photobooster_Ai_Crypto();

        // Defer capability check until WordPress is fully loaded
        add_action('init', array($this, 'init_admin_hooks'));
    }

    /**
     * Initialize admin hooks after WordPress is loaded.
     *
     * @since    1.0.0
     */
    public function init_admin_hooks()
    {
        // Always register the admin_init hook, but check permissions within the callbacks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_photobooster_connection', array($this, 'test_api_connection'));
    }

    /**
     * Add admin menu page.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_options_page(
            'PhotoBooster AI Settings',
            'PhotoBooster AI',
            'manage_options',
            'photobooster-ai-settings',
            array($this, 'settings_page_callback')
        );
    }

    /**
     * Settings page callback.
     *
     * @since    1.0.0
     */
    public function settings_page_callback()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        include_once 'partials/photobooster-ai-settings-display.php';
    }

    /**
     * Register settings using WordPress Settings API.
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        error_log('PhotoBooster AI Settings: register_settings() called');

        // Register setting (WordPress will handle permissions)
        register_setting(
            'photobooster_ai_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );

        error_log('PhotoBooster AI Settings: register_setting completed for option: ' . $this->option_name);

        // Add settings section
        add_settings_section(
            'api_section',
            'API Configuration',
            array($this, 'api_section_callback'),
            'photobooster_ai_settings'
        );

        // Add API key field
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_field_callback'),
            'photobooster_ai_settings',
            'api_section'
        );

        // Add API endpoint field
        add_settings_field(
            'api_endpoint',
            'API Endpoint URL',
            array($this, 'api_endpoint_field_callback'),
            'photobooster_ai_settings',
            'api_section'
        );

        // Add connection status section
        add_settings_section(
            'status_section',
            'Connection Status',
            array($this, 'status_section_callback'),
            'photobooster_ai_settings'
        );
    }

    /**
     * API section callback.
     *
     * @since    1.0.0
     */
    public function api_section_callback()
    {
        echo '<p>Configure your PhotoBooster AI API settings below. Your API key will be encrypted and stored securely.</p>';
    }

    /**
     * Status section callback.
     *
     * @since    1.0.0
     */
    public function status_section_callback()
    {
        $settings = get_option($this->option_name, array());
        $last_test = isset($settings['last_test']) ? $settings['last_test'] : null;
        $connection_status = isset($settings['connection_status']) ? $settings['connection_status'] : 'unknown';

        echo '<p>Test your API connection to ensure everything is working properly.</p>';

        if ($last_test) {
            $time_diff = human_time_diff($last_test, current_time('timestamp'));
            echo '<p><strong>Last tested:</strong> ' . $time_diff . ' ago</p>';
        }

        if ($connection_status !== 'unknown') {
            $status_class = $connection_status === 'success' ? 'success' : 'error';
            $status_text = $connection_status === 'success' ? '✓ Connected' : '✗ Connection failed';
            echo '<p class="status-indicator ' . $status_class . '">' . $status_text . '</p>';
        }
    }

    /**
     * API key field callback.
     *
     * @since    1.0.0
     */
    public function api_key_field_callback()
    {
        $settings = get_option($this->option_name, array());
        $has_key = !empty($settings['api_key']);

        echo '<input type="password" id="api_key" name="' . $this->option_name . '[api_key]" ' .
            'class="regular-text" placeholder="Enter your API key" ' .
            'value="' . $settings['api_key'] . '" />';

        if ($has_key) {
            echo '<p class="description">API key is currently set. Enter a new key to replace it.</p>';
        } else {
            echo '<p class="description">Enter your PhotoBooster AI API key.</p>';
        }
    }

    /**
     * API endpoint field callback.
     *
     * @since    1.0.0
     */
    public function api_endpoint_field_callback()
    {
        $settings = get_option($this->option_name, array());
        $endpoint = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;

        echo '<input type="url" id="api_endpoint" name="' . $this->option_name . '[api_endpoint]" ' .
            'class="regular-text" value="' . esc_attr($endpoint) . '" />';
        echo '<p class="description">The API endpoint URL for PhotoBooster AI service.</p>';
    }

    /**
     * Sanitize and validate settings input.
     *
     * @since    1.0.0
     * @param    array    $input    The input values.
     * @return   array              The sanitized values.
     */
    public function sanitize_settings($input)
    {
        error_log('PhotoBooster AI Settings: sanitize_settings called with input: ' . print_r($input, true));

        $sanitized = array();
        $existing_settings = get_option($this->option_name, array());

        // Test the specific API key format you provided
        $test_key = 'pb_ee847defdf3a97fb67f526d8e6cee052b5b4eaffad4b50201a46e3d7a7bcde8f';
        error_log('PhotoBooster AI Settings: Testing validation for sample key: ' . $this->crypto->is_key_valid($test_key));

        // Sanitize API key
        if (!empty($input['api_key']) && $input['api_key'] !== '••••••••••••••••') {
            $api_key = sanitize_text_field($input['api_key']);

            // Debug logging (remove in production)
            error_log('PhotoBooster AI Settings: Processing API key, length: ' . strlen($api_key));
            error_log('PhotoBooster AI Settings: API key (first 10 chars): ' . substr($api_key, 0, 10) . '...');

            if ($this->crypto->is_key_valid($api_key)) {
                error_log('PhotoBooster AI Settings: API key validation passed');
                $encrypted_key = $this->crypto->encrypt_api_key($api_key);

                if ($encrypted_key !== false) {
                    $sanitized['api_key'] = $encrypted_key;
                    add_settings_error(
                        'photobooster_ai_settings',
                        'api_key_updated',
                        'API key updated successfully.',
                        'success'
                    );
                    error_log('PhotoBooster AI Settings: API key saved successfully');
                } else {
                    add_settings_error(
                        'photobooster_ai_settings',
                        'save_failed',
                        'Failed to save API key. Please try again.'
                    );
                    error_log('PhotoBooster AI Settings: Failed to save API key');
                    // Keep existing key if save fails
                    $sanitized['api_key'] = isset($existing_settings['api_key']) ? $existing_settings['api_key'] : '';
                }
            } else {
                add_settings_error(
                    'photobooster_ai_settings',
                    'invalid_api_key',
                    'Invalid API key format. Key must be 16-500 characters, contain only printable characters, and not include spaces or quotes.'
                );
                error_log('PhotoBooster AI Settings: API key validation failed, length: ' . strlen($api_key));
                error_log('PhotoBooster AI Settings: Failed key (first 10 chars): ' . substr($api_key, 0, 10) . '...');
                // Keep existing key if validation fails
                $sanitized['api_key'] = isset($existing_settings['api_key']) ? $existing_settings['api_key'] : '';
            }
        } else {
            // Keep existing key if not changed
            $sanitized['api_key'] = isset($existing_settings['api_key']) ? $existing_settings['api_key'] : '';
            if (empty($input['api_key'])) {
                error_log('PhotoBooster AI Settings: No API key provided in input');
            } else {
                error_log('PhotoBooster AI Settings: API key unchanged (masked value detected)');
            }
        }

        // Sanitize API endpoint
        if (!empty($input['api_endpoint'])) {
            $endpoint = esc_url_raw($input['api_endpoint']);

            if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
                $sanitized['api_endpoint'] = $endpoint;
            } else {
                add_settings_error(
                    'photobooster_ai_settings',
                    'invalid_endpoint',
                    'Invalid API endpoint URL. Please enter a valid URL.'
                );
                // Use default endpoint if invalid
                $sanitized['api_endpoint'] = PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;
            }
        } else {
            $sanitized['api_endpoint'] = PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;
        }

        // Preserve other settings
        $sanitized['connection_status'] = isset($existing_settings['connection_status']) ? $existing_settings['connection_status'] : 'unknown';
        $sanitized['last_test'] = isset($existing_settings['last_test']) ? $existing_settings['last_test'] : null;
        $sanitized['last_error'] = isset($existing_settings['last_error']) ? $existing_settings['last_error'] : null;

        return $sanitized;
    }

    /**
     * Test API connection via AJAX.
     *
     * @since    1.0.0
     */
    public function test_api_connection()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'photobooster_ai_test_connection')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $settings = get_option($this->option_name, array());

        // Check if API key is configured
        if (empty($settings['api_key'])) {
            wp_send_json_error(array(
                'message' => 'No API key configured. Please save your settings first.'
            ));
        }

        // Get API configuration
        $api_key = $this->crypto->decrypt_api_key($settings['api_key']);
        $endpoint = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;

        if (!$api_key) {
            wp_send_json_error(array(
                'message' => 'Failed to decrypt API key. Please re-enter your API key.'
            ));
        }

        // Test endpoint (modify to match your API's test endpoint)
        $test_endpoint = rtrim($endpoint, '/') . '/test';

        // Make test request
        $response = wp_remote_get($test_endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent' => 'PhotoBooster-AI-Plugin/' . PHOTOBOOSTER_AI_VERSION,
            ),
        ));

        // Update settings with test results
        $settings['last_test'] = current_time('timestamp');

        if (is_wp_error($response)) {
            $settings['connection_status'] = 'failed';
            $settings['last_error'] = $response->get_error_message();
            update_option($this->option_name, $settings);

            wp_send_json_error(array(
                'message' => 'Connection failed: ' . $response->get_error_message()
            ));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code === 200) {
            $settings['connection_status'] = 'success';
            $settings['last_error'] = null;
            update_option($this->option_name, $settings);

            wp_send_json_success(array(
                'message' => 'Connection successful!'
            ));
        } else {
            $settings['connection_status'] = 'failed';
            $settings['last_error'] = 'HTTP ' . $response_code;
            update_option($this->option_name, $settings);

            wp_send_json_error(array(
                'message' => 'Connection failed with HTTP ' . $response_code
            ));
        }
    }

    /**
     * Get encrypted API key from settings.
     *
     * @since    1.0.0
     * @return   string|false    The encrypted API key or false if not set.
     */
    public function get_encrypted_api_key()
    {
        $settings = get_option($this->option_name, array());
        return isset($settings['api_key']) ? $settings['api_key'] : false;
    }

    /**
     * Get decrypted API key for API calls.
     *
     * @since    1.0.0
     * @return   string|false    The decrypted API key or false if not set/invalid.
     */
    public function get_decrypted_api_key()
    {
        $encrypted_key = $this->get_encrypted_api_key();

        if (!$encrypted_key) {
            return false;
        }

        return $this->crypto->decrypt_api_key($encrypted_key);
    }

    /**
     * Get API endpoint URL.
     *
     * @since    1.0.0
     * @return   string    The API endpoint URL.
     */
    public function get_api_endpoint()
    {
        $settings = get_option($this->option_name, array());
        return isset($settings['api_endpoint']) ? $settings['api_endpoint'] : PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;
    }
}
