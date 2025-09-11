<?php

/**
 * The crypto functionality of the plugin.
 *
 * @link       https://photobooster.ai
 * @since      1.0.0
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/includes
 */

/**
 * The crypto functionality of the plugin.
 *
 * Handles encryption and decryption of sensitive data like API keys.
 *
 * @package    Photobooster_Ai
 * @subpackage Photobooster_Ai/includes
 * @author     PhotoBooster AI <info@photobooster.ai>
 */
class Photobooster_Ai_Crypto
{

    /**
     * Store an API key (plain text for debugging).
     *
     * @since    1.0.0
     * @param    string    $plaintext    The API key to store.
     * @return   string|false           The API key or false on failure.
     */
    public function encrypt_api_key($plaintext)
    {
        if (empty($plaintext)) {
            error_log('PhotoBooster AI Crypto: Empty plaintext provided');
            return false;
        }

        // Debug logging
        error_log('PhotoBooster AI Crypto: Storing API key, length: ' . strlen($plaintext));
        error_log('PhotoBooster AI Crypto: API key (first 10 chars): ' . substr($plaintext, 0, 10) . '...');

        // Just return the plain text for now (no encryption)
        return $plaintext;
    }

    /**
     * Retrieve an API key (plain text for debugging).
     *
     * @since    1.0.0
     * @param    string    $stored_key    The stored API key.
     * @return   string|false           The API key or false on failure.
     */
    public function decrypt_api_key($stored_key)
    {
        if (empty($stored_key)) {
            return false;
        }

        // Just return the plain text (no decryption needed)
        return $stored_key;
    }

    /**
     * Validate API key format.
     *
     * @since    1.0.0
     * @param    string    $key    The API key to validate.
     * @return   bool              True if valid, false otherwise.
     */
    public function is_key_valid($key)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }

        // Check minimum length (more reasonable minimum)
        if (strlen($key) < 16) {
            return false;
        }

        // Check for maximum length (prevent extremely long inputs)
        if (strlen($key) > 500) {
            return false;
        }

        // Allow most printable ASCII characters (excluding whitespace and quotes for security)
        if (!preg_match('/^[!-~]+$/', $key) || preg_match('/[\s"\']/', $key)) {
            return false;
        }

        return true;
    }
}
