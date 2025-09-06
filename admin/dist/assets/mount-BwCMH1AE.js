// PhotoBooster AI Mount Module - Temporary Manual Version
console.log('Mount module loaded, version: 1.1.0-manual');

let root = null;
let currentTarget = null;

export const MOUNT_VERSION = '1.1.0-manual';

export function mountApp(target, props = {}) {
    console.log('mountApp called with target:', target, 'props:', props);

    if (root) {
        console.log('Root already exists, returning early');
        return;
    }

    try {
        console.log('Creating overlay...');
        currentTarget = target;

        const overlay = document.createElement('div');
        overlay.className = 'pbai-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            padding: 20px;
        `;

        const modal = document.createElement('div');
        modal.className = 'pbai-modal';
        modal.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow: auto;
            position: relative;
            padding: 20px;
        `;

        const attachment = props.attachment;
        let content = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e1e5e9; padding-bottom: 10px;">
                <h2 style="margin: 0; color: #23282d; font-size: 20px;">✨ AI Photo Enhancement</h2>
                <button id="close-btn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px;" aria-label="Close">×</button>
            </div>
        `;

        if (attachment) {
            content += `
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="${attachment.url}" alt="${attachment.title || attachment.filename}" style="max-width: 100%; max-height: 300px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #23282d; font-size: 16px;">${attachment.title || attachment.filename}</h3>
                    <p style="margin: 5px 0; color: #666; font-size: 14px;">File: ${attachment.filename}</p>
                    <p style="margin: 5px 0; color: #666; font-size: 14px;">Type: ${attachment.mime}</p>
                    <p style="margin: 5px 0; color: #666; font-size: 14px;">ID: ${attachment.id}</p>
                </div>
                <div style="text-align: center;">
                    <button id="enhance-btn" style="background: #0073aa; color: white; border: none; padding: 12px 24px; border-radius: 4px; font-size: 16px; cursor: pointer; min-width: 160px; transition: background-color 0.2s;">
                        ✨ Enhance Photo
                    </button>
                </div>
            `;
        } else {
            content += `
                <div style="text-align: center; color: #d63638; padding: 40px;">
                    <p style="margin: 0; font-size: 16px;">No attachment selected</p>
                </div>
            `;
        }

        modal.innerHTML = content;
        overlay.appendChild(modal);
        target.appendChild(overlay);

        // Add functionality
        const closeBtn = modal.querySelector('#close-btn');
        const enhanceBtn = modal.querySelector('#enhance-btn');

        if (closeBtn) {
            closeBtn.onclick = closeApp;
            closeBtn.onmouseover = function () {
                this.style.backgroundColor = '#f1f1f1';
                this.style.color = '#333';
            };
            closeBtn.onmouseout = function () {
                this.style.backgroundColor = '';
                this.style.color = '#666';
            };
        }

        if (enhanceBtn) {
            enhanceBtn.onclick = function () {
                this.textContent = '🔄 Enhancing...';
                this.disabled = true;
                this.style.backgroundColor = '#ccc';
                this.style.cursor = 'not-allowed';

                setTimeout(() => {
                    alert('Enhancement complete! (This is a demo)');
                    this.textContent = '✨ Enhance Photo';
                    this.disabled = false;
                    this.style.backgroundColor = '#0073aa';
                    this.style.cursor = 'pointer';
                }, 2000);
            };

            enhanceBtn.onmouseover = function () {
                if (!this.disabled) {
                    this.style.backgroundColor = '#005a87';
                }
            };

            enhanceBtn.onmouseout = function () {
                if (!this.disabled) {
                    this.style.backgroundColor = '#0073aa';
                }
            };
        }

        function closeApp() {
            console.log('Closing app');
            unmountApp();
            if (currentTarget) {
                currentTarget.dispatchEvent(new CustomEvent('pbai:close'));
            }
        }

        // Handle ESC key and backdrop click
        function handleKeyDown(e) {
            if (e.key === 'Escape') {
                closeApp();
            }
        }

        overlay.onclick = function (e) {
            if (e.target === overlay) {
                closeApp();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        overlay._handleKeyDown = handleKeyDown;

        root = overlay;
        console.log('App mounted successfully');

    } catch (error) {
        console.error('Error in mountApp:', error);
    }
}

export function unmountApp() {
    console.log('unmountApp called');

    if (!root) return;

    try {
        if (root._handleKeyDown) {
            document.removeEventListener('keydown', root._handleKeyDown);
        }

        if (root.parentNode) {
            root.parentNode.removeChild(root);
        }

        root = null;
        currentTarget = null;
        console.log('App unmounted successfully');
    } catch (error) {
        console.error('Error in unmountApp:', error);
    }
}
