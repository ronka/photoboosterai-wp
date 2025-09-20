<?php
// Test the new API endpoint helper functions
require_once 'photobooster-ai.php';

echo "Testing API endpoint helper functions:\n\n";
echo "Base URL: " . PHOTOBOOSTER_AI_BASE_URL . "\n";
echo "API Base Path: " . PHOTOBOOSTER_AI_API_BASE_PATH . "\n\n";

echo "Helper Functions:\n";
echo "Generate Image Endpoint: " . photobooster_ai_get_generate_image_endpoint() . "\n";
echo "Credits Endpoint: " . photobooster_ai_get_credits_endpoint() . "\n";
echo "Base API Endpoint (no specific path): " . photobooster_ai_get_api_endpoint() . "\n";
echo "Custom endpoint example: " . photobooster_ai_get_api_endpoint('webhook') . "\n";
