(function ($) {
    'use strict';

    function isMediaScreen() {
        // Allow on any admin screen where media modal can appear (post editor, etc.)
        return true;
    }

    function getManifestEntry(key) {
        var map = (window.PBAIEnhance && window.PBAIEnhance.manifest) || {};
        console.log('Manifest map:', map);
        console.log('Looking for key:', key);

        var entry = map[key];
        if (entry) {
            console.log('Found manifest entry:', entry);
            return entry;
        }

        console.log('No manifest entry found, trying fallback...');
        // Fallback: try to find the built files directly
        return getFallbackEntry(key);
    }

    function getFallbackEntry(key) {
        console.log('Getting fallback entry for:', key);

        // Map of expected entries to likely filenames
        var fallbackMap = {
            'src/mount.tsx': {
                file: 'assets/mount-BwCMH1AE.js',
                css: ['assets/mount-16kd5VZX.css']
            },
            'mount.tsx': {
                file: 'assets/mount-BwCMH1AE.js',
                css: ['assets/mount-16kd5VZX.css']
            }
        };

        var entry = fallbackMap[key];
        if (entry) {
            console.log('Found fallback entry:', entry);
            return entry;
        }

        // Try to auto-detect from available files
        if (key.includes('mount')) {
            // Look for mount-related files
            var mountJs = findAssetFile('mount', 'js');
            var mountCss = findAssetFile('mount', 'css');

            if (mountJs) {
                var detectedEntry = {
                    file: mountJs,
                    css: mountCss ? [mountCss] : []
                };
                console.log('Auto-detected entry:', detectedEntry);
                return detectedEntry;
            }
        }

        console.log('No fallback found');
        return null;
    }

    function findAssetFile(name, type) {
        // This is a simple heuristic - in production you'd want to make this more robust
        var distUrl = getDistUrl();
        var baseUrl = distUrl.replace(/\/$/, '');

        if (type === 'js') {
            // Try common patterns for the mount JS file
            var candidates = [
                'assets/mount-BwCMH1AE.js',
                'assets/mount.js',
                'mount.js'
            ];
        } else if (type === 'css') {
            var candidates = [
                'assets/mount-16kd5VZX.css',
                'assets/mount.css',
                'mount.css'
            ];
        }

        // For now, return the first candidate (in production, you'd check if file exists)
        return candidates[0];
    }

    function getDistUrl() {
        return (window.PBAIEnhance && window.PBAIEnhance.distUrl) || '';
    }

    function eligibleImageSelected(selectedAttachment) {
        if (!selectedAttachment) return false;

        var mime = selectedAttachment.get ? selectedAttachment.get('mime') :
            selectedAttachment.mime || selectedAttachment.subtype || '';

        // Check for eligible image MIME types
        var eligibleMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        return eligibleMimes.indexOf(mime) !== -1;
    }

    function getSelectedAttachment($modal) {
        console.log('getSelectedAttachment called');

        // TODO: fix this
        if (window._wpMediaGridSettings && window._wpMediaGridSettings.queryVars && window._wpMediaGridSettings.queryVars.item) {
            try {
                return createAttachmentFromDOM($modal, window._wpMediaGridSettings.queryVars.item)
            } catch (e) {
                console.log('_wpMediaGridSettings.queryVars.item method failed:', e);
            }
        }

        // Method 2: Parse from DOM - look for selected attachment ID
        if ($modal && $modal.length) {
            try {
                console.log('Searching DOM for attachment...', $modal.get(0));

                // Look for attachment ID in various places
                var $selected = $modal.find('.attachment.selected, .attachment.details, .attachment-details');
                console.log('Found selected elements:', $selected.length);

                if ($selected.length) {
                    var attachmentId = $selected.attr('data-id') || $selected.data('id');
                    if (attachmentId) {
                        console.log('Found attachment ID from DOM:', attachmentId);
                        return createAttachmentFromDOM($modal, attachmentId);
                    }
                }

                // Look for data-id on any element
                var $withDataId = $modal.find('[data-id]');
                console.log('Elements with data-id:', $withDataId.length);
                if ($withDataId.length) {
                    var attachmentId = $withDataId.first().attr('data-id');
                    if (attachmentId && /^\d+$/.test(attachmentId)) {
                        console.log('Found attachment ID from data-id:', attachmentId);
                        return createAttachmentFromDOM($modal, attachmentId);
                    }
                }

                // Look in attachment details for edit link
                var $details = $modal.find('.attachment-details, .media-sidebar');
                if ($details.length) {
                    var $link = $details.find('a[href*="post.php?post="], a[href*="edit.php?post="]').first();
                    if ($link.length) {
                        var href = $link.attr('href');
                        var match = href.match(/post=(\d+)/);
                        if (match) {
                            var attachmentId = match[1];
                            console.log('Found attachment ID from details link:', attachmentId);
                            return createAttachmentFromDOM($modal, attachmentId);
                        }
                    }
                }

                // Look for attachment ID in URL fragments or other attributes
                var allElements = $modal.find('*');
                for (var i = 0; i < allElements.length; i++) {
                    var el = allElements[i];
                    var id = el.getAttribute('id');
                    if (id && id.indexOf('attachment-') === 0) {
                        var attachmentId = id.replace('attachment-', '');
                        if (/^\d+$/.test(attachmentId)) {
                            console.log('Found attachment ID from element ID:', attachmentId);
                            return createAttachmentFromDOM($modal, attachmentId);
                        }
                    }
                }

            } catch (e) {
                console.log('DOM parsing method failed:', e);
            }
        }

        console.log('No attachment found');
        return null;
    }

    function createAttachmentFromDOM($modal, attachmentId) {
        try {
            console.log('Creating attachment from DOM for ID:', attachmentId);

            // Extract what we can from the DOM
            var $filename = $modal.find('.filename, .media-sidebar .filename strong, .attachment-details .filename').first();
            var filename = $filename.text().trim() || '';
            console.log('Found filename:', filename);

            var $title = $modal.find('.title, .media-sidebar .title, .attachment-details .title, input[name="title"]').first();
            var title = '';
            if ($title.is('input')) {
                title = $title.val() || '';
            } else {
                title = $title.text().trim() || '';
            }
            title = title || filename;
            console.log('Found title:', title);

            // Try to get the image src from multiple possible locations
            var $img = $modal.find('.details-image img, .attachment-preview img, .thumbnail img, img').first();
            var url = $img.attr('src') || $img.attr('data-src') || '';
            console.log('Found image URL:', url);

            // Look for full-size URL
            var $fullLink = $modal.find('a[href*=".jpg"], a[href*=".jpeg"], a[href*=".png"], a[href*=".webp"]').first();
            var fullUrl = $fullLink.attr('href') || url;

            // Guess MIME type from filename or URL
            var mime = '';
            var testUrl = filename || url;
            if (testUrl.match(/\.jpe?g$/i)) mime = 'image/jpeg';
            else if (testUrl.match(/\.png$/i)) mime = 'image/png';
            else if (testUrl.match(/\.webp$/i)) mime = 'image/webp';
            console.log('Detected MIME type:', mime);

            // Try to extract additional metadata
            var $alt = $modal.find('input[name="alt"], [name="alt"]').first();
            var alt = $alt.is('input') ? $alt.val() : $alt.text();

            var attachment = {
                id: parseInt(attachmentId, 10),
                title: title,
                filename: filename,
                url: fullUrl || url,
                mime: mime,
                alt: alt || '',
                sizes: url ? {
                    full: { url: fullUrl || url },
                    large: { url: url },
                    medium: { url: url },
                    thumbnail: { url: url }
                } : {},
                // Add get method for compatibility
                get: function (key) {
                    return this[key];
                }
            };

            console.log('Created attachment from DOM:', attachment);
            return attachment;
        } catch (e) {
            console.log('Error creating attachment from DOM:', e);
            return null;
        }
    }

    function ensureStyleInjected(entry) {
        var manifest = (window.PBAIEnhance && window.PBAIEnhance.manifest) || {};

        // Function to inject CSS from a single entry
        function injectCssFromEntry(entryToCheck) {
            if (!entryToCheck || !entryToCheck.css) return;
            (entryToCheck.css || []).forEach(function (css) {
                var href = getDistUrl() + css;
                if (document.querySelector('link[data-pbai-css="' + href + '"]')) return;
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.setAttribute('data-pbai-css', href);
                document.head.appendChild(link);
                console.log('Injected CSS:', href);
            });
        }

        // Inject CSS from the main entry
        injectCssFromEntry(entry);

        // Also inject CSS from imported chunks
        if (entry && entry.imports) {
            entry.imports.forEach(function (importKey) {
                var importedEntry = manifest[importKey];
                if (importedEntry) {
                    console.log('Checking imported entry for CSS:', importKey, importedEntry);
                    injectCssFromEntry(importedEntry);
                }
            });
        }
    }

    function createMountNode(modalEl) {
        var id = 'pbai-enhance-root';
        var existing = modalEl.querySelector('#' + id);
        if (existing) return existing;
        var mount = document.createElement('div');
        mount.id = id;
        mount.setAttribute('role', 'dialog');
        mount.setAttribute('aria-modal', 'true');
        mount.style.position = 'fixed';
        mount.style.inset = '0';
        mount.style.zIndex = '100000';
        modalEl.appendChild(mount);
        return mount;
    }

    function removeMountNode(modalEl) {
        var node = modalEl.querySelector('#pbai-enhance-root');
        if (node && node.parentNode) node.parentNode.removeChild(node);
    }

    function mountAppIntoModal(modalEl, attachment) {
        console.log('mountAppIntoModal called with attachment:', attachment);

        var entry = getManifestEntry('src/mount.tsx');
        console.log('Retrieved entry:', entry);

        if (!entry || !entry.file) {
            console.error('No manifest entry found for src/mount.tsx');
            console.log('Available PBAIEnhance data:', window.PBAIEnhance);
            window.alert('AI Enhance app is not built yet. Check console for details.');
            return;
        }
        console.log('Entry before CSS injection:', entry);
        ensureStyleInjected(entry);

        // Manual fallback: inject App CSS directly
        var manifest = (window.PBAIEnhance && window.PBAIEnhance.manifest) || {};
        console.log('Full manifest:', manifest);

        // Look for App CSS in the manifest
        Object.keys(manifest).forEach(function (key) {
            var manifestEntry = manifest[key];
            if (manifestEntry.css && manifestEntry.css.length > 0) {
                console.log('Found CSS in manifest entry:', key, manifestEntry.css);
                manifestEntry.css.forEach(function (css) {
                    var href = getDistUrl() + css;
                    if (!document.querySelector('link[data-pbai-css="' + href + '"]')) {
                        var link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = href;
                        link.setAttribute('data-pbai-css', href);
                        document.head.appendChild(link);
                        console.log('Manually injected CSS:', href);
                    }
                });
            }
        });
        var jsUrl = getDistUrl() + entry.file;
        var mountNode = createMountNode(modalEl);

        // Prepare attachment props
        var props = {};
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

        console.log('Importing module from:', jsUrl);
        import(jsUrl).then(function (mod) {
            console.log('Module loaded successfully:', mod);
            console.log('Module keys:', Object.keys(mod));
            console.log('MOUNT_VERSION:', mod.MOUNT_VERSION);
            console.log('mountApp type:', typeof mod.mountApp);

            // Try to get mountApp from module exports first, then fall back to global
            var mountAppFn = mod.mountApp || window.PBAIMountApp;
            console.log('mountApp function found:', typeof mountAppFn);

            if (typeof mountAppFn === 'function') {
                console.log('mountApp function found, calling with props:', props);
                // Listen for close event from React app
                mountNode.addEventListener('pbai:close', function () {
                    console.log('Close event received');
                    unmountAppFromModal(modalEl);
                });
                mountAppFn(mountNode, props);
                console.log('mountApp called successfully');
            } else {
                console.error('mountApp function not found in module or global scope:', mod);
                console.log('Available global functions:', {
                    PBAIMountApp: typeof window.PBAIMountApp,
                    PBAIUnmountApp: typeof window.PBAIUnmountApp
                });
                window.alert('React app loaded but mountApp function missing.');
            }
        }).catch(function (error) {
            console.error('Failed to load AI Enhance app:', error);
            window.alert('Failed to load AI Enhance app. Check console for details.');
        });
    }

    function unmountAppFromModal(modalEl) {
        var entry = getManifestEntry('src/mount.tsx');
        if (!entry || !entry.file) return;
        var jsUrl = getDistUrl() + entry.file;
        import(jsUrl).then(function (mod) {
            // Try to get unmountApp from module exports first, then fall back to global
            var unmountAppFn = mod.unmountApp || window.PBAIUnmountApp;
            if (typeof unmountAppFn === 'function') {
                unmountAppFn();
            }
            removeMountNode(modalEl);
        }).catch(function () {
            removeMountNode(modalEl);
        });
    }

    function injectOrUpdateButton($modal) {
        console.log('injectOrUpdateButton called with:', $modal.length, $modal.get(0));
        if (!$modal || !$modal.length) return;

        // Try multiple selectors to find a target
        var $target = $modal.find('.attachment-info:visible').first();
        if (!$target.length) {
            $target = $modal.find('.media-sidebar:visible').first();
        }
        if (!$target.length) {
            $target = $modal.find('.attachment-details:visible').first();
        }
        if (!$target.length) {
            // Fallback: inject into any visible child div
            $target = $modal.find('div:visible').first();
        }

        console.log('Target found:', $target.length, $target.get(0));
        if (!$target.length) return;

        var selector = '[data-pbai-enhance="1"]';
        var $btn = $target.find(selector);
        var selectedAttachment = getSelectedAttachment($modal);
        var isEligible = eligibleImageSelected(selectedAttachment);

        console.log('Selected attachment:', selectedAttachment, 'Eligible:', isEligible);

        if (!$btn.length && isEligible) {
            console.log('Creating button...');
            $btn = $('<button>', {
                'text': '✨ AI Enhance',
                'data-pbai-enhance': '1'
            });
            $btn.on('click', function () {
                console.log('Button clicked!');
                var attachment = getSelectedAttachment($modal);
                if (!eligibleImageSelected(attachment)) {
                    alert('Please select a valid image file (JPEG, PNG, or WebP).');
                    return;
                }
                var modalEl = $modal.get(0);
                mountAppIntoModal(modalEl, attachment);
            });
            $target.prepend($btn);
            console.log('Button injected into:', $target.get(0));
        }

        // Show/hide button based on eligibility and selection state
        if ($btn.length) {
            if (isEligible && selectedAttachment) {
                $btn.show();
            } else {
                $btn.hide();
            }
        } else if (!isEligible || !selectedAttachment) {
            // Remove any existing buttons if selection becomes ineligible
            $target.find(selector).remove();
        }
    }

    function listenForMediaModalTriggers() {
        console.log('Setting up click listeners for media modal triggers...');

        // Listen for clicks on attachment previews and set post thumbnail button
        $(document).on('click', '.attachment-preview', function (e) {
            console.log('Media modal trigger clicked:', this.className || this.id);

            // Wait a moment for the modal to be created and populated
            setTimeout(function () {
                // Find the media modal that was opened
                var $modal = $('.media-modal:visible, .media-frame:visible').last();
                console.log('setTimeout: Modal injected:', $modal.length);
                if ($modal.length) {
                    console.log('Found opened modal:', $modal.get(0));
                    injectOrUpdateButton($modal);
                }
            }, 500);
        });
    }


    $(function () {
        console.log('PhotoBooster AI bootstrap initializing...');
        if (!isMediaScreen()) return;

        // Set up click listeners for media modal triggers
        listenForMediaModalTriggers();

        // Initialize for any already-present containers (in case modal is already open)
        $('.media-modal:visible, .attachments-browser:visible, .media-frame:visible').each(function () {
            console.log('Found existing visible container:', this.className);
            var $m = $(this);
            injectOrUpdateButton($m);
        });
    });

})(jQuery);
