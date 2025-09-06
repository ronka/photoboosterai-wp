# Task List: WordPress-NextJS AI Image Generation Integration

Based on PRD: `prd-wordpress-nextjs-ai-image-generation.md`

## Relevant Files

- `includes/class-photobooster-ai-rest.php` - **MODIFIED** - Added new /generate-image endpoint with complete NextJS API integration and media library handling
- `admin/react-app/src/App.tsx` - **MODIFIED** - Updated handleGeneratePhotos() to use real WordPress REST API calls with authentication
- `admin/react-app/src/App.css` - Contains existing styling for loading states and button interactions
- `admin/class-photobooster-ai-admin.php` - Contains existing WordPress REST API nonce configuration (PBAIEnhance localization)
- `includes/class-photobooster-ai.php` - Contains existing REST API hook registration

### Notes

- The existing REST API infrastructure is already in place with proper nonce validation
- Current React app has mock functionality that needs to be replaced
- WordPress media library integration will use existing WordPress functions
- NextJS API endpoint already exists and is compatible with expected request format

## Tasks

- [x] 1.0 Extend WordPress REST API with Image Generation Endpoint
  - [x] 1.1 Add new `/generate-image` route registration in `register_routes()` method
  - [x] 1.2 Create `route_generate_image()` callback method with parameter validation
  - [x] 1.3 Implement attachment ID validation using existing `validate_attachment_for_processing()` method
  - [x] 1.4 Add parameter sanitization for `style` and `additional_instructions` fields
  - [x] 1.5 Retrieve and prepare seed image file for NextJS API transmission

- [x] 2.0 Implement NextJS API Integration Layer
  - [x] 2.1 Create method to build FormData payload with image file and prompt
  - [x] 2.2 Construct combined prompt string incorporating style and additional instructions
  - [x] 2.3 Implement HTTP request to NextJS API using `wp_remote_post()` with proper headers
  - [x] 2.4 Add timeout and error handling for NextJS API communication
  - [x] 2.5 Parse and validate NextJS API response format

- [x] 3.0 Add Media Library Integration for Generated Images
  - [x] 3.1 Convert base64 image data from NextJS response to file format
  - [x] 3.2 Generate unique filename using pattern: `{original-filename}-ai-enhanced-{timestamp}.{ext}`
  - [x] 3.3 Save image file to WordPress uploads directory
  - [x] 3.4 Create new attachment record using `wp_insert_attachment()`
  - [x] 3.5 Generate attachment metadata using `wp_generate_attachment_metadata()`
  - [x] 3.6 Return new attachment ID, URL, and metadata to frontend

- [x] 4.0 Update React Frontend for Real API Integration
  - [x] 4.1 Replace mock functionality in `handleGeneratePhotos()` with real WordPress REST API call
  - [x] 4.2 Implement FormData construction with attachment_id, style, and additional_instructions
  - [x] 4.3 Add WordPress REST API nonce header for authentication
  - [x] 4.4 Configure proper API endpoint URL for WordPress REST API
  - [x] 4.5 Update state management to handle real API response data
  - [x] 4.6 Modify generated photos display to show newly created attachment

- [x] 5.0 Implement Error Handling and User Feedback
  - [x] 5.1 Add loading spinner styling and state management in React app
  - [x] 5.2 Implement JavaScript alert() calls for error conditions in React
  - [x] 5.3 Add comprehensive error logging in WordPress REST endpoint
  - [x] 5.4 Create specific error messages for common failure scenarios
  - [x] 5.5 Add WordPress admin notice integration for server-side errors
