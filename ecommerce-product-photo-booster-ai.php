<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://photoboosterai.com
 * @since             1.0.0
 * @package           Photobooster_Ai
 *
 * @wordpress-plugin
 * Plugin Name:       eCommerce Product Photo Booster AI
 * Plugin URI:        https://photoboosterai.com
 * Description:       Generate studio-quality images from a single photo—no expensive gear, no photo shoots. Upload your product, and within seconds, get polished photos with clean backgrounds, lifestyle mockups, and marketing-ready variations.
 * Version:           1.0.0
 * Author:            PhotoBooster AI
 * Author URI:        https://photoboosterai.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecommerce-product-photo-booster-ai
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PHOTOBOOSTER_AI_VERSION', '1.0.0');

/**
 * API Configuration constants.
 */
define('PHOTOBOOSTER_AI_BASE_URL', 'https://photoboosterai.com');
define('PHOTOBOOSTER_AI_API_BASE_PATH', 'api');
define('PHOTOBOOSTER_AI_API_TIMEOUT', 60);
define('PHOTOBOOSTER_AI_SETTINGS_CAPABILITY', 'manage_options');

/**
 * Security settings.
 */
define('PHOTOBOOSTER_AI_ENCRYPTION_METHOD', 'sodium');
define('PHOTOBOOSTER_AI_KEY_MIN_LENGTH', 32);

/**
 * Helper functions for API endpoints.
 */

/**
 * Get the full API endpoint URL for a specific endpoint.
 *
 * @since 1.0.0
 * @param string $endpoint The API endpoint path (e.g., 'generate-image', 'credits').
 * @return string The full API endpoint URL.
 */
function photobooster_ai_get_api_endpoint($endpoint = '')
{
	$base_url = PHOTOBOOSTER_AI_BASE_URL;
	$api_path = PHOTOBOOSTER_AI_API_BASE_PATH;

	// Construct base API URL
	$base_api_url = rtrim($base_url, '/') . '/' . ltrim($api_path, '/');

	// Add specific endpoint if provided
	if (!empty($endpoint)) {
		$base_api_url .= '/' . ltrim($endpoint, '/');
	}

	return $base_api_url;
}

/**
 * Get the generate image API endpoint URL.
 *
 * @since 1.0.0
 * @return string The generate image API endpoint URL.
 */
function photobooster_ai_get_generate_image_endpoint()
{
	return photobooster_ai_get_api_endpoint('generate-image');
}

/**
 * Get the credits API endpoint URL.
 *
 * @since 1.0.0
 * @return string The credits API endpoint URL.
 */
function photobooster_ai_get_credits_endpoint()
{
	return photobooster_ai_get_api_endpoint('credits');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-photobooster-ai-activator.php
 */
function activate_photobooster_ai()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-photobooster-ai-activator.php';
	Photobooster_Ai_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-photobooster-ai-deactivator.php
 */
function deactivate_photobooster_ai()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-photobooster-ai-deactivator.php';
	Photobooster_Ai_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_photobooster_ai');
register_deactivation_hook(__FILE__, 'deactivate_photobooster_ai');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-photobooster-ai.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_photobooster_ai()
{

	$plugin = new Photobooster_Ai();
	$plugin->run();
}
run_photobooster_ai();
