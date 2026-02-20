/**
 * PhotoBooster AI Media Enhancement Bootstrap
 * 
 * This file handles the integration of AI enhancement functionality into WordPress media modals.
 * It provides button injection, asset loading, and React app mounting for image enhancement.
 * 
 * @author PhotoBooster AI
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    // =============================================================================
    // CONSTANTS
    // =============================================================================

    /** @constant {string} Root element ID for React app mounting */
    const MOUNT_ROOT_ID = 'pbai-enhance-root';

    /** @constant {string} Data attribute for AI enhance buttons */
    const ENHANCE_BUTTON_ATTR = 'data-pbai-enhance';

    /** @constant {string} Data attribute for injected CSS links */
    const CSS_DATA_ATTR = 'data-pbai-css';

    /** @constant {string} Button text for AI enhance functionality */
    const BUTTON_TEXT = '✨ AI Enhance';

    /** @constant {string} Custom event name for closing React app */
    const CLOSE_EVENT_NAME = 'pbai:close';

    /** @constant {number} Delay for DOM operations to complete (ms) */
    const DOM_OPERATION_DELAY = 100;

    /** @constant {number} Z-index for React app overlay */
    const OVERLAY_Z_INDEX = '100000';

    /** @constant {Array<string>} Supported image MIME types */
    const ELIGIBLE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp'
    ];

    /** @constant {string} Primary manifest entry key */
    const MANIFEST_ENTRY_KEY = 'src/mount.tsx';

    /** @constant {Object} Fallback asset file mappings */
    const FALLBACK_ASSET_MAP = {
        'src/mount.tsx': {
            file: 'assets/mount-BwCMH1AE.js',
            css: ['assets/mount-16kd5VZX.css']
        },
        'mount.tsx': {
            file: 'assets/mount-BwCMH1AE.js',
            css: ['assets/mount-16kd5VZX.css']
        }
    };

    /** @constant {Object} Asset file candidate patterns */
    const ASSET_CANDIDATES = {
        js: [
            'assets/mount-BwCMH1AE.js',
            'assets/mount.js',
            'mount.js'
        ],
        css: [
            'assets/mount-16kd5VZX.css',
            'assets/mount.css',
            'mount.css'
        ]
    };

    /** @constant {Object} DOM selectors for various elements */
    const SELECTORS = {
        // Modal containers
        visibleModals: '.media-modal:visible, .media-frame:visible',
        existingContainers: '.media-modal:visible, .attachments-browser:visible, .media-frame:visible',

        // Button targets
        attachmentInfo: '.attachment-info:visible',
        mediaSidebar: '.media-sidebar:visible',
        attachmentDetails: '.attachment-details:visible',

        // Attachment selection
        selectedAttachment: '.attachment.selected, .attachment.details, .attachment-details',
        elementsWithDataId: '[data-id]',

        // Attachment metadata
        filename: '.filename, .media-sidebar .filename strong, .attachment-details .filename',
        title: '.title, .media-sidebar .title, .attachment-details .title, input[name="title"]',
        image: '.details-image img, .attachment-preview img, .thumbnail img, img',
        fullSizeLinks: 'a[href*=".jpg"], a[href*=".jpeg"], a[href*=".png"], a[href*=".webp"]',
        editLinks: 'a[href*="post.php?post="], a[href*="edit.php?post="]',
        altText: 'input[name="alt"], [name="alt"]',

        // Event targets
        attachmentPreview: '.attachment-preview',

        // Button selector
        enhanceButton: '[' + ENHANCE_BUTTON_ATTR + '="1"]'
    };

    /** @constant {Object} Regular expressions for various matching operations */
    const REGEX_PATTERNS = {
        numericId: /^\d+$/,
        postId: /post=(\d+)/,
        jpegExtension: /\.jpe?g$/i,
        pngExtension: /\.png$/i,
        webpExtension: /\.webp$/i,
        attachmentIdPrefix: /^attachment-/
    };

    /** @constant {Object} User-facing error messages */
    const ERROR_MESSAGES = {
        appNotBuilt: 'AI Enhance app is not built yet. Check console for details.',
        mountFunctionMissing: 'React app loaded but mountApp function missing.',
        invalidImageSelected: 'Please select a valid image file (JPEG, PNG, or WebP).',
        loadFailed: 'Failed to load AI Enhance app. Check console for details.'
    };

    // =============================================================================
    // UTILITY FUNCTIONS
    // =============================================================================

    /**
     * Checks if current screen allows media modal functionality
     * @returns {boolean} Always returns true for maximum compatibility
     */
    function isMediaScreen() {
        return true;
    }

    /**
     * Gets the distribution URL for assets from global configuration
     * @returns {string} Distribution URL or empty string if not available
     */
    function getDistUrl() {
        return (window.photobooster_ai_enhance && window.photobooster_ai_enhance.distUrl) || '';
    }

    /**
     * Checks if selected attachment is an eligible image type
     * @param {Object|null} selectedAttachment - The attachment object to check
     * @returns {boolean} True if attachment is an eligible image
     */
    function eligibleImageSelected(selectedAttachment) {
        if (!selectedAttachment) {
            return false;
        }

        const mime = selectedAttachment.get ?
            selectedAttachment.get('mime') :
            selectedAttachment.mime || selectedAttachment.subtype || '';

        return ELIGIBLE_MIME_TYPES.indexOf(mime) !== -1;
    }

    /**
     * Detects MIME type from filename or URL
     * @param {string} testUrl - Filename or URL to analyze
     * @returns {string} Detected MIME type or empty string
     */
    function detectMimeType(testUrl) {
        if (REGEX_PATTERNS.jpegExtension.test(testUrl)) return 'image/jpeg';
        if (REGEX_PATTERNS.pngExtension.test(testUrl)) return 'image/png';
        if (REGEX_PATTERNS.webpExtension.test(testUrl)) return 'image/webp';
        return '';
    }

    /**
     * Safely extracts text content from jQuery element
     * @param {jQuery} $element - jQuery element to extract text from
     * @returns {string} Trimmed text content or empty string
     */
    function extractText($element) {
        return $element.length ? $element.text().trim() : '';
    }

    /**
     * Safely extracts value from input element or text from other elements
     * @param {jQuery} $element - jQuery element to extract value/text from
     * @returns {string} Element value/text or empty string
     */
    function extractValue($element) {
        if (!$element.length) return '';
        return $element.is('input') ? ($element.val() || '') : extractText($element);
    }

    // =============================================================================
    // ASSET MANAGEMENT
    // =============================================================================

    /**
     * Retrieves manifest entry from global configuration or falls back to detection
     * @param {string} key - The manifest entry key to look up
     * @returns {Object|null} Manifest entry object or null if not found
     */
    function getManifestEntry(key) {
        const map = (window.photobooster_ai_enhance && window.photobooster_ai_enhance.manifest) || {};
        const entry = map[key];

        if (entry) {
            return entry;
        }

        return getFallbackEntry(key);
    }

    /**
     * Gets fallback entry when manifest lookup fails
     * @param {string} key - The entry key to find fallback for
     * @returns {Object|null} Fallback entry object or null if not found
     */
    function getFallbackEntry(key) {
        const entry = FALLBACK_ASSET_MAP[key];
        if (entry) {
            return entry;
        }

        if (key.includes('mount')) {
            const mountJs = findAssetFile('mount', 'js');
            const mountCss = findAssetFile('mount', 'css');

            if (mountJs) {
                return {
                    file: mountJs,
                    css: mountCss ? [mountCss] : []
                };
            }
        }

        return null;
    }

    /**
     * Finds asset file using predefined candidate patterns
     * @param {string} name - Asset name to search for
     * @param {string} type - Asset type ('js' or 'css')
     * @returns {string|null} First matching candidate or null
     */
    function findAssetFile(name, type) {
        const candidates = ASSET_CANDIDATES[type];
        return candidates ? candidates[0] : null;
    }

    /**
     * Injects CSS stylesheets from manifest entry and its imports
     * @param {Object} entry - Manifest entry containing CSS files
     */
    function ensureStyleInjected(entry) {
        const manifest = (window.photobooster_ai_enhance && window.photobooster_ai_enhance.manifest) || {};

        /**
         * Injects CSS files from a single manifest entry
         * @param {Object} entryToCheck - Entry object to check for CSS
         */
        function injectCssFromEntry(entryToCheck) {
            if (!entryToCheck || !entryToCheck.css) return;

            entryToCheck.css.forEach(function (css) {
                const href = getDistUrl() + css;
                const existingLink = document.querySelector(`link[${CSS_DATA_ATTR}="${href}"]`);

                if (existingLink) return;

                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.setAttribute(CSS_DATA_ATTR, href);
                document.head.appendChild(link);
            });
        }

        // Inject CSS from the main entry
        injectCssFromEntry(entry);

        // Also inject CSS from imported chunks
        if (entry && entry.imports) {
            entry.imports.forEach(function (importKey) {
                const importedEntry = manifest[importKey];
                if (importedEntry) {
                    injectCssFromEntry(importedEntry);
                }
            });
        }

        // Manual fallback: inject App CSS directly from all manifest entries
        Object.keys(manifest).forEach(function (key) {
            const manifestEntry = manifest[key];
            if (manifestEntry.css && manifestEntry.css.length > 0) {
                injectCssFromEntry(manifestEntry);
            }
        });
    }

    // =============================================================================
    // DOM MANIPULATION
    // =============================================================================

    /**
     * Finds and returns target element for button injection in modal
     * @param {jQuery} $modal - Modal jQuery object
     * @returns {jQuery} Target element or empty jQuery object
     */
    function findButtonTarget($modal) {
        let $target = $modal.find(SELECTORS.attachmentInfo).first();
        if (!$target.length) {
            $target = $modal.find(SELECTORS.mediaSidebar).first();
        }
        if (!$target.length) {
            $target = $modal.find(SELECTORS.attachmentDetails).first();
        }
        return $target;
    }

    /**
     * Extracts attachment ID from WordPress global settings
     * @param {jQuery} $modal - Modal jQuery object (for potential future use)
     * @returns {string|null} Attachment ID or null if not found
     */
    function getAttachmentIdFromGlobals($modal) {
        if (window._wpMediaGridSettings &&
            window._wpMediaGridSettings.queryVars &&
            window._wpMediaGridSettings.queryVars.item) {
            try {
                return window._wpMediaGridSettings.queryVars.item;
            } catch (e) {
                // Silently handle error
                return null;
            }
        }
        return null;
    }

    /**
     * Searches DOM for attachment ID using various strategies
     * @param {jQuery} $modal - Modal jQuery object to search within
     * @returns {string|null} Found attachment ID or null
     */
    function findAttachmentIdInDOM($modal) {
        // Strategy 1: Look for selected attachment elements
        const $selected = $modal.find(SELECTORS.selectedAttachment);
        if ($selected.length) {
            const attachmentId = $selected.attr('data-id') || $selected.data('id');
            if (attachmentId) {
                return attachmentId;
            }
        }

        // Strategy 2: Look for any element with data-id
        const $withDataId = $modal.find(SELECTORS.elementsWithDataId);
        if ($withDataId.length) {
            const attachmentId = $withDataId.first().attr('data-id');
            if (attachmentId && REGEX_PATTERNS.numericId.test(attachmentId)) {
                return attachmentId;
            }
        }

        // Strategy 3: Look in attachment details for edit links
        const $details = $modal.find(SELECTORS.attachmentDetails + ', ' + SELECTORS.mediaSidebar);
        if ($details.length) {
            const $link = $details.find(SELECTORS.editLinks).first();
            if ($link.length) {
                const href = $link.attr('href');
                const match = href.match(REGEX_PATTERNS.postId);
                if (match) {
                    return match[1];
                }
            }
        }

        // Strategy 4: Look for attachment ID in element IDs
        const allElements = $modal.find('*');
        for (let i = 0; i < allElements.length; i++) {
            const el = allElements[i];
            const id = el.getAttribute('id');
            if (id && REGEX_PATTERNS.attachmentIdPrefix.test(id)) {
                const attachmentId = id.replace('attachment-', '');
                if (REGEX_PATTERNS.numericId.test(attachmentId)) {
                    return attachmentId;
                }
            }
        }

        return null;
    }

    /**
     * Extracts attachment metadata from DOM elements
     * @param {jQuery} $modal - Modal jQuery object to search within
     * @returns {Object} Object containing extracted metadata
     */
    function extractAttachmentMetadata($modal) {
        const $filename = $modal.find(SELECTORS.filename).first();
        const filename = extractText($filename);

        const $title = $modal.find(SELECTORS.title).first();
        const title = extractValue($title) || filename;

        const $img = $modal.find(SELECTORS.image).first();
        const url = $img.attr('src') || $img.attr('data-src') || '';

        const $fullLink = $modal.find(SELECTORS.fullSizeLinks).first();
        const fullUrl = $fullLink.attr('href') || url;

        const $alt = $modal.find(SELECTORS.altText).first();
        const alt = extractValue($alt);

        const mime = detectMimeType(filename || url);

        return {
            filename,
            title,
            url,
            fullUrl,
            alt,
            mime
        };
    }

    /**
     * Gets the currently selected attachment from the media modal
     * @param {jQuery} $modal - Modal jQuery object
     * @returns {Object|null} Attachment object or null if not found
     */
    function getSelectedAttachment($modal) {
        // Try getting from WordPress globals first
        const globalAttachmentId = getAttachmentIdFromGlobals($modal);
        if (globalAttachmentId) {
            try {
                return createAttachmentFromDOM($modal, globalAttachmentId);
            } catch (e) {
                // Continue to DOM parsing
            }
        }

        // Parse from DOM if modal exists
        if (!$modal || !$modal.length) {
            return null;
        }

        try {
            const attachmentId = findAttachmentIdInDOM($modal);
            if (attachmentId) {
                return createAttachmentFromDOM($modal, attachmentId);
            }
        } catch (e) {
            // Silently handle DOM parsing errors
        }

        return null;
    }

    /**
     * Creates attachment object from DOM data
     * @param {jQuery} $modal - Modal jQuery object
     * @param {string} attachmentId - ID of the attachment
     * @returns {Object|null} Created attachment object or null on error
     */
    function createAttachmentFromDOM($modal, attachmentId) {
        try {
            const metadata = extractAttachmentMetadata($modal);

            const attachment = {
                id: parseInt(attachmentId, 10),
                title: metadata.title,
                filename: metadata.filename,
                url: metadata.fullUrl || metadata.url,
                mime: metadata.mime,
                alt: metadata.alt,
                sizes: metadata.url ? {
                    full: { url: metadata.fullUrl || metadata.url },
                    large: { url: metadata.url },
                    medium: { url: metadata.url },
                    thumbnail: { url: metadata.url }
                } : {},
                // Add get method for compatibility with WordPress media objects
                get: function (key) {
                    return this[key];
                }
            };

            return attachment;
        } catch (e) {
            return null;
        }
    }

    /**
     * Creates or returns existing mount node for React app
     * @param {Element} modalEl - Modal DOM element
     * @returns {Element} Mount node element
     */
    function createMountNode(modalEl) {
        const existing = modalEl.querySelector('#' + MOUNT_ROOT_ID);
        if (existing) return existing;

        const mount = document.createElement('div');
        mount.id = MOUNT_ROOT_ID;
        mount.setAttribute('role', 'dialog');
        mount.setAttribute('aria-modal', 'true');
        mount.style.position = 'fixed';
        mount.style.inset = '0';
        mount.style.zIndex = OVERLAY_Z_INDEX;
        modalEl.appendChild(mount);

        return mount;
    }

    /**
     * Removes mount node from modal
     * @param {Element} modalEl - Modal DOM element
     */
    function removeMountNode(modalEl) {
        const node = modalEl.querySelector('#' + MOUNT_ROOT_ID);
        if (node && node.parentNode) {
            node.parentNode.removeChild(node);
        }
    }

    // =============================================================================
    // REACT APP INTEGRATION
    // =============================================================================

    /**
     * Prepares props object for React app from attachment data
     * @param {Object|null} attachment - Attachment object
     * @returns {Object} Props object for React app
     */
    function prepareAppProps(attachment) {
        const props = {};
        if (attachment) {
            props.attachment = {
                id: attachment.get ? attachment.get('id') : attachment.id,
                title: attachment.get ? attachment.get('title') : attachment.title,
                url: attachment.get ? attachment.get('url') : attachment.url,
                mime: attachment.get ? attachment.get('mime') : attachment.mime,
                filename: attachment.get ? attachment.get('filename') : attachment.filename,
                sizes: attachment.get ? attachment.get('sizes') : attachment.sizes
            };
        }
        return props;
    }

    /**
     * Mounts React app into modal with given attachment
     * @param {Element} modalEl - Modal DOM element
     * @param {Object|null} attachment - Attachment object to pass to app
     */
    function mountAppIntoModal(modalEl, attachment) {
        const entry = getManifestEntry(MANIFEST_ENTRY_KEY);

        if (!entry || !entry.file) {
            window.alert(ERROR_MESSAGES.appNotBuilt);
            return;
        }

        ensureStyleInjected(entry);

        const jsUrl = getDistUrl() + entry.file;
        const mountNode = createMountNode(modalEl);
        const props = prepareAppProps(attachment);

        import(jsUrl)
            .then(function (mod) {
                const mountAppFn = mod.mountApp || window.PBAIMountApp;

                if (typeof mountAppFn === 'function') {
                    // Listen for close event from React app
                    mountNode.addEventListener(CLOSE_EVENT_NAME, function () {
                        unmountAppFromModal(modalEl);
                    });

                    mountAppFn(mountNode, props);
                } else {
                    window.alert(ERROR_MESSAGES.mountFunctionMissing);
                }
            })
            .catch(function (error) {
                window.alert(ERROR_MESSAGES.loadFailed);
            });
    }

    /**
     * Unmounts React app from modal
     * @param {Element} modalEl - Modal DOM element
     */
    function unmountAppFromModal(modalEl) {
        const entry = getManifestEntry(MANIFEST_ENTRY_KEY);
        if (!entry || !entry.file) {
            removeMountNode(modalEl);
            return;
        }

        const jsUrl = getDistUrl() + entry.file;

        import(jsUrl)
            .then(function (mod) {
                const unmountAppFn = mod.unmountApp || window.PBAIUnmountApp;
                if (typeof unmountAppFn === 'function') {
                    unmountAppFn();
                }
                removeMountNode(modalEl);
            })
            .catch(function () {
                removeMountNode(modalEl);
            });
    }

    // =============================================================================
    // BUTTON MANAGEMENT
    // =============================================================================

    /**
     * Creates AI enhance button with click handler
     * @param {jQuery} $modal - Modal jQuery object
     * @returns {jQuery} Created button element
     */
    function createEnhanceButton($modal) {
        const $btn = $('<button>', {
            'text': BUTTON_TEXT,
            [ENHANCE_BUTTON_ATTR]: '1'
        });

        $btn.on('click', function () {
            const attachment = getSelectedAttachment($modal);
            if (!eligibleImageSelected(attachment)) {
                alert(ERROR_MESSAGES.invalidImageSelected);
                return;
            }
            const modalEl = $modal.get(0);
            mountAppIntoModal(modalEl, attachment);
        });

        return $btn;
    }

    /**
     * Injects or updates AI enhance button in media modal
     * @param {jQuery} $modal - Modal jQuery object
     */
    function injectOrUpdateButton($modal) {
        if (!$modal || !$modal.length) return;

        const $target = findButtonTarget($modal);
        if (!$target.length) return;

        const $btn = $target.find(SELECTORS.enhanceButton);
        const selectedAttachment = getSelectedAttachment($modal);
        const isEligible = eligibleImageSelected(selectedAttachment);

        // Create button if it doesn't exist and attachment is eligible
        if (!$btn.length && isEligible) {
            const $newBtn = createEnhanceButton($modal);
            $target.prepend($newBtn);
        }

        // Show/hide button based on eligibility and selection state
        const $existingBtn = $target.find(SELECTORS.enhanceButton);
        if ($existingBtn.length) {
            if (isEligible && selectedAttachment) {
                $existingBtn.show();
            } else {
                $existingBtn.hide();
            }
        } else if (!isEligible || !selectedAttachment) {
            // Remove any existing buttons if selection becomes ineligible
            $target.find(SELECTORS.enhanceButton).remove();
        }
    }

    // =============================================================================
    // EVENT HANDLING & INITIALIZATION
    // =============================================================================

    /**
     * Sets up event listeners for media modal triggers
     */
    function listenForMediaModalTriggers() {
        $(document).on('click', SELECTORS.attachmentPreview, function (e) {
            // Wait for modal to be created and populated
            setTimeout(function () {
                const $modal = $(SELECTORS.visibleModals).last();
                if ($modal.length) {
                    injectOrUpdateButton($modal);
                }
            }, DOM_OPERATION_DELAY);
        });
    }

    /**
     * Initializes existing visible containers
     */
    function initializeExistingContainers() {
        setTimeout(function () {
            $(SELECTORS.existingContainers).each(function () {
                const $modal = $(this);
                injectOrUpdateButton($modal);
            });
        }, DOM_OPERATION_DELAY);
    }

    // =============================================================================
    // MAIN INITIALIZATION
    // =============================================================================

    /**
     * Main initialization function
     * Sets up all event listeners and initializes existing modals
     */
    $(function () {
        if (!isMediaScreen()) return;

        // Set up click listeners for media modal triggers
        listenForMediaModalTriggers();

        // Initialize for any already-present containers
        initializeExistingContainers();
    });

})(jQuery);