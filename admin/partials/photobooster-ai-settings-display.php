<?php

/**
 * Provide a admin area view for the plugin settings
 *
 * This file is used to markup the admin-facing aspects of the plugin settings.
 *
 * @link       https://photobooster.ai
 * @since      1.0.0
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin/partials
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('photobooster_ai_settings'); ?>

    <div class="photobooster-ai-settings-container">
        <div class="postbox-container" id="postbox-container-1">
            <div class="meta-box-sortables ui-sortable">

                <!-- Main Settings Form -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">API Configuration</h2>
                    </div>
                    <div class="inside">
                        <form action="options.php" method="post">
                            <?php
                            echo '<!-- DEBUG: settings_fields output -->';
                            settings_fields('photobooster_ai_settings_group');
                            echo '<!-- DEBUG: do_settings_sections output -->';
                            do_settings_sections('photobooster_ai_settings');
                            echo '<!-- DEBUG: settings sections complete -->';
                            ?>

                            <div class="photobooster-connection-test">
                                <button type="button" id="test-connection" class="button button-secondary">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    Test Connection
                                </button>
                                <span id="connection-status" class="status"></span>
                                <div id="connection-spinner" class="spinner" style="display: none;"></div>
                            </div>

                            <?php submit_button('Save Settings', 'primary', 'submit', true, array('id' => 'save-settings')); ?>
                        </form>
                    </div>
                </div>

                <!-- Information Box -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Security Information</h2>
                    </div>
                    <div class="inside">
                        <div class="photobooster-info-box">
                            <p><strong>🔒 Security Features:</strong></p>
                            <ul>
                                <li>✓ Only administrators can access these settings</li>
                                <li>✓ All requests use secure HTTPS connections</li>
                                <li>✓ API keys are validated before storage</li>
                                <li>✓ Form submissions are protected with nonces</li>
                                <li>✓ Input sanitization prevents malicious data</li>
                            </ul>

                            <p><strong>📋 Requirements:</strong></p>
                            <ul>
                                <li>Valid PhotoBooster AI API key</li>
                                <li>WordPress administrator privileges</li>
                                <li>HTTPS enabled (recommended)</li>
                                <li>PHP 7.4 or higher</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics (Future) -->
                <div class="postbox" style="opacity: 0.6;">
                    <div class="postbox-header">
                        <h2 class="hndle">Usage Statistics <span class="coming-soon">(Coming Soon)</span></h2>
                    </div>
                    <div class="inside">
                        <div class="photobooster-stats-placeholder">
                            <p>📊 Track your API usage, success rates, and performance metrics.</p>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number">-</span>
                                    <span class="stat-label">Images Enhanced</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">-</span>
                                    <span class="stat-label">Success Rate</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">-</span>
                                    <span class="stat-label">Avg Response Time</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">-</span>
                                    <span class="stat-label">Credits Used</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .photobooster-ai-settings-container {
        max-width: 800px;
    }

    .photobooster-connection-test {
        margin: 20px 0;
        padding: 15px;
        background: #f8f9fa;
        border: 1px solid #e1e5e9;
        border-radius: 4px;
    }

    .photobooster-connection-test button {
        margin-right: 10px;
    }

    .photobooster-connection-test .status {
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 3px;
        margin-left: 10px;
    }

    .photobooster-connection-test .status.success {
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
    }

    .photobooster-connection-test .status.error {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
    }

    .photobooster-connection-test .status.testing {
        color: #004085;
        background-color: #cce7ff;
        border: 1px solid #99d6ff;
    }

    .photobooster-info-box ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .photobooster-info-box li {
        margin: 5px 0;
    }

    .coming-soon {
        font-size: 0.8em;
        color: #666;
        font-weight: normal;
    }

    .photobooster-stats-placeholder {
        text-align: center;
        color: #666;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 4px;
    }

    .stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .stat-label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .status-indicator {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 14px;
        font-weight: 600;
    }

    .status-indicator.success {
        color: #155724;
        background-color: #d4edda;
    }

    .status-indicator.error {
        color: #721c24;
        background-color: #f8d7da;
    }

    #connection-spinner {
        float: none;
        margin: 0 0 0 10px;
    }

    .form-table th {
        width: 150px;
    }

    .form-table td input[type="password"],
    .form-table td input[type="url"] {
        width: 100%;
        max-width: 400px;
    }

    /* Responsive design */
    @media screen and (max-width: 782px) {
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        }

        .photobooster-connection-test {
            text-align: center;
        }

        .photobooster-connection-test button {
            margin: 5px;
        }
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle connection test
        $('#test-connection').on('click', function() {
            var button = $(this);
            var status = $('#connection-status');
            var spinner = $('#connection-spinner');

            // Show loading state
            button.prop('disabled', true);
            spinner.show();
            status.removeClass('success error').addClass('testing').text('Testing connection...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_photobooster_connection',
                    nonce: '<?php echo wp_create_nonce('photobooster_ai_test_connection'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.removeClass('testing error').addClass('success').text('✓ ' + response.data.message);
                    } else {
                        status.removeClass('testing success').addClass('error').text('✗ ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#connection-status').removeClass('testing success').addClass('error').text('✗ Connection test failed');
                },
                complete: function() {
                    button.prop('disabled', false);
                    spinner.hide();
                }
            });
        });
    });
</script>