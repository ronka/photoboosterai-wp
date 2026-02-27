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
			'/credits',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array($this, 'route_credits'),
					'permission_callback' => array($this, 'permissions_uploaders_with_nonce'),
				),
			)
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
						'preset_id' => array(
							'required'          => false,
							'validate_callback' => function ($param) {
								$valid_presets = array(
									'white-infinity',
									'minimal-shadow',
									'color-pop',
									'lifestyle-neutral',
									'glossy-reflection',
									'soft-pastel',
									'natural-light-desk',
									'dramatic-dark',
									'plant-props',
									'gradient-glow'
								);
								return empty($param) || in_array($param, $valid_presets, true);
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
	 * Return the current user's credit balance from the external API.
	 *
	 * @return WP_REST_Response
	 */
	public function route_credits()
	{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		$settings = get_option('photobooster_ai_settings', array());
		$crypto   = new Photobooster_Ai_Crypto();

		$api_key = '';
		if (!empty($settings['api_key'])) {
			$api_key = $crypto->decrypt_api_key($settings['api_key']);
		}

		if (empty($api_key)) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'API key not configured. Please check plugin settings.',
				),
				400
			);
		}

		$credits_url = photobooster_ai_get_credits_endpoint();
		$response    = wp_remote_get(
			add_query_arg('apiKey', rawurlencode($api_key), $credits_url),
			array(
				'timeout' => PHOTOBOOSTER_AI_API_TIMEOUT,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		if (is_wp_error($response)) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Failed to connect to API: ' . $response->get_error_message(),
				),
				502
			);
		}

		$status_code = wp_remote_retrieve_response_code($response);
		$body        = wp_remote_retrieve_body($response);
		$data        = json_decode($body, true);

		if ($status_code !== 200) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => isset($data['error']) ? $data['error'] : 'API request failed with status ' . $status_code,
				),
				$status_code
			);
		}

		return new WP_REST_Response(
			array(
				'success'       => true,
				'credits'       => $data['credits'] ?? 0,
				'lastResetDate' => $data['lastResetDate'] ?? null,
				'userId'        => $data['userId'] ?? null,
			),
			200
		);
	}

	/**
	 * Generate AI enhanced image from seed image.
	 *
	 * @param WP_REST_Request $request Request containing attachment_id, style, and optional additional_instructions.
	 * @return WP_REST_Response
	 */
	public function route_generate_image($request)
	{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		// Log the incoming request for debugging

		// Get sanitized parameters (already validated by route args)
		$attachment_id = $request->get_param('attachment_id');
		$preset_id = $request->get_param('preset_id') ?: '';
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

		// Handle optional reference image upload
		$reference_file_path = null;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if (isset($_FILES['reference_image']) && UPLOAD_ERR_OK === $_FILES['reference_image']['error']) {
			$ref_file      = $_FILES['reference_image']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$ref_allowed   = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp');
			$ref_max_bytes = 10 * 1024 * 1024; // 10MB

			$ref_mime = isset($ref_file['type']) ? sanitize_mime_type($ref_file['type']) : '';
			$ref_size = isset($ref_file['size']) ? (int) $ref_file['size'] : 0;
			$ref_tmp  = isset($ref_file['tmp_name']) ? $ref_file['tmp_name'] : '';

			if (! in_array($ref_mime, $ref_allowed, true)) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => 'Reference image must be a JPEG, PNG, or WebP file.',
					),
					400
				);
			}

			if ($ref_size > $ref_max_bytes) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => 'Reference image must be 10MB or smaller.',
					),
					400
				);
			}

			if (! $ref_tmp || ! is_uploaded_file($ref_tmp)) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => 'Invalid reference image upload.',
					),
					400
				);
			}

			$reference_file_path = $ref_tmp;
		}

		// Log the generation attempt
		try {
			// Call NextJS API integration
			$api_result = $this->call_nextjs_api($file_path, $preset_id, $additional_instructions, $reference_file_path);

			// Check if API call returned an error
			if (is_array($api_result) && isset($api_result['success']) && ! $api_result['success']) {
				throw new Exception($api_result['error'] ?? 'Unknown API error');
			}

			// If successful, $api_result should be the base64 image data
			$generated_image_data = $api_result;
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
	 * @param string      $file_path             Path to the seed image file.
	 * @param string      $preset_id             Selected preset ID.
	 * @param string      $additional_instructions Optional additional instructions.
	 * @param string|null $reference_file_path   Optional path to a reference image file.
	 * @return string|false Base64 encoded image data on success, false on failure.
	 */
	private function call_nextjs_api($file_path, $preset_id, $additional_instructions, $reference_file_path = null)
	{
		// Get API configuration from settings
		$settings = get_option('photobooster_ai_settings', array());
		$crypto = new Photobooster_Ai_Crypto();

		// Get decrypted API key
		$api_key = '';
		if (!empty($settings['api_key'])) {
			$api_key = $crypto->decrypt_api_key($settings['api_key']);
		}

		// Get API endpoint
		$settings_instance = new Photobooster_Ai_Settings();
		$nextjs_api_url = $settings_instance->get_api_endpoint();

		// Validate API key exists
		if (empty($api_key)) {
			return array(
				'success' => false,
				'error' => 'API key not configured. Please check plugin settings.',
				'code' => 'missing_api_key'
			);
		}

		// Construct the prompt
		$prompt = $this->build_generation_prompt($preset_id, $additional_instructions);

		// Get image file contents
		$image_contents = file_get_contents($file_path);
		if (false === $image_contents) {
			return array(
				'success' => false,
				'error' => 'Failed to read image file',
				'code' => 'file_read_error'
			);
		}

		// Get image mime type
		$image_info = getimagesize($file_path);
		if (false === $image_info) {
			return array(
				'success' => false,
				'error' => 'Failed to get image information',
				'code' => 'image_info_error'
			);
		}
		$mime_type = $image_info['mime'];

		// Read optional reference image
		$reference_image_contents = null;
		$reference_mime_type      = null;
		if ($reference_file_path) {
			$ref_contents = file_get_contents($reference_file_path);
			if (false !== $ref_contents) {
				$ref_info = getimagesize($reference_file_path);
				if (false !== $ref_info) {
					$reference_image_contents = $ref_contents;
					$reference_mime_type      = $ref_info['mime'];
				}
			}
		}

		// Create multipart form data boundary
		$boundary = wp_generate_uuid4();

		// Build multipart form data payload
		$payload = $this->build_multipart_payload($image_contents, $mime_type, $prompt, $boundary, $reference_image_contents, $reference_mime_type);

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
			return array(
				'success' => false,
				'error' => 'API request failed: ' . $response->get_error_message(),
				'code' => 'request_failed'
			);
		}

		$response_code = wp_remote_retrieve_response_code($response);

		// Handle authentication failures
		if (in_array($response_code, array(401, 403))) {

			return array(
				'success' => false,
				'error' => 'Authentication failed. Please check your API key in plugin settings.',
				'code' => 'auth_failed'
			);
		}

		if (200 !== $response_code) {
			$error_message = $this->parse_api_error_response($response, $response_code);
			return array(
				'success' => false,
				'error' => $error_message,
				'code' => 'api_error'
			);
		}

		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body, true);

		if (! $response_data || ! isset($response_data['success']) || ! $response_data['success']) {
			$error_message = 'Unknown API error';

			if (isset($response_data['error'])) {
				$error_message = $this->extract_readable_error($response_data['error']);
			}

			return array(
				'success' => false,
				'error' => $error_message,
				'code' => 'api_response_error'
			);
		}

		if (! isset($response_data['image'])) {
			return array(
				'success' => false,
				'error' => 'API response missing image data',
				'code' => 'missing_image_data'
			);
		}

		return $response_data['image'];
	}

	/**
	 * Build generation prompt combining preset and additional instructions.
	 *
	 * @param string $preset_id               Selected preset ID.
	 * @param string $additional_instructions Additional instructions.
	 * @return string Combined prompt.
	 */
	private function build_generation_prompt($preset_id, $additional_instructions)
	{
		$preset_prompts = array(
			'white-infinity'     => 'Transform the product into a professional studio photo on a pure seamless white background, soft even lighting, crisp details, no shadows, commercial catalog style.',
			'minimal-shadow'     => 'Enhance the product with a soft studio setup, light gray background, gentle diffused shadows beneath and around the object, modern e-commerce look.',
			'color-pop'          => 'Place the product in a vibrant studio scene with a single bold background color, soft light reflections, high contrast to emphasize the product, editorial style.',
			'lifestyle-neutral'  => 'Render the product in a styled lifestyle scene with neutral tones, clean surfaces, natural daylight effect, minimal furniture or props, calm and aspirational mood.',
			'glossy-reflection'  => 'Place the product on a glossy reflective surface, clean studio lighting from above, subtle reflection visible underneath, premium catalog feel.',
			'soft-pastel'        => 'Transform into a studio shot with pastel background (choose color), diffused lighting, dreamy highlights, playful and elegant atmosphere.',
			'natural-light-desk' => 'Show the product on a modern tabletop with sunlight filtering in from the side, natural shadows, lifestyle photo with airy and authentic feel.',
			'dramatic-dark'      => 'Place the product in a dramatic studio scene with deep black background, spotlight glow, strong contrast, cinematic and luxurious mood.',
			'plant-props'        => 'Render the product in a styled studio scene with minimal props (green plants, books, small decor), soft neutral background, aspirational lifestyle aesthetic.',
			'gradient-glow'      => 'Enhance the product with a soft studio gradient background (two tones), diffused top lighting, clean balanced shadows, futuristic product showcase.',
		);

		// Default to white-infinity if no preset selected or invalid preset
		$base_prompt = $preset_prompts[$preset_id] ?? $preset_prompts['white-infinity'];

		if (! empty($additional_instructions)) {
			$base_prompt .= ' Additional requirements: ' . $additional_instructions;
		}

		return $base_prompt;
	}

	/**
	 * Build multipart form data payload for NextJS API.
	 *
	 * @param string      $image_contents            Image file contents.
	 * @param string      $mime_type                 Image MIME type.
	 * @param string      $prompt                    Generation prompt.
	 * @param string      $boundary                  Multipart boundary.
	 * @param string|null $reference_image_contents  Optional reference image file contents.
	 * @param string|null $reference_mime_type       Optional reference image MIME type.
	 * @return string Multipart payload.
	 */
	private function build_multipart_payload($image_contents, $mime_type, $prompt, $boundary, $reference_image_contents = null, $reference_mime_type = null)
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

		// Add optional reference image field
		if (null !== $reference_image_contents && null !== $reference_mime_type) {
			$payload .= '--' . $boundary . "\r\n";
			$payload .= 'Content-Disposition: form-data; name="reference_image"; filename="reference.jpg"' . "\r\n";
			$payload .= 'Content-Type: ' . $reference_mime_type . "\r\n";
			$payload .= "\r\n";
			$payload .= $reference_image_contents . "\r\n";
		}

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
			return false;
		}

		// Get WordPress uploads directory
		$upload_dir = wp_upload_dir();
		if ($upload_dir['error']) {
			return false;
		}

		// Create full file path
		$new_file_path = $upload_dir['path'] . '/' . $new_filename;

		// Save image file
		$bytes_written = file_put_contents($new_file_path, $image_data);
		if (false === $bytes_written) {
			return false;
		}

		// Detect MIME type
		$file_type = wp_check_filetype($new_filename);
		if (! $file_type['type']) {
			// Clean up file if MIME type detection fails
			wp_delete_file($new_file_path);
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
			wp_delete_file($new_file_path);
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

		return array(
			'id'  => $attachment_id,
			'url' => $attachment_url,
		);
	}

	/**
	 * Extract readable error message from nested JSON error structures.
	 *
	 * @param mixed $error_data Error data that might be nested JSON.
	 * @return string Readable error message.
	 */
	private function extract_readable_error($error_data)
	{
		// If it's already a simple string, return it
		if (is_string($error_data) && strpos($error_data, '{') !== 0) {
			return $error_data;
		}

		// If it's a JSON string, try to decode it
		if (is_string($error_data)) {
			$decoded = json_decode($error_data, true);
			if ($decoded && is_array($decoded)) {
				$error_data = $decoded;
			}
		}

		// If it's an array, look for error structures
		if (is_array($error_data)) {
			// Check for nested error object
			if (isset($error_data['error'])) {
				return $this->extract_readable_error($error_data['error']);
			}

			// Check for Google AI API error structure
			if (isset($error_data['code'], $error_data['message'])) {
				$code = $error_data['code'];
				$message = $error_data['message'];

				switch ($code) {
					case 429:
						if (strpos($message, 'quota') !== false) {
							return 'Google AI API quota exceeded. Please wait a few minutes and try again, or upgrade your Google AI API plan for higher limits.';
						}
						return 'Rate limit exceeded. Please wait a moment and try again.';

					case 401:
						return 'Google AI API authentication failed. Please check your Google AI API key.';

					case 403:
						return 'Access denied. Please check your Google AI API permissions.';

					case 400:
						return 'Invalid request. Please contact support if this persists.';

					default:
						return 'API error: ' . $message;
				}
			}

			// Check for message field
			if (isset($error_data['message'])) {
				return $error_data['message'];
			}
		}

		// Fallback for complex structures
		return 'An error occurred while processing your request. Please try again later.';
	}

	/**
	 * Parse complex API error responses into user-friendly messages.
	 *
	 * @param array|WP_Error $response HTTP response object.
	 * @param int $response_code HTTP response code.
	 * @return string User-friendly error message.
	 */
	private function parse_api_error_response($response, $response_code)
	{
		// Default error message
		$default_message = 'API request failed with code: ' . $response_code;

		// Get response body
		$response_body = wp_remote_retrieve_body($response);
		if (empty($response_body)) {
			return $default_message;
		}

		// Try to parse JSON response
		$response_data = json_decode($response_body, true);
		if (!$response_data) {
			return $default_message;
		}

		// Check for nested error structure
		if (isset($response_data['error'])) {
			$error_content = $response_data['error'];

			// If error is a JSON string, decode it
			if (is_string($error_content)) {
				$nested_error = json_decode($error_content, true);
				if ($nested_error && isset($nested_error['error'])) {
					$error_content = $nested_error['error'];
				}
			}

			// Handle Google AI API quota errors
			if (is_array($error_content) && isset($error_content['code'], $error_content['message'])) {
				$error_code = $error_content['code'];
				$error_message = $error_content['message'];

				switch ($error_code) {
					case 429:
						if (strpos($error_message, 'quota') !== false) {
							return 'Google AI API quota exceeded. Please wait a few minutes and try again, or upgrade your Google AI API plan.';
						}
						return 'Too many requests. Please wait a moment and try again.';

					case 401:
						return 'Google AI API authentication failed. Please check your Google AI API key configuration.';

					case 403:
						return 'Access denied. Please check your Google AI API permissions.';

					case 400:
						return 'Invalid request format. Please contact support if this persists.';

					default:
						return 'Google AI API error: ' . $error_message;
				}
			}

			// Handle string error messages
			if (is_string($error_content)) {
				return $error_content;
			}
		}

		// Check for Clerk authentication errors in headers
		$headers = wp_remote_retrieve_headers($response);
		if (isset($headers['x-clerk-auth-reason'])) {
			$auth_reason = $headers['x-clerk-auth-reason'];
			$auth_message = isset($headers['x-clerk-auth-message']) ? $headers['x-clerk-auth-message'] : '';

			switch ($auth_reason) {
				case 'token-invalid':
					return 'Authentication error: Invalid API key format. Please check your API key configuration.';
				case 'token-expired':
					return 'Authentication error: API key has expired. Please generate a new API key.';
				case 'session-revoked':
					return 'Authentication error: API key has been revoked. Please generate a new API key.';
				default:
					return 'Authentication error: ' . $auth_message;
			}
		}

		// Handle common HTTP error codes
		switch ($response_code) {
			case 400:
				return 'Bad request. Please check your input parameters.';
			case 401:
				return 'Authentication failed. Please check your API key.';
			case 403:
				return 'Access forbidden. Please check your API key permissions.';
			case 404:
				return 'API endpoint not found. Please check your configuration.';
			case 429:
				return 'Rate limit exceeded. Please wait a moment and try again.';
			case 500:
				return 'Server error. Please try again later or contact support.';
			case 502:
				return 'Bad gateway. The API service may be temporarily unavailable.';
			case 503:
				return 'Service unavailable. Please try again later.';
			case 504:
				return 'Gateway timeout. The request took too long to process.';
			default:
				return $default_message;
		}
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
