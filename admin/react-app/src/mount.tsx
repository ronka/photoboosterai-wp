import { StrictMode } from 'react'
import { createRoot, Root } from 'react-dom/client'
import App from './App'
import './index.css'

let root: Root | null = null

export function mountApp(target: HTMLElement, props?: Record<string, unknown>) {
    if (root) return
    root = createRoot(target)
    root.render(
        <StrictMode>
            <App {...(props || {})} />
        </StrictMode>
    )
}

export function unmountApp() {
    if (!root) return
    root.unmount()
    root = null
}
