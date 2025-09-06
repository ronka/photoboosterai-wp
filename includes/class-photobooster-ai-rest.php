<?php

/**
 * REST API scaffold for Photobooster AI.
 *
 * @package Photobooster_Ai
 */

class Photobooster_Ai_REST {
	/**
	 * Plugin namespace for routes.
	 *
	 * @var string
	 */
	private $namespace = 'photobooster-ai/v1';

	/**
	 * Register all plugin REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/noop',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'route_noop' ),
					'permission_callback' => array( $this, 'permissions_uploaders_with_nonce' ),
				),
			),
			array( 'args' => array() )
		);
	}

	/**
	 * Simple connectivity route returning ok: true.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function route_noop( $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Permission callback that requires upload_files and a valid REST nonce.
	 *
	 * @return bool
	 */
	public function permissions_uploaders_with_nonce() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		if ( ! current_user_can( 'upload_files' ) ) {
			return false;
		}
		// Check nonce from X-WP-Nonce header.
		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
		if ( ! $nonce ) {
			return false;
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Validate that the current user can modify the given attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool True if user can modify this attachment.
	 */
	public function validate_attachment_ownership( $attachment_id ) {
		// Ensure it's a valid attachment
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return false;
		}

		// Check if user can edit this attachment
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
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
	public function validate_attachment_file_type( $attachment_id ) {
		$mime_type = get_post_mime_type( $attachment_id );
		
		$eligible_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/webp',
		);

		return in_array( $mime_type, $eligible_types, true );
	}

	/**
	 * Get attachment file path and validate it exists and is readable.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string|false File path if valid, false otherwise.
	 */
	public function get_attachment_file_path( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		
		if ( ! $file_path || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
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
	public function validate_attachment_for_processing( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		
		if ( ! $attachment_id ) {
			return array(
				'valid' => false,
				'error' => 'Invalid attachment ID.',
			);
		}

		if ( ! $this->validate_attachment_ownership( $attachment_id ) ) {
			return array(
				'valid' => false,
				'error' => 'You do not have permission to modify this attachment.',
			);
		}

		if ( ! $this->validate_attachment_file_type( $attachment_id ) ) {
			return array(
				'valid' => false,
				'error' => 'Attachment must be a JPEG, PNG, or WebP image.',
			);
		}

		if ( ! $this->get_attachment_file_path( $attachment_id ) ) {
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
}
