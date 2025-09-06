import { useState, useEffect, useRef } from 'react'
import './App.css'

interface Attachment {
    id: number
    title: string
    url: string
    mime: string
    filename: string
    sizes?: {
        thumbnail?: { url: string }
        medium?: { url: string }
        large?: { url: string }
        full?: { url: string }
    }
}

interface AppProps {
    attachment?: Attachment
    onClose?: () => void
}

function App({ attachment, onClose }: AppProps) {
    const [isEnhancing, setIsEnhancing] = useState(false)
    const modalRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)

    const handleEnhance = () => {
        if (!attachment) return
        setIsEnhancing(true)
        // TODO: Implement actual enhancement logic
        setTimeout(() => {
            setIsEnhancing(false)
            alert('Enhancement complete! (This is a placeholder)')
        }, 2000)
    }

    const handleClose = () => {
        if (onClose) {
            onClose()
        }
    }

    // Handle ESC key and focus management
    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                handleClose()
            }

            // Basic focus trap
            if (event.key === 'Tab' && modalRef.current) {
                const focusableElements = modalRef.current.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                )
                const firstElement = focusableElements[0] as HTMLElement
                const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement

                if (event.shiftKey) {
                    if (document.activeElement === firstElement) {
                        event.preventDefault()
                        lastElement.focus()
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        event.preventDefault()
                        firstElement.focus()
                    }
                }
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        // Focus the close button when modal opens
        if (closeButtonRef.current) {
            closeButtonRef.current.focus()
        }

        return () => {
            document.removeEventListener('keydown', handleKeyDown)
        }
    }, [onClose])

    // Prevent background scroll
    useEffect(() => {
        document.body.style.overflow = 'hidden'
        return () => {
            document.body.style.overflow = ''
        }
    }, [])

    const getPreviewUrl = () => {
        if (!attachment) return ''

        // Try to get the best available preview
        if (attachment.sizes) {
            return attachment.sizes.medium?.url ||
                attachment.sizes.large?.url ||
                attachment.sizes.full?.url ||
                attachment.url
        }
        return attachment.url
    }

    return (
        <div className="pbai-overlay">
            <div className="pbai-modal" ref={modalRef}>
                <header className="pbai-header">
                    <h2>✨ AI Photo Enhancement</h2>
                    <button
                        ref={closeButtonRef}
                        className="pbai-close"
                        onClick={handleClose}
                        aria-label="Close"
                    >
                        ×
                    </button>
                </header>

                <div className="pbai-content">
                    {attachment ? (
                        <>
                            <div className="pbai-preview">
                                <img
                                    src={getPreviewUrl()}
                                    alt={attachment.title || attachment.filename}
                                    className="pbai-image"
                                />
                            </div>

                            <div className="pbai-info">
                                <h3>{attachment.title || attachment.filename}</h3>
                                <p>File: {attachment.filename}</p>
                                <p>Type: {attachment.mime}</p>
                                <p>ID: {attachment.id}</p>
                            </div>

                            <div className="pbai-actions">
                                <button
                                    className="pbai-enhance-btn"
                                    onClick={handleEnhance}
                                    disabled={isEnhancing}
                                >
                                    {isEnhancing ? '🔄 Enhancing...' : '✨ Enhance Photo'}
                                </button>
                            </div>
                        </>
                    ) : (
                        <div className="pbai-error">
                            <p>No attachment selected</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}

export default App
