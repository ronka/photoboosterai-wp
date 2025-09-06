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
}
