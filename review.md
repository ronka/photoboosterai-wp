It's time to move forward with the plugin review "ronka"!

Your plugin is not yet ready to be approved, you are receiving this email because the volunteers have manually checked it and have found some issues in the code / functionality of your plugin.

Please check this email thoroughly, address any issues listed, test your changes, and upload a corrected version of your code if all is well.

List of issues found


## No publicly documented resource for your generated/compressed content

In reviewing your plugin, we cannot find a non-compiled version of your javascript and/or css related source code.

In order to comply with our guidelines of human-readable code, we require you to include the source code and / or a link to the source code, this is true for your own code and for developer libraries you’ve included in your plugin. If you include a link, this may be in your source code, however we require you to also have it in your readme.

https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable

We strongly feel that one of the strengths of open source is the ability to review, observe, and adapt code. By maintaining a public directory of freely available code, we encourage and welcome future developers to engage with WordPress and push it forward.

That said, with the advent of larger and larger plugins using more complex libraries, people are making good use of build tools (such as composer or npm) to generate their distributed production code. In order to balance the need to keep plugin sizes smaller while still encouraging open source development, we require plugins to make the source code to any compressed files available to the public in an easy to find location, by documenting it in the readme.

For example, if you’ve made a Gutenberg plugin and used npm and webpack to compress and minify it, you must either include the source code within the published plugin or provide access to a public maintained source that can be reviewed, studied, and yes, forked.

We strongly recommend you include directions on the use of any build tools to encourage future developers.

