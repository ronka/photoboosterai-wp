import { useState, useEffect, useRef } from 'react'
import './App.css'
import { PresetSelector } from './components/preset-selector'
import { useCredits } from './hooks/useCredits'
import { CreditsDisplay } from './components/CreditsDisplay'

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

interface CreditsData {
    credits: number
    lastResetDate?: string
    userId?: string
}

interface AppProps {
    attachment?: Attachment
    onClose?: () => void
}

function App({ attachment, onClose }: AppProps) {
    const [isGenerating, setIsGenerating] = useState(false)
    const [seedImage, setSeedImage] = useState<Attachment | null>(attachment || null)
    const [selectedPreset, setSelectedPreset] = useState<string>('')
    const [additionalInstructions, setAdditionalInstructions] = useState('')
    const [generatedPhotos, setGeneratedPhotos] = useState<GeneratedPhoto[]>([])
    const [selectedImagePopup, setSelectedImagePopup] = useState<GeneratedPhoto | null>(null)
    const [referenceImage, setReferenceImage] = useState<File | null>(null)
    const [referenceImagePreview, setReferenceImagePreview] = useState<string | null>(null)
    const [isDraggingOver, setIsDraggingOver] = useState(false)
    const [referenceImageError, setReferenceImageError] = useState<string | null>(null)
    const { credits, creditsLoading, creditsError } = useCredits()
    const modalRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)
    const imagePopupRef = useRef<HTMLDivElement>(null)
    const referenceInputRef = useRef<HTMLInputElement>(null)

    const handleImageClick = (photo: GeneratedPhoto) => {
        setSelectedImagePopup(photo)
    }

    const handleCloseImagePopup = () => {
        setSelectedImagePopup(null)
    }

    const handleReferenceImageChange = (file: File) => {
        setReferenceImageError(null)
        const validTypes = ['image/jpeg', 'image/png', 'image/webp']
        if (!validTypes.includes(file.type)) {
            setReferenceImageError('Please upload a JPEG, PNG, or WebP image.')
            return
        }
        const maxSize = 10 * 1024 * 1024 // 10MB
        if (file.size > maxSize) {
            setReferenceImageError('Image must be 10MB or smaller.')
            return
        }
        if (referenceImagePreview) {
            URL.revokeObjectURL(referenceImagePreview)
        }
        setReferenceImage(file)
        setReferenceImagePreview(URL.createObjectURL(file))
    }

    const handleRemoveReferenceImage = () => {
        if (referenceImagePreview) {
            URL.revokeObjectURL(referenceImagePreview)
        }
        setReferenceImage(null)
        setReferenceImagePreview(null)
        setReferenceImageError(null)
        if (referenceInputRef.current) {
            referenceInputRef.current.value = ''
        }
    }

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault()
        setIsDraggingOver(true)
    }

    const handleDragLeave = () => {
        setIsDraggingOver(false)
    }

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault()
        setIsDraggingOver(false)
        const file = e.dataTransfer.files[0]
        if (file) {
            handleReferenceImageChange(file)
        }
    }

    const handleGeneratePhotos = async () => {
        if (!seedImage) {
            alert('Please select a seed image first')
            return
        }

        setIsGenerating(true)

        try {
            // Get WordPress REST API settings
            const wpSettings = (window as any).photobooster_ai_enhance
            if (!wpSettings) {
                throw new Error('WordPress API settings not found')
            }

            // Prepare form data
            const formData = new FormData()
            formData.append('attachment_id', seedImage.id.toString())
            formData.append('preset_id', selectedPreset)
            if (additionalInstructions.trim()) {
                formData.append('additional_instructions', additionalInstructions.trim())
            }
            if (referenceImage) {
                formData.append('reference_image', referenceImage)
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

            alert('Image generated successfully! Refresh the page to see the new photo in media library')

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

    // Cleanup reference image object URL on unmount
    useEffect(() => {
        return () => {
            if (referenceImagePreview) {
                URL.revokeObjectURL(referenceImagePreview)
            }
        }
    }, [referenceImagePreview])

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
                    <div className="flex gap-4">
                        <h2>✨ AI Photo Generator</h2>
                        <CreditsDisplay
                            credits={credits}
                            creditsLoading={creditsLoading}
                            creditsError={creditsError}
                        />
                    </div>

                    <div className="pbai-header-right">
                        <button
                            ref={closeButtonRef}
                            className="pbai-close"
                            onClick={handleClose}
                            aria-label="Close"
                        >
                            ×
                        </button>
                    </div>
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
                            </div>
                        </div>

                        {/* Reference Image Upload Section */}
                        <div className="pbai-reference-section">
                            <h3>Reference Image <span className="pbai-optional-label">(Optional)</span></h3>
                            {referenceImagePreview ? (
                                <div className="pbai-reference-preview">
                                    <img
                                        src={referenceImagePreview}
                                        alt="Reference image preview"
                                        className="pbai-reference-image"
                                    />
                                    <button
                                        className="pbai-reference-remove"
                                        onClick={handleRemoveReferenceImage}
                                        aria-label="Remove reference image"
                                    >
                                        ×
                                    </button>
                                </div>
                            ) : (
                                <div
                                    className={`pbai-reference-dropzone${isDraggingOver ? ' pbai-reference-dropzone--active' : ''}`}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                    onClick={() => referenceInputRef.current?.click()}
                                    role="button"
                                    tabIndex={0}
                                    onKeyDown={(e) => e.key === 'Enter' && referenceInputRef.current?.click()}
                                    aria-label="Upload reference image"
                                >
                                    <span className="pbai-reference-dropzone-icon">🖼️</span>
                                    <span className="pbai-reference-dropzone-text">
                                        Drag & drop or <u>click to browse</u>
                                    </span>
                                    <input
                                        ref={referenceInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        className="pbai-reference-input"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0]
                                            if (file) handleReferenceImageChange(file)
                                        }}
                                    />
                                </div>
                            )}
                            {referenceImageError && (
                                <p className="pbai-reference-error">{referenceImageError}</p>
                            )}
                            <p className="pbai-reference-hint">
                                Upload a reference image to guide the AI's style and composition
                            </p>
                        </div>

                        {/* Configuration Options */}
                        <div className="pbai-config-section">
                            <div className="pbai-form-group">
                                <PresetSelector
                                    required={true}
                                    selectedPreset={selectedPreset}
                                    onPresetChange={setSelectedPreset}
                                />
                            </div>

                            <div className="pbai-form-group">
                                <label htmlFor="instructions">Additional Instructions (optional):</label>
                                <textarea
                                    id="instructions"
                                    value={additionalInstructions}
                                    onChange={(e) => setAdditionalInstructions(e.target.value)}
                                    className="pbai-textarea"
                                    rows={4}
                                    placeholder="Describe any specific requirements for the photos like background, product type, etc... (e.g. 'Product is a shoe, so it should be on a shoe box')"
                                />
                            </div>
                        </div>

                        {/* Generate Button */}
                        <div className="pbai-actions">
                            <button
                                className={`pbai-generate-btn ${isGenerating ? 'generating' : ''}`}
                                onClick={handleGeneratePhotos}
                                disabled={isGenerating || !seedImage || !selectedPreset}
                            >
                                {isGenerating ? (
                                    <>
                                        <div>
                                            <span className="pbai-spinner"></span>
                                            <span className="pbai-spinner-text">Generating...</span>
                                        </div>
                                        <span className="text-sm">( it might take a few seconds)</span>
                                    </>
                                ) : (
                                    'Generate AI Photos'
                                )}
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
