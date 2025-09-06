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

        // Method 1: Try to get from wp.media frame
        if (window.wp && window.wp.media && window.wp.media.frame) {
            try {
                var selection = window.wp.media.frame.state().get('selection');
                if (selection && selection.length) {
                    console.log('Found attachment via wp.media.frame:', selection.first());
                    return selection.first();
                }
            } catch (e) {
                console.log('wp.media.frame method failed:', e);
            }
        }

        // Method 2: Try alternative wp.media approach
        if (window.wp && window.wp.media && window.wp.media.frames) {
            try {
                for (var frameId in window.wp.media.frames) {
                    var frame = window.wp.media.frames[frameId];
                    if (frame && frame.state && frame.state().get) {
                        var selection = frame.state().get('selection');
                        if (selection && selection.length) {
                            console.log('Found attachment via wp.media.frames:', selection.first());
                            return selection.first();
                        }
                    }
                }
            } catch (e) {
                console.log('wp.media.frames method failed:', e);
            }
        }

        // Method 3: Parse from DOM - look for selected attachment ID
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
        if (!entry || !entry.css) return;
        (entry.css || []).forEach(function (css) {
            var href = getDistUrl() + css;
            if (document.querySelector('link[data-pbai-css="' + href + '"]')) return;
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.setAttribute('data-pbai-css', href);
            document.head.appendChild(link);
        });
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
        ensureStyleInjected(entry);
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

            if (mod && typeof mod.mountApp === 'function') {
                console.log('mountApp function found, calling with props:', props);
                // Listen for close event from React app
                mountNode.addEventListener('pbai:close', function () {
                    console.log('Close event received');
                    unmountAppFromModal(modalEl);
                });
                mod.mountApp(mountNode, props);
                console.log('mountApp called successfully');
            } else {
                console.error('mountApp function not found in module:', mod);
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
            if (mod && typeof mod.unmountApp === 'function') {
                mod.unmountApp();
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

    function observeMediaModal() {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                Array.prototype.forEach.call(m.addedNodes || [], function (node) {
                    if (!(node instanceof HTMLElement)) return;
                    if (node.classList && (node.classList.contains('media-modal') || node.classList.contains('attachments-browser') || node.classList.contains('media-frame'))) {
                        console.log('New container detected:', node.className);
                        var $node = $(node);
                        injectOrUpdateButton($node);
                        attachPerContainerObservers(node, $node);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function attachPerContainerObservers(node, $node) {
        var innerObs = new MutationObserver(function () {
            // Re-evaluate button presence and eligibility on selection changes
            injectOrUpdateButton($node);
        });
        innerObs.observe(node, { childList: true, subtree: true });

        // Listen for wp.media selection changes
        if (window.wp && window.wp.media && window.wp.media.frame) {
            var frame = window.wp.media.frame;
            if (frame.on) {
                frame.on('selection:single selection:toggle', function () {
                    setTimeout(function () {
                        injectOrUpdateButton($node);
                    }, 50);
                });
            }
        }

        // Cleanup on close for modal variant
        if (node.classList && node.classList.contains('media-modal')) {
            $node.on('click', '.media-modal-close, .media-modal-backdrop', function () {
                unmountAppFromModal(node);
                $node.find('[data-pbai-enhance="1"]').remove();
            });
        }

        // Listen for when media frame closes completely
        if (window.wp && window.wp.media && window.wp.media.frame) {
            var frame = window.wp.media.frame;
            if (frame.on) {
                frame.on('close', function () {
                    $node.find('[data-pbai-enhance="1"]').remove();
                    unmountAppFromModal(node);
                });
            }
        }
    }

    $(function () {
        console.log('PhotoBooster AI bootstrap initializing...');
        if (!isMediaScreen()) return;

        // Observe future containers
        observeMediaModal();

        // Initialize for any already-present containers
        $('.media-modal, .attachments-browser, .media-frame').each(function () {
            console.log('Found existing container:', this.className);
            var $m = $(this);
            injectOrUpdateButton($m);
            attachPerContainerObservers(this, $m);
        });

        // Force inject into any media-related container as fallback
        setTimeout(function () {
            $('.media-sidebar, .attachment-info, .attachment-details').each(function () {
                if ($(this).find('[data-pbai-enhance="1"]').length === 0) {
                    console.log('Fallback injection into:', this.className);
                    injectOrUpdateButton($(this).closest('.media-frame, .media-modal, body'));
                }
            });
        }, 500);
    });

})(jQuery);
