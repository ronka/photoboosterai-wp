import { StrictMode } from 'react'
import { createRoot, Root } from 'react-dom/client'
import App from './App'
import './index.css'

let root: Root | null = null
let currentTarget: HTMLElement | null = null

export function mountApp(target: HTMLElement, props?: Record<string, unknown>) {
    if (root) return

    currentTarget = target
    root = createRoot(target)

    const onClose = () => {
        unmountApp()
        // Dispatch custom event for cleanup
        if (currentTarget) {
            currentTarget.dispatchEvent(new CustomEvent('pbai:close'))
        }
    }

    root.render(
        <StrictMode>
            <App {...(props || {})} onClose={onClose} />
        </StrictMode>
    )
}

export function unmountApp() {
    if (!root) return
    root.unmount()
    root = null
    currentTarget = null
}
