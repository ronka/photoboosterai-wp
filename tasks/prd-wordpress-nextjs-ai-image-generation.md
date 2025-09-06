# Product Requirements Document: WordPress-NextJS AI Image Generation Integration

## Introduction/Overview

This feature will integrate the WordPress PhotoBooster AI plugin's React-based UI with the NextJS API for AI-powered image generation. The WordPress plugin will act as a proxy between the React frontend and the NextJS API, handling file uploads, API communication, and saving generated images back to the WordPress media library.

**Problem it solves:** Currently, the "pbai-generate-btn" button in the React app only shows mock functionality. This integration will enable real AI image generation using Google's Gemini API through the existing NextJS service.

**Goal:** Enable users to generate AI-enhanced images directly from the WordPress media modal using their selected seed image and generation parameters.

## Goals

1. **Enable Real AI Generation:** Replace mock functionality with actual AI image generation
2. **Seamless WordPress Integration:** Generated images automatically saved to media library  
3. **Secure Proxy Architecture:** WordPress acts as secure proxy to NextJS API
4. **User-Friendly Experience:** Clear loading states and error handling
5. **Parameter Support:** Pass style and additional instructions to AI generation

## User Stories

1. **As a WordPress user**, I want to select an image in the media modal and click "Generate AI Photos" so that I can create an enhanced version using AI.

2. **As a WordPress user**, I want to specify a style (Professional, Creative, etc.) and additional instructions so that the AI generates images matching my preferences.

3. **As a WordPress user**, I want to see a loading spinner when generation is in progress so that I know the system is working.

4. **As a WordPress user**, I want generated images to automatically appear in my media library so that I can use them immediately in my content.

5. **As a WordPress user**, I want to receive clear error messages if generation fails so that I understand what went wrong.

## Functional Requirements

### WordPress REST API Endpoint

1. **The system must** create a new REST endpoint `/wp-json/photobooster-ai/v1/generate-image` that accepts POST requests.

2. **The system must** validate the following request parameters:
   - `attachment_id` (required): ID of the seed image attachment
   - `style` (required): Generation style (Professional, Creative, Artistic, Modern, Vintage, Minimalist)
   - `additional_instructions` (optional): Custom prompt instructions

3. **The system must** validate user permissions using the existing `permissions_uploaders_with_nonce()` method.

4. **The system must** validate the seed image attachment using existing validation methods in `class-photobooster-ai-rest.php`.

5. **The system must** retrieve the seed image file and convert it to the format expected by the NextJS API.

### NextJS API Integration

6. **The system must** make HTTP requests to the NextJS API endpoint at the configured URL.

7. **The system must** send the following data to NextJS:
   - Seed image file (as FormData)
   - Combined prompt string incorporating style and additional instructions

8. **The system must** handle NextJS API responses and extract the generated image data.

### Media Library Integration

9. **The system must** save generated images as new attachment records in the WordPress media library.

10. **The system must** generate appropriate filenames for saved images (e.g., `{original-filename}-ai-enhanced-{timestamp}.{ext}`).

11. **The system must** preserve original image metadata where applicable.

12. **The system must** return the new attachment ID and URL to the React frontend.

### React Frontend Updates

13. **The system must** update the `handleGeneratePhotos()` function to make real API calls to the WordPress endpoint.

14. **The system must** display a loading spinner during generation process.

15. **The system must** show JavaScript alerts for error conditions.

16. **The system must** update the generated photos display with the newly created image.

### Error Handling

17. **The system must** validate NextJS API availability and handle connection errors.

18. **The system must** provide specific error messages for common failure scenarios:
    - Invalid attachment ID
    - File not found
    - API connection failed  
    - Generation failed
    - File save failed

19. **The system must** log detailed error information for debugging purposes.

## Non-Goals (Out of Scope)

- Multiple image generation in a single request (numberOfPhotos parameter)
- Storing Google AI API keys in WordPress (handled by NextJS)
- Advanced rate limiting or usage tracking
- Custom NextJS API endpoint URL configuration (will be hardcoded initially)
- Image editing or post-processing features
- User interface redesign beyond loading states
- Replacing existing images (only creating new ones)

## Technical Considerations

### WordPress Side
- Extend `class-photobooster-ai-rest.php` with new endpoint
- Use WordPress HTTP API (`wp_remote_post()`) for NextJS communication
- Utilize existing attachment validation methods
- Follow WordPress media handling best practices

### React Side  
- Update `App.tsx` to make authenticated WordPress REST API calls
- Implement proper loading state management
- Use WordPress REST API nonce for authentication
- Handle file upload and response processing

### NextJS Integration
- NextJS API endpoint at `/api/generate-image` already exists and is compatible
- WordPress will send FormData with image file and prompt
- Response format: `{ success: true, image: base64Data, prompt: string }`

### Dependencies
- WordPress HTTP API
- WordPress media functions (`wp_insert_attachment()`, `wp_generate_attachment_metadata()`)
- React state management for loading states
- Existing NextJS API infrastructure

## Success Metrics

1. **Functional Success:** Users can successfully generate AI images that are saved to media library
2. **User Experience:** Loading states provide clear feedback during 3-10 second generation process  
3. **Error Handling:** All error conditions display helpful messages to users
4. **Integration Quality:** Generated images maintain proper WordPress metadata and are immediately usable
5. **Performance:** API proxy adds minimal overhead (< 1 second) to generation process

## Open Questions

1. Should the NextJS API URL be configurable through WordPress admin settings, or hardcoded initially?
2. What should be the timeout duration for NextJS API calls?
3. Should there be any file size limits for seed images beyond WordPress defaults?
4. Do we need to handle any specific image format conversions between WordPress and NextJS?

---

**Target Audience:** Junior Developer  
**Implementation Priority:** High  
**Estimated Complexity:** Medium
