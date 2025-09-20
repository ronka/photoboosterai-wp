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
                            settings_fields('photobooster_ai_settings_group');
                            do_settings_sections('photobooster_ai_settings');
                            ?>

                            <div id="credits-status" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">
                                <h4>Current Credits</h4>
                                <p id="credits-info">Loading...</p>
                            </div>

                            <div style="margin-top: 10px;">
                                <button type="button" id="check-credits" class="button" style="margin-right: 10px;">Check Credits</button>
                                <?php submit_button('Save Settings', 'primary', 'submit', true, array('id' => 'save-settings')); ?>
                            </div>
                        </form>
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

    }
</style>