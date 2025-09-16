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
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/admin
 * @author     PhotoBooster AI <PhotoBoosterai@gmail.com>
 */
class Photobooster_Ai_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Photobooster_Ai_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Photobooster_Ai_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/photobooster-ai-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Photobooster_Ai_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Photobooster_Ai_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */


		// Base admin script from the original plugin scaffold.
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/photobooster-ai-admin.js', array('jquery'), $this->version, false);

		// Register lightweight bootstrap for the media modal enhance feature.
		$handle = $this->plugin_name . '-media-enhance-bootstrap';
		wp_register_script(
			$handle,
			plugin_dir_url(__FILE__) . 'js/media-enhance-bootstrap.js',
			array('jquery'),
			$this->version,
			true
		);

		// Only expose data and enqueue for users who can upload files.
		if (current_user_can('upload_files')) {
			$localized = array(
				'restBase'   => untrailingslashit(rest_url('photobooster-ai/v1')),
				'nonce'      => wp_create_nonce('wp_rest'),
				'canEnhance' => true,
				'distUrl'    => $this->get_admin_dist_url(),
				'manifest'   => $this->get_vite_manifest_map(),
			);
			wp_localize_script($handle, 'PBAIEnhance', $localized);
			wp_enqueue_script($handle);
		}

		// Enqueue scripts for post edit pages (including WooCommerce products)
		$this->enqueue_post_edit_scripts();
	}

	/**
	 * Enqueue scripts and styles specifically for post edit pages.
	 * This ensures the AI Enhance functionality is available on product edit pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_post_edit_scripts()
	{
		global $pagenow, $post_type;

		// Check if we're on a post edit page
		if (in_array($pagenow, array('post.php', 'post-new.php'))) {
			// Enqueue for all post types that support thumbnails, including WooCommerce products
			if (post_type_supports($post_type, 'thumbnail') || $post_type === 'product') {
				// Ensure our bootstrap script is loaded
				$handle = $this->plugin_name . '-media-enhance-bootstrap';
				if (!wp_script_is($handle, 'enqueued') && current_user_can('upload_files')) {
					$localized = array(
						'restBase'   => untrailingslashit(rest_url('photobooster-ai/v1')),
						'nonce'      => wp_create_nonce('wp_rest'),
						'canEnhance' => true,
						'distUrl'    => $this->get_admin_dist_url(),
						'manifest'   => $this->get_vite_manifest_map(),
					);
					wp_localize_script($handle, 'PBAIEnhance', $localized);
					wp_enqueue_script($handle);
				}
			}
		}
	}

	/**
	 * Resolve the URL to the admin dist directory produced by Vite.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_admin_dist_url()
	{
		return plugin_dir_url(__FILE__) . 'dist/';
	}

	/**
	 * Resolve the absolute path to the admin dist directory.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_admin_dist_path()
	{
		return plugin_dir_path(__FILE__) . 'dist/';
	}

	/**
	 * Load and decode the Vite manifest.json from the admin dist directory.
	 * Returns an associative array keyed by entries with their asset metadata.
	 * If the manifest does not exist or is invalid, a fallback manifest is returned.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_vite_manifest_map()
	{
		$manifest_path = $this->get_admin_dist_path() . '.vite/manifest.json';
		if (! file_exists($manifest_path)) {
			// Fallback manifest based on current built assets
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
	 * @since 1.0.0
	 * @return array
	 */
	private function get_fallback_manifest()
	{
		$dist_path = $this->get_admin_dist_path();
		$assets_path = $dist_path . 'assets/';

		$manifest = array();

		if (is_dir($assets_path)) {
			$files = scandir($assets_path);
			foreach ($files as $file) {
				if ($file === '.' || $file === '..') {
					continue;
				}

				// Map common file patterns to entries
				if (strpos($file, 'mount-') === 0 && substr($file, -3) === '.js') {
					$manifest['src/mount.tsx'] = array(
						'file' => 'assets/' . $file,
						'isEntry' => true,
					);

					// Look for corresponding CSS file with same hash pattern
					$css_file = str_replace('.js', '.css', $file);
					if (file_exists($assets_path . $css_file)) {
						$manifest['src/mount.tsx']['css'] = array('assets/' . $css_file);
					} else {
						// Try to find any mount CSS file
						$css_files = glob($assets_path . 'mount-*.css');
						if (! empty($css_files)) {
							$css_filename = basename($css_files[0]);
							$manifest['src/mount.tsx']['css'] = array('assets/' . $css_filename);
						}
					}
				} elseif (strpos($file, 'app-') === 0 && substr($file, -3) === '.js') {
					$manifest['src/main.tsx'] = array(
						'file' => 'assets/' . $file,
						'isEntry' => true,
					);
				}
			}
		}

		return $manifest;
	}

	/**
	 * Inject AI Enhance button into the WooCommerce product image box.
	 * This adds the button directly into the postimagediv for quick access.
	 *
	 * @since 1.0.0
	 */
	public function inject_product_image_enhance_button()
	{
		global $post_type, $post;

		// Only show on product edit pages and if user can upload files
		if ($post_type !== 'product' || !current_user_can('upload_files')) {
			return;
		}

		// Only show if there's a featured image set
		if (!has_post_thumbnail($post->ID)) {
			return;
		}

		$attachment_id = get_post_thumbnail_id($post->ID);
		$attachment = get_post($attachment_id);

		if (!$attachment) {
			return;
		}

		// Check if it's an eligible image type
		$eligible_mimes = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp');
		if (!in_array($attachment->post_mime_type, $eligible_mimes)) {
			return;
		}

		// Get image URL for the attachment
		$image_url = wp_get_attachment_url($attachment_id);

?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Inject the AI Enhance button into the product image area
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

						// Create attachment object compatible with our enhance modal
						var attachment = {
							id: <?php echo intval($attachment_id); ?>,
							title: <?php echo json_encode($attachment->post_title); ?>,
							filename: <?php echo json_encode(basename(get_attached_file($attachment_id))); ?>,
							url: <?php echo json_encode($image_url); ?>,
							mime: <?php echo json_encode($attachment->post_mime_type); ?>,
							alt: <?php echo json_encode(get_post_meta($attachment_id, '_wp_attachment_image_alt', true)); ?>,
							sizes: {
								full: {
									url: <?php echo json_encode($image_url); ?>
								},
								large: {
									url: <?php echo json_encode(wp_get_attachment_image_url($attachment_id, 'large')); ?>
								},
								medium: {
									url: <?php echo json_encode(wp_get_attachment_image_url($attachment_id, 'medium')); ?>
								},
								thumbnail: {
									url: <?php echo json_encode(wp_get_attachment_image_url($attachment_id, 'thumbnail')); ?>
								}
							},
							get: function(key) {
								return this[key];
							}
						};

						console.log('Product image enhance clicked for attachment:', attachment);

						// Create a temporary modal container for our enhance app
						var modalContainer = $('<div>', {
							id: 'pbai-product-enhance-modal',
							style: 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; display: flex; align-items: center; justify-content: center;'
						});

						// Add close functionality
						modalContainer.on('click', function(e) {
							if (e.target === this) {
								$(this).remove();
							}
						});

						$('body').append(modalContainer);

						// Ensure CSS is loaded regardless of which mount function we use
						var entry = window.PBAIEnhance && window.PBAIEnhance.manifest && window.PBAIEnhance.manifest['src/mount.tsx'];
						var distUrl = window.PBAIEnhance && window.PBAIEnhance.distUrl || '';

						console.log('Injecting CSS for product page modal...');
						if (entry && entry.css) {
							entry.css.forEach(function(css) {
								var href = distUrl + css;
								if (!document.querySelector('link[data-pbai-css="' + href + '"]')) {
									var link = document.createElement('link');
									link.rel = 'stylesheet';
									link.href = href;
									link.setAttribute('data-pbai-css', href);
									document.head.appendChild(link);
									console.log('Injected CSS for product page:', href);
								}
							});
						}

						// Also check for CSS in other manifest entries
						var manifest = window.PBAIEnhance && window.PBAIEnhance.manifest || {};
						Object.keys(manifest).forEach(function(key) {
							var manifestEntry = manifest[key];
							if (manifestEntry.css && manifestEntry.css.length > 0) {
								console.log('Found CSS in manifest entry:', key, manifestEntry.css);
								manifestEntry.css.forEach(function(css) {
									var href = distUrl + css;
									if (!document.querySelector('link[data-pbai-css="' + href + '"]')) {
										var link = document.createElement('link');
										link.rel = 'stylesheet';
										link.href = href;
										link.setAttribute('data-pbai-css', href);
										document.head.appendChild(link);
										console.log('Manually injected CSS for product page:', href);
									}
								});
							}
						});

						// Use our existing mountAppIntoModal function if available
						if (typeof window.mountAppIntoModal === 'function') {
							window.mountAppIntoModal(modalContainer[0], attachment);
						} else {
							// Fallback: try to load and mount the app
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

										// Listen for close event
										mountNode.addEventListener('pbai:close', function() {
											modalContainer.remove();
										});

										mountAppFn(mountNode, {
											attachment: attachment
										});
									} else {
										console.error('mountApp function not found');
										modalContainer.remove();
										alert('Failed to load AI Enhance app.');
									}
								}).catch(function(error) {
									console.error('Failed to load AI Enhance app:', error);
									modalContainer.remove();
									alert('Failed to load AI Enhance app.');
								});
							} else {
								console.error('No manifest entry found for AI Enhance app');
								modalContainer.remove();
								alert('AI Enhance app not properly configured.');
							}
						}
					});

					$('#postimagediv .inside').append($('<p>').append(enhanceBtn));
					console.log('AI Enhance button injected into product image area');
				}
			});
		</script>
<?php
	}
}
