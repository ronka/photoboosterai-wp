import { StrictMode } from 'react'
import { createRoot, Root } from 'react-dom/client'
import App from './App'
import './index.css'

let root: Root | null = null
let currentTarget: HTMLElement | null = null

// Version identifier for debugging
export const MOUNT_VERSION = '1.1.0'
console.log('Mount module loaded, version:', MOUNT_VERSION)

function mountApp(target: HTMLElement, props?: Record<string, unknown>) {
    console.log('mountApp called with target:', target, 'props:', props)

    if (root) {
        console.log('Root already exists, returning early')
        return
    }

    try {
        console.log('Creating React root...')
        currentTarget = target
        root = createRoot(target)
        console.log('React root created successfully:', root)

        const onClose = () => {
            console.log('onClose called')
            unmountApp()
            // Dispatch custom event for cleanup
            if (currentTarget) {
                currentTarget.dispatchEvent(new CustomEvent('pbai:close'))
            }
        }

        console.log('Rendering React app...')
        root.render(
            <StrictMode>
                <App {...(props || {})} onClose={onClose} />
            </StrictMode>
        )
        console.log('React app rendered successfully')
    } catch (error) {
        console.error('Error in mountApp:', error)
    }
}

function unmountApp() {
    if (!root) return
    root.unmount()
    root = null
    currentTarget = null
    document.querySelector('#pbai-product-enhance-modal')?.remove()
    document.querySelector('#pbai-enhance-root')?.remove()
}

// Ensure functions are available globally and not tree-shaken
if (typeof window !== 'undefined') {
    // @ts-ignore
    window.PBAIMountApp = mountApp
    // @ts-ignore
    window.PBAIUnmountApp = unmountApp
    console.log('Mount functions exposed globally')
}

// Export for ES module usage - these should not be tree-shaken due to side effects
export { mountApp, unmountApp }