From your plugin:
admin/dist/assets/App-BQB4NFeD.js:17  ...*/var ih;function Yy(){if(ih)return ve;ih=1;var i=Symbol.for("react.transitional.element"),c=Symbol.for("react.portal"),r=Symbol.for("react.fragment"),o=Symbol.for("react.strict_mode"),f=Symbol.for("r... 
admin/dist/assets/App-BQB4NFeD.js:1  ...function By(i,c){for(var r=0;r<c.length;r++){const o=c[r];if(typeof o!="string"&&!Array.isArray(o)){for(const f in o)if(f!=="default"&&!(f in i)){const d=Object.getOwnPropertyDescriptor(o,f);d&&Object... 
admin/dist/assets/mount-DGxQw8SG.js:1  ...import{c as s,j as r,r as u,A as a}from"./App-BQB4NFeD.js";let o=null,e=null;const p="1.1.0";console.log("Mount module loaded, version:",p);function i(n,l){if(console.log("mountApp called with target:... 
admin/dist/assets/app-D6S4vjQv.js:1  ...import{c as n,j as i,r as l,A as u}from"./App-BQB4NFeD.js";(function(){const r=document.createElement("link").relList;if(r&&r.supports&&r.supports("modulepreload"))return;for(const e of document.query... 



## Use wp_enqueue commands

Your plugin is not correctly including JS and/or CSS. You should be using the built in functions for this:

When including JavaScript code you can use:
wp_register_script() and wp_enqueue_script() to add JavaScript code from a file.
wp_add_inline_script() to add inline JavaScript code to previous declared scripts.

When including CSS you can use:
wp_register_style() and wp_enqueue_style() to add CSS from a file.
wp_add_inline_style() to add inline CSS to previously declared CSS.

Note that as of WordPress 6.3, you can easily pass attributes like defer or async: https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/

Also, as of WordPress 5.7, you can pass other attributes by using this functions and filters: https://make.wordpress.org/core/2021/02/23/introducing-script-attributes-related-functions-in-wordpress-5-7/

If you're trying to enqueue on the admin pages you'll want to use the admin enqueues.

https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_styles/

Example(s) from your plugin:
admin/class-photobooster-ai-admin.php:540 <script type="text/javascript">
admin/partials/photobooster-ai-settings-display.php:69 <style>



## Undocumented use of a 3rd Party / external service

Plugins are permitted to require the use of third party/external services as long as they are clearly documented.

When your plugin reach out to external services, you must disclose it. This is true even if you are the one providing that service.

You are required to document it in a clear and plain language, so users are aware of: what data is sent, why, where and under which conditions.

To do this, you must update your readme file to clearly explain that your plugin relies on third party/external services, and include at least the following information for each third party/external service that this plugin uses:
What the service is and what it is used for.
What data is sent and when.
Provide links to the service's terms of service and privacy policy.
Remember, this is for your own legal protection. Use of services must be upfront and well documented. This allows users to ensure that any legal issues with data transmissions are covered.

Example:
== External services ==

This plugin connects to an API to obtain weather information, it's needed to show the weather information and forecasts in the included widget.

It sends the user's location every time the widget is loaded (If the location isn't available and/or the user hasn't given their consent, it displays a configurable default location).
This service is provided by "PRT Weather INC": terms of use, privacy policy.


Example(s) from your plugin:
# Domain(s) mentioned in the readme file. Links to service terms and/or privacy policy not found.
ecommerce-product-photo-booster-ai.php:67 $base_url = PHOTOBOOSTER_AI_BASE_URL;
# ↳ Detected: https://photoboosterai.com



## Data Must be Sanitized, Escaped, and Validated

When you include POST/GET/REQUEST/FILE calls in your plugin, it's important to sanitize, validate, and escape them. The goal here is to prevent a user from accidentally sending trash data through the system, as well as protecting them from potential security issues.

SANITIZE: Data that is input (either by a user or automatically) must be sanitized as soon as possible. This lessens the possibility of XSS vulnerabilities and MITM attacks where posted data is subverted.

VALIDATE: All data should be validated, no matter what. Even when you sanitize, remember that you don’t want someone putting in ‘dog’ when the only valid values are numbers.

ESCAPE: Data that is output must be escaped properly when it is echo'd, so it can't hijack admin screens. There are many esc_*() functions you can use to make sure you don't show people the wrong data.

To help you with this, WordPress comes with a number of sanitization and escaping functions. You can read about those here:

https://developer.wordpress.org/apis/security/sanitizing/
https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. If you’re sanitizing email, use sanitize_email() , if you’re outputting HTML, use wp_kses_post() , and so on.

An easy mantra here is this:

Sanitize early
Escape Late
Always Validate

Clean everything, check everything, escape everything, and never trust the users to always have input sane data. After all, users come from all walks of life.

Example(s) from your plugin:
admin/class-photobooster-ai-admin.php:678 if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {



Note: When checking a nonce using wp_verify_nonce you will need to sanitize the input using wp_unslash AND sanitize_text_field , this is because this function is pluggable, and extenders should not trust its input values.

Example:
if ( ! isset( $_POST['ecomprph_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['ecomprph_nonce'] ) ) , 'ecomprph_nonce' ) )

Example(s) from your plugin:
admin/class-photobooster-ai-admin.php:678 if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {


✔️ You can check this using Plugin Check.


## Generic function/class/define/namespace/option names

All plugins must have unique function names, namespaces, defines, class and option names. This prevents your plugin from conflicting with other plugins or themes. We need you to update your plugin to use more unique and distinct names.

A good way to do this is with a prefix. For example, if your plugin is called "eCommerce Product Photo Booster AI" then you could use names like these:
function ecomprph_save_post(){ ... }
class ECOMPRPH_Admin { ... }
update_option( 'ecomprph_options', $options );
register_setting( 'ecomprph_settings', 'ecomprph_user_id', ... );
define( 'ECOMPRPH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
global $ecomprph_options;
add_action('wp_ajax_ecomprph_save_data', ... );
namespace ronka\ecommerceproductphotoboosterai;

Disclaimer: These are just examples that may have been self-generated from your plugin name, we trust you can find better options. If you have a good alternative, please use it instead, this is just an example.

The prefix should be at least four (4) characters long (don't try to use two- or three-letter prefixes anymore). We host almost 100,000 plugins on WordPress.org alone. There are tens of thousands more outside our servers. Believe us, you're likely to encounter conflicts.

You also need to avoid the use of __ (double underscores), wp_ , or _ (single underscore) as a prefix. Those are reserved for WordPress itself. You can use them inside your classes, but not as stand-alone function.

Please remember, if you're using _n() or __() for translation, that's fine. We're only talking about functions you've created for your plugin, not the core functions from WordPress. In fact, those core features are why you need to not use those prefixes in your own plugin! You don't want to break WordPress for your users.

Related to this, using if (!function_exists('NAME')) { around all your functions and classes sounds like a great idea until you realize the fatal flaw. If something else has a function with the same name and their code loads first, your plugin will break. Using if-exists should be reserved for shared libraries only.

Remember: Good prefix names are unique and distinct to your plugin. This will help you and the next person in debugging, as well as prevent conflicts.

Analysis result:
# This plugin is using the prefix "photobooster_ai" for 19 element(s).

# Looks like there is an element not using common prefixes.
admin/class-photobooster-ai-admin.php:260 wp_localize_script($handle, self::LOCALIZE_OBJECT_NAME, $localized_data);
# ↳ Detected name: PBAIEnhance


👉 Continue with the review process.

Read this email thoroughly.

Please, take the time to fully understand the issues we've raised. Review the examples provided, read the relevant documentation, and research as needed. Our goal is for you to gain a clear understanding of the problems so you can address them effectively and avoid similar issues when maintaining your plugin in the future.
Note that there may be false positives - we are humans and make mistakes, we apologize if there is anything we have gotten wrong. If you have doubts you can ask us for clarification, when asking us please be clear, concise, direct and include an example.

📋 Complete your checklist.

✔️ I fixed all the issues in my plugin based on the feedback I received and my own review, as I know that the Plugins Team may not share all cases of the same issue. I am familiar with tools such as Plugin Check, PHPCS + WPCS, and similar utilities to help me identify problems in my code.
✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true.
⚠️ Do not skip this step. Testing is essential to make sure your fixes actually work and that you haven’t introduced new issues.

✔️ I acknowledge that this review will be rejected if I overlook the issues or fail to test my code.
✔️ I went to "Add your plugin" and uploaded the updated version. I can continue updating the code there throughout the review process — the team will always check the latest version.
✔️ I replied to this email. I was concise and shared any clarifications or important context that the team needed to know.
I didn't list all the changes, as the team will review the entire plugin again and that is not necessary at all.

ℹ️ To make this process as quick as possible and to avoid burden on the volunteers devoting their time to review this plugin's code, we ask you to thoroughly check all shared issues and fix them before sending the code back to us. I know we already asked you to do so, and it is because we are really trying to make it very clear.

While we try to make our reviews as exhaustive as possible we, like you, are humans and may have missed things. We appreciate your patience and understanding.
