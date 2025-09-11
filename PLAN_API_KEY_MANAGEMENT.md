# Plan: Secure API Key Management for PhotoBooster AI Plugin

## Overview
Add a secure admin settings page for API key management and integrate it with the REST API calls to the NextJS backend.

## 1. Database Storage Strategy

### 1.1 Option Storage
- Use WordPress Options API for storing the encrypted API key
- Option name: `photobooster_ai_api_key` 
- Store as encrypted string using WordPress built-in encryption functions
- Use WordPress salts for additional security

### 1.2 Security Measures
- **Encryption**: Use `wp_hash()` with unique salts for key derivation
- **Access Control**: Only administrators can view/modify settings
- **Sanitization**: Validate and sanitize API key input
- **Nonce Protection**: CSRF protection for form submissions
- **Database Security**: Store encrypted keys, never plain text

## 2. Admin Page Implementation

### 2.1 Admin Menu Integration
- Add new admin page under "Settings" menu
- Menu title: "PhotoBooster AI Settings"
- Required capability: `manage_options` (admin only)
- Hook into existing admin class structure

### 2.2 Settings Page Structure
```
Settings Page Components:
├── Page Header ("PhotoBooster AI Settings")
├── API Configuration Section
│   ├── API Key Input Field (password type)
│   ├── API Endpoint URL Field (with default)
│   ├── Connection Test Button
│   └── Status Display
├── Usage Statistics Section (future)
└── Save Settings Button
```

### 2.3 Form Security
- WordPress Settings API integration
- Automatic nonce generation and verification
- Input sanitization and validation
- Error handling and user feedback

## 3. File Structure Changes

### 3.1 New Files to Create
```
admin/
├── partials/
│   └── photobooster-ai-settings-display.php    # Settings page HTML
└── class-photobooster-ai-settings.php          # Settings management class

includes/
└── class-photobooster-ai-crypto.php            # Encryption/decryption utilities
```

### 3.2 Files to Modify
```
admin/class-photobooster-ai-admin.php            # Add admin menu hook
includes/class-photobooster-ai.php               # Load new dependencies
includes/class-photobooster-ai-rest.php          # API key integration (line 318)
```

## 4. Class Structure

### 4.1 New Settings Class (`Photobooster_Ai_Settings`)
```php
class Photobooster_Ai_Settings {
    
    // Properties
    private $option_name = 'photobooster_ai_settings';
    private $crypto;
    
    // Methods
    public function __construct()                    // Initialize hooks
    public function add_admin_menu()                 // Register settings page
    public function settings_page_callback()         // Display settings page
    public function register_settings()              // Register WordPress settings
    public function sanitize_api_key($input)        // Validate and sanitize input
    public function test_api_connection()            // AJAX endpoint for testing
    public function get_encrypted_api_key()         // Retrieve encrypted key
    public function get_decrypted_api_key()         // Get key for API calls
}
```

### 4.2 New Crypto Class (`Photobooster_Ai_Crypto`)
```php
class Photobooster_Ai_Crypto {
    
    // Methods
    public function encrypt_api_key($key)           // Encrypt API key for storage
    public function decrypt_api_key($encrypted)     // Decrypt API key for use
    private function generate_encryption_key()      // Create encryption key from WP salts
    public function is_key_valid($key)             // Validate API key format
    private function get_nonce()                   // Generate unique nonce for encryption
}
```

## 5. WordPress Settings API Integration

### 5.1 Settings Registration
- Setting group: `photobooster_ai_settings`
- Option names:
  - `photobooster_ai_api_key` (encrypted)
  - `photobooster_ai_api_endpoint` (plain text URL)
  - `photobooster_ai_connection_status` (last test status)
  - `photobooster_ai_last_test` (timestamp)

### 5.2 Settings Fields
```php
// Field definitions
add_settings_field(
    'api_key',
    'API Key',
    array($this, 'api_key_field_callback'),
    'photobooster_ai_settings',
    'api_section'
);

add_settings_field(
    'api_endpoint',
    'API Endpoint URL',
    array($this, 'api_endpoint_field_callback'),
    'photobooster_ai_settings',
    'api_section'
);
```

## 6. REST API Integration

### 6.1 Modify `call_nextjs_api()` Method in `class-photobooster-ai-rest.php`

