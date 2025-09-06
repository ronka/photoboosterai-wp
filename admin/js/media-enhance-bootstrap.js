(function ($) {
    'use strict';

    function isMediaScreen() {
        // Allow on any admin screen where media modal can appear (post editor, etc.)
        return true;
    }

    function getManifestEntry(key) {
        var map = (window.PBAIEnhance && window.PBAIEnhance.manifest) || {};
        return map[key] || null;
    }

    function getDistUrl() {
        return (window.PBAIEnhance && window.PBAIEnhance.distUrl) || '';
    }

    function eligibleImageSelected($container) {
        // Try to read selected attachment mime by file name extension as a basic heuristic
        var $filename = $container.find('.attachment-info .filename, .attachment-details .filename, .media-sidebar .filename strong, .media-sidebar .filename').first();
        var name = $filename.text() || '';
        name = name.toLowerCase();
        if (/(\.jpe?g|\.png|\.webp)$/.test(name)) return true;
        // If filename unavailable, conservatively show button and let app validate later
        return !$filename.length;
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

    function mountAppIntoModal(modalEl) {
        var entry = getManifestEntry('src/mount.tsx');
        if (!entry || !entry.file) {
            window.alert('AI Enhance app is not built yet.');
            return;
        }
        ensureStyleInjected(entry);
        var jsUrl = getDistUrl() + entry.file;
        var mountNode = createMountNode(modalEl);
        import(jsUrl).then(function (mod) {
            if (mod && typeof mod.mountApp === 'function') {
                mod.mountApp(mountNode, {});
            }
        }).catch(function () {
            window.alert('Failed to load AI Enhance app.');
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
        if (!$btn.length) {
            console.log('Creating button...');
            $btn = $('<button>', {
                'text': '✨ AI Enhance',
                'class': 'button button-primary',
                'data-pbai-enhance': '1',
                'css': {
                    marginBottom: '8px',
                    backgroundColor: '#0073aa',
                    color: 'white'
                }
            });
            $btn.on('click', function () {
                console.log('Button clicked!');
                var modalEl = $modal.get(0);
                mountAppIntoModal(modalEl);
            });
            $target.prepend($btn);
            console.log('Button injected into:', $target.get(0));
        }
        // Always show; eligibility will be validated inside the app
        $btn.show();
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
        // Cleanup on close for modal variant
        if (node.classList && node.classList.contains('media-modal')) {
            $node.on('click', '.media-modal-close, .media-modal-backdrop', function () {
                unmountAppFromModal(node);
                $node.find('[data-pbai-enhance="1"]').remove();
            });
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
