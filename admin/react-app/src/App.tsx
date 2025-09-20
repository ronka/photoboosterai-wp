import { useState, useEffect, useRef } from 'react'
import './App.css'
import { PresetSelector } from './components/preset-selector'

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
    const [selectedImagePopup, setSelectedImagePopup] = useState<GeneratedPhoto | null>(null)
    const modalRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)
    const imagePopupRef = useRef<HTMLDivElement>(null)

    const handleSelectSeedImage = () => {
        // TODO: Implement WordPress media modal integration
        console.log('Opening media modal for seed image selection')
    }

    const handleImageClick = (photo: GeneratedPhoto) => {
        setSelectedImagePopup(photo)
    }

    const handleCloseImagePopup = () => {
        setSelectedImagePopup(null)
    }

    const handleGeneratePhotos = async () => {
        if (!seedImage) {
            alert('Please select a seed image first')
            return
        }

        setIsGenerating(true)

        try {
            // Get WordPress REST API settings
            const wpSettings = (window as any).PBAIEnhance
            if (!wpSettings) {
                throw new Error('WordPress API settings not found')
            }

            // Prepare form data
            const formData = new FormData()
            formData.append('attachment_id', seedImage.id.toString())
            formData.append('style', style)
            if (additionalInstructions.trim()) {
                formData.append('additional_instructions', additionalInstructions.trim())
            }

            // Make request to WordPress REST API
            const response = await fetch(`${wpSettings.restBase}/generate-image`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': wpSettings.nonce,
                },
                body: formData,
            })

            const result = await response.json()

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Failed to generate image')
            }

            // Create new photo object with generated image data
            const newPhoto: GeneratedPhoto = {
                id: `generated-${result.attachment_id}`,
                url: result.url,
                timestamp: new Date()
            }

            // Add to generated photos list
            setGeneratedPhotos(prev => [newPhoto, ...prev])

            alert('Image generated successfully!')

        } catch (error) {
            console.error('Image generation error:', error)
            alert(`Failed to generate image: ${error instanceof Error ? error.message : 'Unknown error'}`)
        } finally {
            setIsGenerating(false)
        }
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
                if (selectedImagePopup) {
                    handleCloseImagePopup()
                } else {
                    handleClose()
                }
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
                                <PresetSelector />
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
                                        <div
                                            key={photo.id}
                                            className="pbai-generated-photo"
                                            onClick={() => handleImageClick(photo)}
                                        >
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

            {/* Image Popup Modal */}
            {selectedImagePopup && (
                <div className="pbai-image-popup-overlay" onClick={handleCloseImagePopup}>
                    <div
                        className="pbai-image-popup-modal"
                        ref={imagePopupRef}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            className="pbai-image-popup-close"
                            onClick={handleCloseImagePopup}
                            aria-label="Close image"
                        >
                            ×
                        </button>
                        <img
                            src={selectedImagePopup.url}
                            alt="Generated photo"
                            className="pbai-image-popup-image"
                        />
                        <div className="pbai-image-popup-info">
                            <p>Generated on {selectedImagePopup.timestamp.toLocaleString()}</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}

export default App