**Current code at line 318:**
```php
$nextjs_api_url = 'http://localhost:3000/api/generate-image'; // TODO: Make configurable
```

**New implementation:**
```php
private function call_nextjs_api( $file_path, $style, $additional_instructions ) {
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
        : 'http://localhost:3000/api/generate-image';
    
    // Validate API key exists
    if (empty($api_key)) {
        error_log('PhotoBooster AI: No API key configured');
        return false;
    }
    
    // ... rest of existing method with added authentication header
}
```

### 6.2 HTTP Request Headers Update
```php
// Set up HTTP request arguments
$args = array(
    'method'  => 'POST',
    'timeout' => 60,
    'headers' => array(
        'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        'Authorization' => 'Bearer ' . $api_key,
        'X-Plugin-Version' => PHOTOBOOSTER_AI_VERSION,
    ),
    'body'    => $payload,
);
```

## 7. Security Implementation Details

### 7.1 Encryption Strategy
```php
// Use WordPress constants for key derivation
private function generate_encryption_key() {
    $key_material = AUTH_KEY . SECURE_AUTH_KEY . NONCE_KEY;
    return hash('sha256', $key_material, true);
}

// Encrypt using sodium (PHP 7.2+)
public function encrypt_api_key($plaintext) {
    if (!function_exists('sodium_crypto_secretbox')) {
        // Fallback to OpenSSL
        return $this->encrypt_openssl($plaintext);
    }
    
    $key = $this->generate_encryption_key();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);
    
    return base64_encode($nonce . $ciphertext);
}
```

### 7.2 Access Control Implementation
```php
// In settings class constructor
public function __construct() {
    // Only load for administrators
    if (current_user_can('manage_options')) {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_photobooster_connection', array($this, 'test_api_connection'));
    }
}

// Permission callback for settings page
public function settings_page_capability() {
    return current_user_can('manage_options');
}
```

### 7.3 Input Sanitization
```php
public function sanitize_settings($input) {
    $sanitized = array();
    
    // Sanitize API key
    if (!empty($input['api_key'])) {
        $api_key = sanitize_text_field($input['api_key']);
        if ($this->crypto->is_key_valid($api_key)) {
            $sanitized['api_key'] = $this->crypto->encrypt_api_key($api_key);
        } else {
            add_settings_error('photobooster_ai_settings', 'invalid_api_key', 'Invalid API key format.');
        }
    }
    
    // Sanitize API endpoint
    if (!empty($input['api_endpoint'])) {
        $endpoint = esc_url_raw($input['api_endpoint']);
        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $sanitized['api_endpoint'] = $endpoint;
        } else {
            add_settings_error('photobooster_ai_settings', 'invalid_endpoint', 'Invalid API endpoint URL.');
        }
    }
    
    return $sanitized;
}
```

## 8. User Experience Features

### 8.1 Settings Page HTML Structure
```php
// In photobooster-ai-settings-display.php
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('photobooster_ai_settings'); ?>
    
    <form action="options.php" method="post">
        <?php
        settings_fields('photobooster_ai_settings');
        do_settings_sections('photobooster_ai_settings');
        ?>
        
        <div class="photobooster-connection-test">
            <button type="button" id="test-connection" class="button button-secondary">
                Test Connection
            </button>
            <span id="connection-status" class="status"></span>
        </div>
        
        <?php submit_button('Save Settings'); ?>
    </form>
</div>
```

### 8.2 AJAX Connection Testing
```javascript
// In admin JavaScript
jQuery(document).ready(function($) {
    $('#test-connection').on('click', function() {
        var button = $(this);
        var status = $('#connection-status');
        
        button.prop('disabled', true).text('Testing...');
        status.removeClass('success error').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_photobooster_connection',
                nonce: photoboosterAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    status.addClass('success').text('✓ Connection successful');
                } else {
                    status.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                status.addClass('error').text('✗ Connection test failed');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
});
```

## 9. Implementation Steps

### Phase 1: Core Infrastructure
1. **Create `includes/class-photobooster-ai-crypto.php`**
   - Implement encryption/decryption methods
   - Add API key validation
   - Test encryption functionality

2. **Create `admin/class-photobooster-ai-settings.php`**
   - Basic class structure
   - WordPress Settings API integration
   - Menu registration

