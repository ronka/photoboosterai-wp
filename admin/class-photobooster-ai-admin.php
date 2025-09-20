<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://PhotoBoosterai.vercel.app
 * @since      1.0.0
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Handles admin enqueuing, asset management, and product image enhancement functionality.
 * Provides integration with WordPress media library and WooCommerce product images.
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin
 * @author     PhotoBooster AI <PhotoBoosterai@gmail.com>
 * @since      1.0.0
 */
class Photobooster_Ai_Admin
{
	// =============================================================================
	// CONSTANTS
	// =============================================================================

	/**
	 * Script handles and asset paths.
	 */
	const SCRIPT_HANDLE_BOOTSTRAP = '-media-enhance-bootstrap';
	const CSS_PATH = 'css/photobooster-ai-admin.css';
	const JS_PATH = 'js/photobooster-ai-admin.js';
	const JS_BOOTSTRAP_PATH = 'js/media-enhance-bootstrap.js';
	const DIST_PATH = 'dist/';
	const ASSETS_PATH = 'assets/';
	const MANIFEST_PATH = '.vite/manifest.json';

	/**
	 * WordPress capabilities and post types.
	 */
	const REQUIRED_CAPABILITY = 'upload_files';
	const PRODUCT_POST_TYPE = 'product';

	/**
	 * Supported image MIME types for enhancement.
	 */
	const SUPPORTED_IMAGE_TYPES = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/webp'
	);

	/**
	 * REST API configuration.
	 */
	const REST_NAMESPACE = 'photobooster-ai/v1';
	const REST_NONCE_ACTION = 'wp_rest';
	const LOCALIZE_OBJECT_NAME = 'PBAIEnhance';

	// =============================================================================
	// PROPERTIES
	// =============================================================================

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $version The current version of this plugin.
	 */
	private $version;

	// =============================================================================
	// CONSTRUCTOR & INITIALIZATION
	// =============================================================================

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	// =============================================================================
	// ASSET ENQUEUEING
	// =============================================================================

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * Enqueues the main admin stylesheet for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . self::CSS_PATH,
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * Enqueues admin scripts and registers the media enhancement bootstrap script.
	 * Only loads enhancement features for users with upload capabilities.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts()
	{
		// Base admin script
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . self::JS_PATH,
			array('jquery'),
			$this->version,
			false
		);

		// Register and conditionally enqueue the media enhancement bootstrap
		$this->register_media_enhancement_script();

		// Enqueue scripts for post edit pages
		$this->enqueue_post_edit_scripts();
	}

	/**
	 * Register the media enhancement bootstrap script.
	 *
	 * Registers and conditionally enqueues the bootstrap script for media enhancement
	 * functionality. Only loads for users with upload capabilities.
	 *
	 * @since 1.0.0
	 */
	private function register_media_enhancement_script()
	{
		$handle = $this->get_bootstrap_script_handle();

		wp_register_script(
			$handle,
			plugin_dir_url(__FILE__) . self::JS_BOOTSTRAP_PATH,
			array('jquery'),
			$this->version,
			true
		);

		// Only expose data and enqueue for users who can upload files
		if (current_user_can(self::REQUIRED_CAPABILITY)) {
			$this->localize_and_enqueue_script($handle);
		}
	}

	/**
	 * Enqueue scripts and styles specifically for post edit pages.
	 *
	 * Ensures the AI Enhance functionality is available on product edit pages
	 * and other post types that support thumbnails.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_post_edit_scripts()
	{
		global $pagenow, $post_type;

		// Check if we're on a post edit page
		if (! $this->is_post_edit_page($pagenow)) {
			return;
		}

		// Enqueue for supported post types
		if ($this->should_enqueue_for_post_type($post_type)) {
			$handle = $this->get_bootstrap_script_handle();

			if (! wp_script_is($handle, 'enqueued') && current_user_can(self::REQUIRED_CAPABILITY)) {
				$this->localize_and_enqueue_script($handle);
			}
		}
	}

	// =============================================================================
	// UTILITY METHODS
	// =============================================================================

	/**
	 * Get the bootstrap script handle.
	 *
	 * @since 1.0.0
	 * @return string The script handle for the bootstrap script.
	 */
	private function get_bootstrap_script_handle()
	{
		return $this->plugin_name . self::SCRIPT_HANDLE_BOOTSTRAP;
	}

	/**
	 * Check if we're on a post edit page.
	 *
	 * @since 1.0.0
	 * @param string $pagenow Current page name.
	 * @return bool True if on post edit page.
	 */
	private function is_post_edit_page($pagenow)
	{
		return in_array($pagenow, array('post.php', 'post-new.php'), true);
	}

	/**
	 * Check if we should enqueue scripts for the given post type.
	 *
	 * @since 1.0.0
	 * @param string $post_type The post type to check.
	 * @return bool True if should enqueue for this post type.
	 */
	private function should_enqueue_for_post_type($post_type)
	{
		return post_type_supports($post_type, 'thumbnail') || $post_type === self::PRODUCT_POST_TYPE;
	}

	/**
	 * Localize and enqueue a script with enhancement data.
	 *
	 * @since 1.0.0
	 * @param string $handle The script handle to localize and enqueue.
	 */
	private function localize_and_enqueue_script($handle)
	{
		$localized_data = $this->get_localized_script_data();
		wp_localize_script($handle, self::LOCALIZE_OBJECT_NAME, $localized_data);
		wp_enqueue_script($handle);
	}

	/**
	 * Get the data to be localized for JavaScript.
	 *
	 * @since 1.0.0
	 * @return array The localized data array.
	 */
	private function get_localized_script_data()
	{
		return array(
			'restBase'   => untrailingslashit(rest_url(self::REST_NAMESPACE)),
			'nonce'      => wp_create_nonce(self::REST_NONCE_ACTION),
			'canEnhance' => true,
			'distUrl'    => $this->get_admin_dist_url(),
			'manifest'   => $this->get_vite_manifest_map(),
			'pluginUrl'  => plugin_dir_url(__FILE__),
			'assetsUrl'  => plugin_dir_url(__FILE__) . 'react-app/public/',
		);
	}

	/**
	 * Resolve the URL to the admin dist directory produced by Vite.
	 *
	 * @since 1.0.0
	 * @return string The URL to the dist directory.
	 */
	private function get_admin_dist_url()
	{
		return plugin_dir_url(__FILE__) . self::DIST_PATH;
	}

	/**
	 * Resolve the absolute path to the admin dist directory.
	 *
	 * @since 1.0.0
	 * @return string The absolute path to the dist directory.
	 */
	private function get_admin_dist_path()
	{
		return plugin_dir_path(__FILE__) . self::DIST_PATH;
	}

	// =============================================================================
	// VITE MANIFEST HANDLING
	// =============================================================================

	/**
	 * Load and decode the Vite manifest.json from the admin dist directory.
	 *
	 * Returns an associative array keyed by entries with their asset metadata.
	 * If the manifest does not exist or is invalid, a fallback manifest is returned.
	 *
	 * @since 1.0.0
	 * @return array The manifest data array with asset information.
	 */
	private function get_vite_manifest_map()
	{
		$manifest_path = $this->get_admin_dist_path() . self::MANIFEST_PATH;

		if (! file_exists($manifest_path)) {
			return $this->get_fallback_manifest();
		}

		$raw = file_get_contents($manifest_path);
		if (false === $raw) {
			return $this->get_fallback_manifest();
		}

		$decoded = json_decode($raw, true);
		if (! is_array($decoded)) {
			return $this->get_fallback_manifest();
		}

		return $decoded;
	}

	/**
	 * Generate a fallback manifest by scanning the dist directory for built assets.
	 *
	 * This method creates a manifest structure by detecting common Vite build patterns
	 * when the actual manifest.json file is not available.
	 *
	 * @since 1.0.0
	 * @return array The fallback manifest array.
	 */
	private function get_fallback_manifest()
	{
		$dist_path   = $this->get_admin_dist_path();
		$assets_path = $dist_path . self::ASSETS_PATH;
		$manifest    = array();

		if (! is_dir($assets_path)) {
			return $manifest;
		}

		$files = scandir($assets_path);
		if (false === $files) {
			return $manifest;
		}

		foreach ($files as $file) {
			if ('.' === $file || '..' === $file) {
				continue;
			}

			// Process mount files
			if ($this->is_mount_js_file($file)) {
				$manifest['src/mount.tsx'] = array(
					'file'    => self::ASSETS_PATH . $file,
					'isEntry' => true,
				);

				$css_file = $this->find_corresponding_css_file($assets_path, $file);
				if ($css_file) {
					$manifest['src/mount.tsx']['css'] = array(self::ASSETS_PATH . $css_file);
				}
			} elseif ($this->is_app_js_file($file)) {
				// Process app files
				$manifest['src/main.tsx'] = array(
					'file'    => self::ASSETS_PATH . $file,
					'isEntry' => true,
				);
			}
		}

		return $manifest;
	}

	/**
	 * Check if a file is a mount JavaScript file.
	 *
	 * @since 1.0.0
	 * @param string $file The filename to check.
	 * @return bool True if it's a mount JS file.
	 */
	private function is_mount_js_file($file)
	{
		return 0 === strpos($file, 'mount-') && '.js' === substr($file, -3);
	}

	/**
	 * Check if a file is an app JavaScript file.
	 *
	 * @since 1.0.0
	 * @param string $file The filename to check.
	 * @return bool True if it's an app JS file.
	 */
	private function is_app_js_file($file)
	{
		return 0 === strpos($file, 'app-') && '.js' === substr($file, -3);
	}

	/**
	 * Find the corresponding CSS file for a JavaScript file.
	 *
	 * @since 1.0.0
	 * @param string $assets_path The path to the assets directory.
	 * @param string $js_file     The JavaScript filename.
	 * @return string|false The CSS filename if found, false otherwise.
	 */
	private function find_corresponding_css_file($assets_path, $js_file)
	{
		$css_file = str_replace('.js', '.css', $js_file);

		if (file_exists($assets_path . $css_file)) {
			return $css_file;
		}

		// Try to find any mount CSS file as fallback
		$css_files = glob($assets_path . 'mount-*.css');
		if (! empty($css_files)) {
			return basename($css_files[0]);
		}

		return false;
	}

	// =============================================================================
	// PRODUCT IMAGE ENHANCEMENT
	// =============================================================================

	/**
	 * Inject AI Enhance button into the WooCommerce product image box.
	 *
	 * This adds the button directly into the postimagediv for quick access
	 * when editing WooCommerce products with featured images.
	 *
	 * @since 1.0.0
	 */
	public function inject_product_image_enhance_button()
	{
		global $post_type, $post;

		// Validate conditions for showing the button
		if (! $this->should_show_enhance_button($post_type, $post)) {
			return;
		}

		$attachment_data = $this->get_product_attachment_data($post->ID);
		if (! $attachment_data) {
			return;
		}

		$this->render_product_enhancement_script($attachment_data);
	}

	/**
	 * Check if the enhance button should be shown.
	 *
	 * @since 1.0.0
	 * @param string   $post_type The current post type.
	 * @param \WP_Post $post      The current post object.
	 * @return bool True if button should be shown.
	 */
	private function should_show_enhance_button($post_type, $post)
	{
		// Only show on product edit pages and if user can upload files
		if (self::PRODUCT_POST_TYPE !== $post_type || ! current_user_can(self::REQUIRED_CAPABILITY)) {
			return false;
		}

		// Only show if there's a featured image set
		if (! has_post_thumbnail($post->ID)) {
			return false;
		}

		return true;
	}

	/**
	 * Get attachment data for the product's featured image.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 * @return array|false The attachment data array or false if invalid.
	 */
	private function get_product_attachment_data($post_id)
	{
		$attachment_id = get_post_thumbnail_id($post_id);
		$attachment    = get_post($attachment_id);

		if (! $attachment) {
			return false;
		}

		// Check if it's an eligible image type
		if (! in_array($attachment->post_mime_type, self::SUPPORTED_IMAGE_TYPES, true)) {
			return false;
		}

		$image_url = wp_get_attachment_url($attachment_id);
		if (! $image_url) {
			return false;
		}

		return array(
			'id'           => $attachment_id,
			'attachment'   => $attachment,
			'image_url'    => $image_url,
			'alt_text'     => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
			'filename'     => basename(get_attached_file($attachment_id)),
		);
	}

	/**
	 * Render the product enhancement script.
	 *
	 * @since 1.0.0
	 * @param array $attachment_data The attachment data array.
	 */
	private function render_product_enhancement_script($attachment_data)
	{
		$attachment_id = $attachment_data['id'];
		$attachment    = $attachment_data['attachment'];
		$image_url     = $attachment_data['image_url'];

?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				if ($('#postimagediv .inside').length && !$('#pbai-product-enhance-btn').length) {
					var enhanceBtn = $('<button>', {
						id: 'pbai-product-enhance-btn',
						type: 'button',
						class: 'button button-primary',
						text: '✨ AI Enhance Product Image',
						style: 'margin-top: 10px; width: 100%;'
					});

					enhanceBtn.on('click', function(e) {
						e.preventDefault();

						var attachment = {
							id: <?php echo intval($attachment_id); ?>,
							title: <?php echo wp_json_encode($attachment->post_title); ?>,
							filename: <?php echo wp_json_encode($attachment_data['filename']); ?>,
							url: <?php echo wp_json_encode($image_url); ?>,
							mime: <?php echo wp_json_encode($attachment->post_mime_type); ?>,
							alt: <?php echo wp_json_encode($attachment_data['alt_text']); ?>,
							sizes: {
								full: {
									url: <?php echo wp_json_encode($image_url); ?>
								},
								large: {
									url: <?php echo wp_json_encode(wp_get_attachment_image_url($attachment_id, 'large')); ?>
								},
								medium: {
									url: <?php echo wp_json_encode(wp_get_attachment_image_url($attachment_id, 'medium')); ?>
								},
								thumbnail: {
									url: <?php echo wp_json_encode(wp_get_attachment_image_url($attachment_id, 'thumbnail')); ?>
								}
							},
							get: function(key) {
								return this[key];
							}
						};

						var modalContainer = $('<div>', {
							id: 'pbai-product-enhance-modal',
							style: 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; display: flex; align-items: center; justify-content: center;'
						});

						modalContainer.on('click', function(e) {
							if (e.target === this) {
								$(this).remove();
							}
						});

						$('body').append(modalContainer);

						var entry = window.PBAIEnhance && window.PBAIEnhance.manifest && window.PBAIEnhance.manifest['src/mount.tsx'];
						var distUrl = window.PBAIEnhance && window.PBAIEnhance.distUrl || '';

						if (entry && entry.css) {
							entry.css.forEach(function(css) {
								var href = distUrl + css;
								if (!document.querySelector('link[data-pbai-css="' + href + '"]')) {
									var link = document.createElement('link');
									link.rel = 'stylesheet';
									link.href = href;
									link.setAttribute('data-pbai-css', href);
									document.head.appendChild(link);
								}
							});
						}

						var manifest = window.PBAIEnhance && window.PBAIEnhance.manifest || {};
						Object.keys(manifest).forEach(function(key) {
							var manifestEntry = manifest[key];
							if (manifestEntry.css && manifestEntry.css.length > 0) {
								manifestEntry.css.forEach(function(css) {
									var href = distUrl + css;
									if (!document.querySelector('link[data-pbai-css="' + href + '"]')) {
										var link = document.createElement('link');
										link.rel = 'stylesheet';
										link.href = href;
										link.setAttribute('data-pbai-css', href);
										document.head.appendChild(link);
									}
								});
							}
						});

						if (typeof window.mountAppIntoModal === 'function') {
							window.mountAppIntoModal(modalContainer[0], attachment);
						} else {
							if (entry && entry.file) {
								var jsUrl = distUrl + entry.file;

								import(jsUrl).then(function(mod) {
									var mountAppFn = mod.mountApp || window.PBAIMountApp;
									if (typeof mountAppFn === 'function') {
										var mountNode = $('<div>', {
											id: 'pbai-enhance-root',
											style: 'background: white; border-radius: 8px; max-width: 90vw; max-height: 90vh; overflow: auto;'
										})[0];
										modalContainer.append(mountNode);

										mountNode.addEventListener('pbai:close', function() {
											modalContainer.remove();
										});

										mountAppFn(mountNode, {
											attachment: attachment
										});
									} else {
										modalContainer.remove();
										alert('Failed to load AI Enhance app.');
									}
								}).catch(function() {
									modalContainer.remove();
									alert('Failed to load AI Enhance app.');
								});
							} else {
								modalContainer.remove();
								alert('AI Enhance app not properly configured.');
							}
						}
					});

					$('#postimagediv .inside').append($('<p>').append(enhanceBtn));
				}
			});
		</script>
<?php
	}
}
