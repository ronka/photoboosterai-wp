import { StrictMode } from 'react'
import { createRoot, Root } from 'react-dom/client'
import App from './App'
import './index.css'

let root: Root | null = null
let currentTarget: HTMLElement | null = null

// Version identifier for debugging
export const MOUNT_VERSION = '1.1.0'
console.log('Mount module loaded, version:', MOUNT_VERSION)

export function mountApp(target: HTMLElement, props?: Record<string, unknown>) {
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

export function unmountApp() {
    if (!root) return
    root.unmount()
    root = null
    currentTarget = null
}
