<?php

/**
 * REST API scaffold for Photobooster AI.
 *
 * @package Photobooster_Ai
 */

class Photobooster_Ai_REST
{
	/**
	 * Plugin namespace for routes.
	 *
	 * @var string
	 */
	private $namespace = 'photobooster-ai/v1';

	/**
	 * Register all plugin REST routes.
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'/noop',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array($this, 'route_noop'),
					'permission_callback' => array($this, 'permissions_uploaders_with_nonce'),
				),
			),
			array('args' => array())
		);

		register_rest_route(
			$this->namespace,
			'/generate-image',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array($this, 'route_generate_image'),
					'permission_callback' => array($this, 'permissions_uploaders_with_nonce'),
					'args'                => array(
						'attachment_id' => array(
							'required'          => true,
							'validate_callback' => function ($param) {
								return is_numeric($param) && intval($param) > 0;
							},
							'sanitize_callback' => 'absint',
						),
						'style' => array(
							'required'          => true,
							'validate_callback' => function ($param) {
								$valid_styles = array('Professional', 'Creative', 'Artistic', 'Modern', 'Vintage', 'Minimalist');
								return in_array($param, $valid_styles, true);
							},
							'sanitize_callback' => 'sanitize_text_field',
						),
						'additional_instructions' => array(
							'required'          => false,
							'validate_callback' => function ($param) {
								return is_string($param);
							},
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Simple connectivity route returning ok: true.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function route_noop($request)
	{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		return new WP_REST_Response(array('ok' => true), 200);
	}

	/**
	 * Generate AI enhanced image from seed image.
	 *
	 * @param WP_REST_Request $request Request containing attachment_id, style, and optional additional_instructions.
	 * @return WP_REST_Response
	 */
	public function route_generate_image($request)
	{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		// Get sanitized parameters (already validated by route args)
		$attachment_id = $request->get_param('attachment_id');
		$style = $request->get_param('style');
		$additional_instructions = $request->get_param('additional_instructions') ?: '';

		// Validate attachment for processing
		$validation_result = $this->validate_attachment_for_processing($attachment_id);
		if (! $validation_result['valid']) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => $validation_result['error'],
				),
				400
			);
		}

		// Get attachment file path
		$file_path = $this->get_attachment_file_path($attachment_id);
		if (! $file_path) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Could not retrieve attachment file.',
				),
				500
			);
		}

		// Log the generation attempt
		error_log(sprintf('PhotoBooster AI: Starting image generation for attachment %d with style: %s', $attachment_id, $style));

		try {
			// Call NextJS API integration (to be implemented in next tasks)
			$generated_image_data = $this->call_nextjs_api($file_path, $style, $additional_instructions);

			if (! $generated_image_data) {
				throw new Exception('Failed to generate image via NextJS API');
			}

			// Save generated image to media library (to be implemented in next tasks)
			$new_attachment = $this->save_generated_image_to_media_library($generated_image_data, $attachment_id);

			if (! $new_attachment) {
				throw new Exception('Failed to save generated image to media library');
			}

			return new WP_REST_Response(
				array(
					'success'       => true,
					'attachment_id' => $new_attachment['id'],
					'url'           => $new_attachment['url'],
					'message'       => 'Image generated successfully',
				),
				200
			);
		} catch (Exception $e) {
			error_log(sprintf('PhotoBooster AI: Image generation failed for attachment %d: %s', $attachment_id, $e->getMessage()));

			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Image generation failed: ' . $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Permission callback that requires upload_files and a valid REST nonce.
	 *
	 * @return bool
	 */
	public function permissions_uploaders_with_nonce()
	{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		if (! current_user_can('upload_files')) {
			return false;
		}
		// Check nonce from X-WP-Nonce header.
		$nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';
		if (! $nonce) {
			return false;
		}
		return (bool) wp_verify_nonce($nonce, 'wp_rest');
	}

	/**
	 * Validate that the current user can modify the given attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool True if user can modify this attachment.
	 */
	public function validate_attachment_ownership($attachment_id)
	{
		// Ensure it's a valid attachment
		$attachment = get_post($attachment_id);
		if (! $attachment || 'attachment' !== $attachment->post_type) {
			return false;
		}

		// Check if user can edit this attachment
		if (! current_user_can('edit_post', $attachment_id)) {
			return false;
		}

		return true;
	}

	/**
	 * Validate that an attachment is an eligible image type for processing.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool True if attachment is eligible for AI enhancement.
	 */
	public function validate_attachment_file_type($attachment_id)
	{
		$mime_type = get_post_mime_type($attachment_id);

		$eligible_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/webp',
		);

		return in_array($mime_type, $eligible_types, true);
	}

	/**
	 * Get attachment file path and validate it exists and is readable.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string|false File path if valid, false otherwise.
	 */
	public function get_attachment_file_path($attachment_id)
	{
		$file_path = get_attached_file($attachment_id);

		if (! $file_path || ! file_exists($file_path) || ! is_readable($file_path)) {
			return false;
		}

		return $file_path;
	}

	/**
	 * Comprehensive validation for attachment processing.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return array Array with 'valid' boolean and 'error' message if invalid.
	 */
	public function validate_attachment_for_processing($attachment_id)
	{
		$attachment_id = absint($attachment_id);

		if (! $attachment_id) {
			return array(
				'valid' => false,
				'error' => 'Invalid attachment ID.',
			);
		}

		if (! $this->validate_attachment_ownership($attachment_id)) {
			return array(
				'valid' => false,
				'error' => 'You do not have permission to modify this attachment.',
			);
		}

		if (! $this->validate_attachment_file_type($attachment_id)) {
			return array(
				'valid' => false,
				'error' => 'Attachment must be a JPEG, PNG, or WebP image.',
			);
		}

		if (! $this->get_attachment_file_path($attachment_id)) {
			return array(
				'valid' => false,
				'error' => 'Attachment file not found or not readable.',
			);
		}

		return array(
			'valid' => true,
			'error' => '',
		);
	}

	/**
	 * Call NextJS API to generate enhanced image.
	 *
	 * @param string $file_path   Path to the seed image file.
	 * @param string $style       Generation style.
	 * @param string $additional_instructions Optional additional instructions.
	 * @return string|false Base64 encoded image data on success, false on failure.
	 */
	private function call_nextjs_api($file_path, $style, $additional_instructions)
	{
		// Get API configuration from settings
		$settings = get_option('photobooster_ai_settings', array());
		$crypto = new Photobooster_Ai_Crypto();

		// Get decrypted API key
		$api_key = '';
		if (!empty($settings['api_key'])) {
			$api_key = $crypto->decrypt_api_key($settings['api_key']);
		}

		// Get API endpoint (with fallback)
		$nextjs_api_url = !empty($settings['api_endpoint'])
			? $settings['api_endpoint']
			: PHOTOBOOSTER_AI_DEFAULT_ENDPOINT;

		// Validate API key exists
		if (empty($api_key)) {
			error_log('PhotoBooster AI: No API key configured. Please check plugin settings.');
			return array(
				'success' => false,
				'error' => 'API key not configured. Please check plugin settings.',
				'code' => 'missing_api_key'
			);
		}

		// Construct the prompt
		$prompt = $this->build_generation_prompt($style, $additional_instructions);

		// Get image file contents
		$image_contents = file_get_contents($file_path);
		if (false === $image_contents) {
			error_log('PhotoBooster AI: Failed to read image file: ' . $file_path);
			return false;
		}

		// Get image mime type
		$image_info = getimagesize($file_path);
		if (false === $image_info) {
			error_log('PhotoBooster AI: Failed to get image info for: ' . $file_path);
			return false;
		}
		$mime_type = $image_info['mime'];

		// Create multipart form data boundary
		$boundary = wp_generate_uuid4();

		// Build multipart form data payload
		$payload = $this->build_multipart_payload($image_contents, $mime_type, $prompt, $boundary);

		// Set up HTTP request arguments with authentication
		$args = array(
			'method'  => 'POST',
			'timeout' => PHOTOBOOSTER_AI_API_TIMEOUT,
			'headers' => array(
				'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				'Authorization' => 'Bearer ' . $api_key,
				'X-Plugin-Version' => PHOTOBOOSTER_AI_VERSION,
				'User-Agent' => 'PhotoBooster-AI-Plugin/' . PHOTOBOOSTER_AI_VERSION,
			),
			'body'    => $payload,
		);

		// Make request to NextJS API
		$response = wp_remote_post($nextjs_api_url, $args);

		if (is_wp_error($response)) {
			error_log('PhotoBooster AI: NextJS API request failed: ' . $response->get_error_message());
			return array(
				'success' => false,
				'error' => 'API request failed: ' . $response->get_error_message(),
				'code' => 'request_failed'
			);
		}

		$response_code = wp_remote_retrieve_response_code($response);

		// Handle authentication failures
		if (in_array($response_code, array(401, 403))) {
			error_log('PhotoBooster AI: Authentication failed with code: ' . $response_code);

			// Update connection status in settings
			$settings['connection_status'] = 'failed';
			$settings['last_error'] = 'Authentication failed';
			update_option('photobooster_ai_settings', $settings);

			return array(
				'success' => false,
				'error' => 'Authentication failed. Please check your API key in plugin settings.',
				'code' => 'auth_failed'
			);
		}

		if (200 !== $response_code) {
			error_log('PhotoBooster AI: NextJS API returned error code: ' . $response_code);
			return array(
				'success' => false,
				'error' => 'API returned error code: ' . $response_code,
				'code' => 'api_error'
			);
		}

		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body, true);

		if (! $response_data || ! isset($response_data['success']) || ! $response_data['success']) {
			error_log('PhotoBooster AI: NextJS API returned error: ' . ($response_data['error'] ?? 'Unknown error'));
			return false;
		}

		if (! isset($response_data['image'])) {
			error_log('PhotoBooster AI: NextJS API response missing image data');
			return false;
		}

		return $response_data['image'];
	}

	/**
	 * Build generation prompt combining style and additional instructions.
	 *
	 * @param string $style                   Generation style.
	 * @param string $additional_instructions Additional instructions.
	 * @return string Combined prompt.
	 */
	private function build_generation_prompt($style, $additional_instructions)
	{
		$style_prompts = array(
			'Professional' => 'Create a professional, high-quality enhanced version of this image with improved lighting, sharpness, and color balance.',
			'Creative'     => 'Transform this image with creative enhancements, artistic flair, and dynamic visual improvements.',
			'Artistic'     => 'Apply artistic enhancements to this image with painterly effects, rich textures, and creative interpretation.',
			'Modern'       => 'Modernize this image with contemporary styling, clean aesthetics, and current design trends.',
			'Vintage'      => 'Give this image a vintage look with retro styling, warm tones, and classic photographic effects.',
			'Minimalist'   => 'Enhance this image with minimalist aesthetics, clean lines, and simplified composition.',
		);

		$base_prompt = $style_prompts[$style] ?? $style_prompts['Professional'];

		if (! empty($additional_instructions)) {
			$base_prompt .= ' Additional requirements: ' . $additional_instructions;
		}

		return $base_prompt;
	}

	/**
	 * Build multipart form data payload for NextJS API.
	 *
	 * @param string $image_contents Image file contents.
	 * @param string $mime_type      Image MIME type.
	 * @param string $prompt         Generation prompt.
	 * @param string $boundary       Multipart boundary.
	 * @return string Multipart payload.
	 */
	private function build_multipart_payload($image_contents, $mime_type, $prompt, $boundary)
	{
		$payload = '';

		// Add prompt field
		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="prompt"' . "\r\n";
		$payload .= "\r\n";
		$payload .= $prompt . "\r\n";

		// Add image field
		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="image"; filename="seed.jpg"' . "\r\n";
		$payload .= 'Content-Type: ' . $mime_type . "\r\n";
		$payload .= "\r\n";
		$payload .= $image_contents . "\r\n";

		// Close boundary
		$payload .= '--' . $boundary . '--' . "\r\n";

		return $payload;
	}

	/**
	 * Save generated image data to WordPress media library.
	 *
	 * @param string $base64_image_data Base64 encoded image data from NextJS API.
	 * @param int    $original_attachment_id Original seed attachment ID.
	 * @return array|false Array with new attachment info on success, false on failure.
	 */
	private function save_generated_image_to_media_library($base64_image_data, $original_attachment_id)
	{
		// Get original attachment info
		$original_attachment = get_post($original_attachment_id);
		if (! $original_attachment) {
			error_log('PhotoBooster AI: Original attachment not found: ' . $original_attachment_id);
			return false;
		}

		$original_file_path = get_attached_file($original_attachment_id);
		$original_filename = basename($original_file_path);
		$original_file_info = pathinfo($original_filename);

		// Generate unique filename
		$timestamp = current_time('timestamp');
		$new_filename = $original_file_info['filename'] . '-ai-enhanced-' . $timestamp . '.' . $original_file_info['extension'];

		// Decode base64 image data
		$image_data = base64_decode($base64_image_data);
		if (false === $image_data) {
			error_log('PhotoBooster AI: Failed to decode base64 image data');
			return false;
		}

		// Get WordPress uploads directory
		$upload_dir = wp_upload_dir();
		if ($upload_dir['error']) {
			error_log('PhotoBooster AI: Upload directory error: ' . $upload_dir['error']);
			return false;
		}

		// Create full file path
		$new_file_path = $upload_dir['path'] . '/' . $new_filename;

		// Save image file
		$bytes_written = file_put_contents($new_file_path, $image_data);
		if (false === $bytes_written) {
			error_log('PhotoBooster AI: Failed to write image file: ' . $new_file_path);
			return false;
		}

		// Detect MIME type
		$file_type = wp_check_filetype($new_filename);
		if (! $file_type['type']) {
			// Clean up file if MIME type detection fails
			unlink($new_file_path);
			error_log('PhotoBooster AI: Invalid file type for: ' . $new_filename);
			return false;
		}

		// Prepare attachment data
		$attachment_data = array(
			'guid'           => $upload_dir['url'] . '/' . $new_filename,
			'post_mime_type' => $file_type['type'],
			'post_title'     => $original_file_info['filename'] . ' (AI Enhanced)',
			'post_content'   => 'AI enhanced version of: ' . $original_attachment->post_title,
			'post_status'    => 'inherit',
			'post_parent'    => $original_attachment->post_parent, // Keep same parent as original
		);

		// Insert attachment into database
		$attachment_id = wp_insert_attachment($attachment_data, $new_file_path);
		if (is_wp_error($attachment_id)) {
			// Clean up file if database insertion fails
			unlink($new_file_path);
			error_log('PhotoBooster AI: Failed to insert attachment: ' . $attachment_id->get_error_message());
			return false;
		}

		// Include required files for image processing
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Generate attachment metadata (thumbnails, etc.)
		$attachment_metadata = wp_generate_attachment_metadata($attachment_id, $new_file_path);
		wp_update_attachment_metadata($attachment_id, $attachment_metadata);

		// Copy some metadata from original attachment
		$this->copy_attachment_metadata($original_attachment_id, $attachment_id);

		// Get the new attachment URL
		$attachment_url = wp_get_attachment_url($attachment_id);

		error_log(sprintf(
			'PhotoBooster AI: Successfully created new attachment %d from original %d',
			$attachment_id,
			$original_attachment_id
		));

		return array(
			'id'  => $attachment_id,
			'url' => $attachment_url,
		);
	}

	/**
	 * Copy relevant metadata from original attachment to new attachment.
	 *
	 * @param int $original_attachment_id Original attachment ID.
	 * @param int $new_attachment_id      New attachment ID.
	 */
	private function copy_attachment_metadata($original_attachment_id, $new_attachment_id)
	{
		// Copy alt text
		$alt_text = get_post_meta($original_attachment_id, '_wp_attachment_image_alt', true);
		if ($alt_text) {
			update_post_meta($new_attachment_id, '_wp_attachment_image_alt', $alt_text . ' (AI Enhanced)');
		}

		// Copy caption if exists
		$original_post = get_post($original_attachment_id);
		if ($original_post && ! empty($original_post->post_excerpt)) {
			wp_update_post(array(
				'ID'           => $new_attachment_id,
				'post_excerpt' => $original_post->post_excerpt . ' (AI Enhanced)',
			));
		}

		// Add custom meta to track the relationship
		update_post_meta($new_attachment_id, '_photobooster_ai_source_attachment', $original_attachment_id);
		update_post_meta($new_attachment_id, '_photobooster_ai_generated', current_time('mysql'));
	}
}
