=== eCommerce Product Photo Booster AI ===
Contributors: photobooster-ai
Donate link: https://photobooster-ai.vercel.app/
Tags: ai, image-generation, product-photos, e-commerce, photography, marketing
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate studio-quality images from a single photo—no expensive gear, no photo shoots. Upload your product, and within seconds, get polished photos with clean backgrounds, lifestyle mockups, and marketing-ready variations.

== Description ==

**eCommerce Product Photo Booster AI** revolutionizes product photography for eCommerce businesses. Using advanced AI technology, this plugin transforms ordinary product photos into professional, studio-quality images that boost conversions and enhance your online store's visual appeal.

### Key Features

* **AI-Powered Image Generation**: Transform single product photos into multiple professional variations
* **Clean Backgrounds**: Automatically remove and replace backgrounds with clean, professional options
* **Lifestyle Mockups**: Generate realistic lifestyle and context shots for better product presentation
* **Marketing-Ready Variations**: Create multiple angles, lighting conditions, and styling options
* **Bulk Processing**: Enhance multiple product images simultaneously
* **Secure API Integration**: Encrypted API key storage with secure communication
* **WordPress Integration**: Seamless integration with your existing WordPress media library

### How It Works

1. Upload your product photo to the WordPress media library
2. Select from various AI enhancement presets
3. Customize with additional instructions if needed
4. Generate professional images in seconds
5. Use the enhanced images directly in your eCommerce store

### Use Cases

* **Product Photography**: Transform amateur product shots into professional catalog images
* **Marketing Materials**: Generate multiple variations for A/B testing and campaigns
* **Social Media**: Create consistent, high-quality images for social media marketing
* **Website Optimization**: Improve product pages with better visuals for higher conversions

== Installation ==

1. Upload the `photobooster-ai` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings > PhotoBooster AI Settings
4. Enter your API key (obtain from [photobooster-ai.vercel.app](https://photobooster-ai.vercel.app))
5. Configure your preferences and start enhancing images

== Frequently Asked Questions ==

= How do I get an API key? =

Visit [photobooster-ai.vercel.app](https://photobooster-ai.vercel.app) to sign up and obtain your API key.

= What image formats are supported? =

The plugin works with JPEG, PNG, and WebP formats. Recommended minimum resolution is 512x512 pixels.

= How many images can I process? =

Processing limits depend on your API plan. Check your account at [photobooster-ai.vercel.app](https://photobooster-ai.vercel.app) for details.

= Can I customize the AI generation? =

Yes! You can provide additional instructions when processing images to guide the AI towards your desired outcome.

= Is my data secure? =

Absolutely. All API keys are encrypted and stored securely. Images are processed via secure HTTPS connections.

== Screenshots ==

1. **Settings Page** - Configure your API key and preferences
2. **Media Library Integration** - Process images directly from your media library
3. **AI Enhancement Results** - Before and after comparison of enhanced images
4. **Preset Options** - Choose from various enhancement styles

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered image enhancement
* Multiple preset options
* Secure API integration
* WordPress media library integration

== Upgrade Notice ==

= 1.0.0 =
Initial release with full AI image enhancement capabilities. Upgrade from any previous version to access all features.

== External Services ==

This plugin connects to the PhotoBooster AI service to generate enhanced product images and to check API credit balances.

**Service**: PhotoBooster AI (https://photobooster-ai.vercel.app)

**What data is sent and when**:

* When a user clicks "AI Enhance": the product image URL and the user's API key are sent to the PhotoBooster AI API to generate enhanced images.
* When a user clicks "Check Credits": the user's API key is sent to the PhotoBooster AI API to retrieve the remaining credit balance.
* No data is sent automatically in the background; all API calls require explicit user action.

**Terms of Service**: https://photobooster-ai.vercel.app/terms
**Privacy Policy**: https://photobooster-ai.vercel.app/privacy

== Source Code ==

The admin UI is built with React 19, TypeScript, Vite, and Tailwind CSS. The source lives in the `admin/react-app/` directory inside the plugin folder.

To rebuild the compiled assets:

1. `cd admin/react-app`
2. `npm install`
3. `npm run build`

The build output is written to `admin/dist/`.