3. **Update `includes/class-photobooster-ai.php`**
   - Load new dependencies
   - Initialize settings class

### Phase 2: Settings Interface
1. **Create `admin/partials/photobooster-ai-settings-display.php`**
   - HTML form structure
   - Settings fields layout
   - Status indicators

2. **Update `admin/class-photobooster-ai-admin.php`**
   - Add admin menu hook
   - Enqueue admin styles/scripts
   - Add AJAX handlers

3. **Implement form validation and sanitization**
   - Input validation
   - Error handling
   - Success messages

### Phase 3: REST API Integration
1. **Update `includes/class-photobooster-ai-rest.php`**
   - Modify `call_nextjs_api()` method (starting at line 318)
   - Add API key retrieval
   - Add authentication headers
   - Implement error handling

2. **Test end-to-end functionality**
   - Settings save/retrieve
   - API calls with authentication
   - Error scenarios

### Phase 4: Security & Polish
1. **Security audit**
   - Penetration testing
   - Code review
   - Encryption verification

2. **User experience improvements**
   - Connection testing
   - Status indicators
   - Help documentation

## 10. Configuration Constants

### 10.1 Add to main plugin file
```php
// Default configuration
define('PHOTOBOOSTER_AI_DEFAULT_ENDPOINT', 'http://localhost:3000/api/generate-image');
define('PHOTOBOOSTER_AI_API_TIMEOUT', 60);
define('PHOTOBOOSTER_AI_SETTINGS_CAPABILITY', 'manage_options');

// Security settings
define('PHOTOBOOSTER_AI_ENCRYPTION_METHOD', 'sodium');
define('PHOTOBOOSTER_AI_KEY_MIN_LENGTH', 32);
```

### 10.2 Environment-specific configuration
```php
// In wp-config.php (optional)
define('PHOTOBOOSTER_AI_API_ENDPOINT', 'https://api.photobooster.ai/v1/generate');
define('PHOTOBOOSTER_AI_DEBUG_MODE', false);
```

## 11. Error Handling Strategy

### 11.1 API Key Missing/Invalid
```php
// In call_nextjs_api method
if (empty($api_key)) {
    error_log('PhotoBooster AI: No API key configured');
    return array(
        'success' => false,
        'error' => 'API key not configured. Please check plugin settings.',
        'code' => 'missing_api_key'
    );
}
```

### 11.2 Authentication Failures
```php
// Handle HTTP 401/403 responses
if (in_array($response_code, array(401, 403))) {
    error_log('PhotoBooster AI: Authentication failed with code: ' . $response_code);
    
    // Update connection status
    $settings = get_option('photobooster_ai_settings', array());
    $settings['connection_status'] = 'failed';
    $settings['last_error'] = 'Authentication failed';
    update_option('photobooster_ai_settings', $settings);
    
    return array(
        'success' => false,
        'error' => 'Authentication failed. Please check your API key.',
        'code' => 'auth_failed'
    );
}
```

## 12. Testing Checklist

### 12.1 Functionality Tests
- [ ] Settings page displays correctly
- [ ] API key encryption/decryption works
- [ ] Settings save and retrieve properly
- [ ] Connection testing works
- [ ] API calls include authentication
- [ ] Error handling works correctly

### 12.2 Security Tests
- [ ] Only administrators can access settings
- [ ] API keys are encrypted in database
- [ ] Nonce verification works
- [ ] Input sanitization prevents XSS
- [ ] SQL injection protection
- [ ] No API keys in logs

### 12.3 User Experience Tests
- [ ] Clear error messages
- [ ] Success confirmations
- [ ] Connection status indicators
- [ ] Form validation feedback
- [ ] Mobile responsive design

## 13. Future Enhancements

### 13.1 Advanced Features
- Multiple API key support (dev/staging/prod)
- Usage analytics and rate limiting
- API key rotation scheduling
- Webhook configuration
- Batch processing settings

### 13.2 Integration Improvements
- WooCommerce integration settings
- Custom post type configuration
- Image optimization preferences
- Automatic backup settings

---

**Implementation Priority:** High
**Estimated Development Time:** 2-3 days
**Security Review Required:** Yes
**Documentation Needed:** Admin user guide, developer API reference
