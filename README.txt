=== eCommerce Product Photo Booster AI ===
Contributors: ronka
Donate link: https://photoboosterai.com/
Tags: ai, image-generation, product-photos, e-commerce, photography
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create studio-quality product photos from one image—clean backgrounds, lifestyle scenes, and marketing-ready variants.

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
4. Enter your API key (obtain from [photoboosterai.com](https://photoboosterai.com))
5. Configure your preferences and start enhancing images

== Frequently Asked Questions ==

= How do I get an API key? =

Visit [photoboosterai.com](https://photoboosterai.com) to sign up and obtain your API key.

= What image formats are supported? =

The plugin works with JPEG, PNG, and WebP formats. Recommended minimum resolution is 512x512 pixels.

= How many images can I process? =

Processing limits depend on your API plan. Check your account at [photoboosterai.com](https://photoboosterai.com) for details.

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

This plugin connects to the PhotoBooster AI API to generate and enhance product images. The API is required for the core functionality of this plugin.

**What the service does:** The PhotoBooster AI API receives your product image and returns AI-generated enhanced versions (e.g., clean backgrounds, lifestyle scenes, marketing variants).

**What data is sent and when:**

* Your product image (as a URL or binary data) is sent to the API each time you click the "AI Enhance" button.
* Your API key is sent with every request to authenticate your account.
* No other personal or site data is transmitted.

**Service provider:** PhotoBooster AI (https://photoboosterai.com)

* Terms of Service: https://photoboosterai.com/terms
* Privacy Policy: https://photoboosterai.com/privacy

== Source Code ==

The admin UI of this plugin is built with React and compiled using Vite. The compiled (minified) files are located in `admin/dist/assets/`. The full, human-readable source code is publicly available at:

https://github.com/ronka/photoboosterai-wp

**Building from source:**

1. Clone the repository: `git clone https://github.com/ronka/photoboosterai-wp.git`
2. Navigate to the React app directory: `cd admin/react-app`
3. Install dependencies: `npm install`
4. Build for production: `npm run build`

The build output will be placed in `admin/dist/`.