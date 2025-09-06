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
class Photobooster_Ai_Admin {

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
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/photobooster-ai-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/photobooster-ai-admin.js', array( 'jquery' ), $this->version, false );

		// Register lightweight bootstrap for the media modal enhance feature.
		$handle = $this->plugin_name . '-media-enhance-bootstrap';
		wp_register_script(
			$handle,
			plugin_dir_url( __FILE__ ) . 'js/media-enhance-bootstrap.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Only expose data and enqueue for users who can upload files.
		if ( current_user_can( 'upload_files' ) ) {
			$localized = array(
				'restBase'   => untrailingslashit( rest_url( 'photobooster-ai/v1' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'canEnhance' => true,
				'distUrl'    => $this->get_admin_dist_url(),
				'manifest'   => $this->get_vite_manifest_map(),
			);
			wp_localize_script( $handle, 'PBAIEnhance', $localized );
			wp_enqueue_script( $handle );
		}

	}

	/**
	 * Resolve the URL to the admin dist directory produced by Vite.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_admin_dist_url() {
		return plugin_dir_url( __FILE__ ) . 'dist/';
	}

	/**
	 * Resolve the absolute path to the admin dist directory.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_admin_dist_path() {
		return plugin_dir_path( __FILE__ ) . 'dist/';
	}

	/**
	 * Load and decode the Vite manifest.json from the admin dist directory.
	 * Returns an associative array keyed by entries with their asset metadata.
	 * If the manifest does not exist or is invalid, a fallback manifest is returned.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_vite_manifest_map() {
		$manifest_path = $this->get_admin_dist_path() . 'manifest.json';
		if ( ! file_exists( $manifest_path ) ) {
			// Fallback manifest based on current built assets
			return $this->get_fallback_manifest();
		}
		$raw = file_get_contents( $manifest_path );
		if ( false === $raw ) {
			return $this->get_fallback_manifest();
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
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
	private function get_fallback_manifest() {
		$dist_path = $this->get_admin_dist_path();
		$assets_path = $dist_path . 'assets/';
		
		$manifest = array();
		
		if ( is_dir( $assets_path ) ) {
			$files = scandir( $assets_path );
			foreach ( $files as $file ) {
				if ( $file === '.' || $file === '..' ) {
					continue;
				}
				
				// Map common file patterns to entries
				if ( strpos( $file, 'mount-' ) === 0 && substr( $file, -3 ) === '.js' ) {
					$manifest['src/mount.tsx'] = array(
						'file' => 'assets/' . $file,
						'isEntry' => true,
					);
					
					// Look for corresponding CSS file with same hash pattern
					$css_file = str_replace( '.js', '.css', $file );
					if ( file_exists( $assets_path . $css_file ) ) {
						$manifest['src/mount.tsx']['css'] = array( 'assets/' . $css_file );
					} else {
						// Try to find any mount CSS file
						$css_files = glob( $assets_path . 'mount-*.css' );
						if ( ! empty( $css_files ) ) {
							$css_filename = basename( $css_files[0] );
							$manifest['src/mount.tsx']['css'] = array( 'assets/' . $css_filename );
						}
					}
				} elseif ( strpos( $file, 'app-' ) === 0 && substr( $file, -3 ) === '.js' ) {
					$manifest['src/main.tsx'] = array(
						'file' => 'assets/' . $file,
						'isEntry' => true,
					);
				}
			}
		}
		
		return $manifest;
	}

}
