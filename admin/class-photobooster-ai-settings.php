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

    // Class constants for error handling and status
    private const ERROR_INVALID_API_KEY = 'invalid_api_key';
    private const ERROR_SAVE_FAILED = 'save_failed';
    private const SUCCESS_API_KEY_UPDATED = 'api_key_updated';


    // API endpoint URL - now uses helper function

    /**
     * The crypto instance for encryption/decryption.
     *
     * @since    1.0.0
     * @access   private
     * @var      Photobooster_Ai_Crypto    $crypto    The crypto instance.
     */
    private $crypto;

    /**
     * Get plugin settings from database.
     *
     * @since    1.0.0
     * @access   private
     * @return   array    The plugin settings.
     */
    private function get_settings()
    {
        return get_option($this->option_name, array());
    }


    /**
     * Check if current user has required permissions.
     *
     * @since    1.0.0
     * @access   private
     * @return   bool    True if user has permissions, false otherwise.
     */
    private function user_has_permissions()
    {
        return current_user_can('manage_options');
    }

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
    }

    /**
     * Add admin menu page.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {
        if (!$this->user_has_permissions()) {
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
        if (!$this->user_has_permissions()) {
            wp_die(esc_html(__('You do not have sufficient permissions to access this page.', 'ecommerce-product-photo-booster-ai')));
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
        // Register setting (WordPress will handle permissions)
        register_setting(
            'photobooster_ai_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );

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
     * API key field callback.
     *
     * @since    1.0.0
     */
    public function api_key_field_callback()
    {
        $settings = $this->get_settings();
        $has_key = !empty($settings['api_key']);

        echo '<input type="password" id="api_key" name="' . esc_attr($this->option_name) . '[api_key]" ' .
            'class="regular-text" placeholder="Enter your API key" ' .
            'value="' . esc_attr($settings['api_key'] ?? '') . '" />';

        if ($has_key) {
            echo '<p class="description">API key is currently set. Enter a new key to replace it.</p>';
        } else {
            echo '<p class="description">Enter your PhotoBooster AI API key.</p>';
        }
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
        $sanitized = array();
        $existing_settings = $this->get_settings();

        // Sanitize API key
        if (!empty($input['api_key']) && $input['api_key'] !== '••••••••••••••••') {
            $api_key = sanitize_text_field($input['api_key']);

            if ($this->crypto->is_key_valid($api_key)) {
                $encrypted_key = $this->crypto->encrypt_api_key($api_key);

                if ($encrypted_key !== false) {
                    $sanitized['api_key'] = $encrypted_key;
                    add_settings_error(
                        'photobooster_ai_settings',
                        self::SUCCESS_API_KEY_UPDATED,
                        'API key updated successfully.',
                        'success'
                    );
                } else {
                    add_settings_error(
                        'photobooster_ai_settings',
                        self::ERROR_SAVE_FAILED,
                        'Failed to save API key. Please try again.'
                    );
                    // Keep existing key if save fails
                    $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
                }
            } else {
                add_settings_error(
                    'photobooster_ai_settings',
                    self::ERROR_INVALID_API_KEY,
                    'Invalid API key format. Key must be 16-500 characters, contain only printable characters, and not include spaces or quotes.'
                );
                // Keep existing key if validation fails
                $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
            }
        } else {
            // Keep existing key if not changed
            $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
        }



        return $sanitized;
    }


    /**
     * Get encrypted API key from settings.
     *
     * @since    1.0.0
     * @return   string|false    The encrypted API key or false if not set.
     */
    public function get_encrypted_api_key()
    {
        $settings = $this->get_settings();
        return $settings['api_key'] ?? false;
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
     * Get API endpoint URL for image generation.
     *
     * @since    1.0.0
     * @return   string    The API endpoint URL for image generation.
     */
    public function get_api_endpoint()
    {
        return photobooster_ai_get_generate_image_endpoint();
    }

    /**
     * Get credits API endpoint URL.
     *
     * @since    1.0.1
     * @return   string    The credits API endpoint URL.
     */
    public function get_credits_endpoint()
    {
        return photobooster_ai_get_credits_endpoint();
    }
}
