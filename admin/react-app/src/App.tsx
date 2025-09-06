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

interface GeneratedPhoto {
    id: string
    url: string
    timestamp: Date
}

interface AppProps {
    attachment?: Attachment
    onClose?: () => void
}

function App({ attachment, onClose }: AppProps) {
    const [isGenerating, setIsGenerating] = useState(false)
    const [seedImage, setSeedImage] = useState<Attachment | null>(attachment || null)
    const [numberOfPhotos, setNumberOfPhotos] = useState('4')
    const [style, setStyle] = useState('Professional')
    const [additionalInstructions, setAdditionalInstructions] = useState('')
    const [generatedPhotos, setGeneratedPhotos] = useState<GeneratedPhoto[]>([])
    const modalRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)

    const handleSelectSeedImage = () => {
        // TODO: Implement WordPress media modal integration
        console.log('Opening media modal for seed image selection')
    }

    const handleGeneratePhotos = () => {
        if (!seedImage) {
            alert('Please select a seed image first')
            return
        }

        setIsGenerating(true)
        // TODO: Implement actual AI photo generation logic
        setTimeout(() => {
            // Mock generated photos
            const newPhotos: GeneratedPhoto[] = Array.from({ length: parseInt(numberOfPhotos) }, (_, i) => ({
                id: `generated-${Date.now()}-${i}`,
                url: seedImage.url, // Using seed image as placeholder
                timestamp: new Date()
            }))
            setGeneratedPhotos(prev => [...newPhotos, ...prev])
            setIsGenerating(false)
        }, 3000)
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

    const getSeedImageUrl = () => {
        if (!seedImage) return ''

        // Try to get the best available preview
        if (seedImage.sizes) {
            return seedImage.sizes.medium?.url ||
                seedImage.sizes.large?.url ||
                seedImage.sizes.full?.url ||
                seedImage.url
        }
        return seedImage.url
    }

    return (
        <div className="pbai-overlay">
            <div className="pbai-modal" ref={modalRef}>
                <header className="pbai-header">
                    <h2>✨ AI Photo Generator</h2>
                    <button
                        ref={closeButtonRef}
                        className="pbai-close"
                        onClick={handleClose}
                        aria-label="Close"
                    >
                        ×
                    </button>
                </header>

                <div className="pbai-main-content">
                    {/* Left Panel - Configuration */}
                    <div className="pbai-left-panel">
                        {/* Seed Image Section */}
                        <div className="pbai-seed-section">
                            <h3>Seed Image</h3>
                            <div className="pbai-seed-image-container">
                                {seedImage ? (
                                    <div className="pbai-seed-image-wrapper">
                                        <img
                                            src={getSeedImageUrl()}
                                            alt={seedImage.title || seedImage.filename}
                                            className="pbai-seed-image"
                                        />
                                        <p className="pbai-seed-label">Current seed image</p>
                                    </div>
                                ) : (
                                    <div className="pbai-no-seed-image">
                                        <div className="pbai-placeholder-image">
                                            📷
                                        </div>
                                        <p className="pbai-seed-label">No seed image selected</p>
                                    </div>
                                )}
                                <button
                                    className="pbai-select-seed-btn"
                                    onClick={handleSelectSeedImage}
                                >
                                    Select Seed Image
                                </button>
                            </div>
                        </div>

                        {/* Configuration Options */}
                        <div className="pbai-config-section">
                            <div className="pbai-form-group">
                                <label htmlFor="numberOfPhotos">Number of Photos:</label>
                                <select
                                    id="numberOfPhotos"
                                    value={numberOfPhotos}
                                    onChange={(e) => setNumberOfPhotos(e.target.value)}
                                    className="pbai-select"
                                >
                                    <option value="1">1 Photo</option>
                                    <option value="2">2 Photos</option>
                                    <option value="3">3 Photos</option>
                                    <option value="4">4 Photos</option>
                                    <option value="5">5 Photos</option>
                                    <option value="6">6 Photos</option>
                                    <option value="8">8 Photos</option>
                                    <option value="10">10 Photos</option>
                                </select>
                            </div>

                            <div className="pbai-form-group">
                                <label htmlFor="style">Style:</label>
                                <select
                                    id="style"
                                    value={style}
                                    onChange={(e) => setStyle(e.target.value)}
                                    className="pbai-select"
                                >
                                    <option value="Professional">Professional</option>
                                    <option value="Creative">Creative</option>
                                    <option value="Artistic">Artistic</option>
                                    <option value="Modern">Modern</option>
                                    <option value="Vintage">Vintage</option>
                                    <option value="Minimalist">Minimalist</option>
                                </select>
                            </div>

                            <div className="pbai-form-group">
                                <label htmlFor="instructions">Additional Instructions (optional):</label>
                                <textarea
                                    id="instructions"
                                    value={additionalInstructions}
                                    onChange={(e) => setAdditionalInstructions(e.target.value)}
                                    className="pbai-textarea"
                                    rows={4}
                                    placeholder="Describe any specific requirements for the photos..."
                                />
                            </div>
                        </div>

                        {/* Generate Button */}
                        <div className="pbai-actions">
                            <button
                                className="pbai-generate-btn"
                                onClick={handleGeneratePhotos}
                                disabled={isGenerating || !seedImage}
                            >
                                {isGenerating ? '🔄 Generating...' : 'Generate AI Photos'}
                            </button>
                        </div>
                    </div>

                    {/* Right Panel - Generated Photos */}
                    <div className="pbai-right-panel">
                        <h3>Previously Generated Photos</h3>
                        <div className="pbai-generated-photos">
                            {generatedPhotos.length > 0 ? (
                                <div className="pbai-photos-grid">
                                    {generatedPhotos.map((photo) => (
                                        <div key={photo.id} className="pbai-generated-photo">
                                            <img
                                                src={photo.url}
                                                alt="Generated photo"
                                                className="pbai-generated-image"
                                            />
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="pbai-no-photos">
                                    <div className="pbai-no-photos-placeholder">
                                        <p>No photos generated yet</p>
                                        <p className="pbai-no-photos-subtitle">Generated photos will appear here</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default App
