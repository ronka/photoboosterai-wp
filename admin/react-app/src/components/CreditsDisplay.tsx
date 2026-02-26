interface CreditsDisplayProps {
    credits: number | null
    creditsLoading: boolean
    creditsError: string | null
}

export function CreditsDisplay({ credits, creditsLoading, creditsError }: CreditsDisplayProps) {
    if (creditsLoading) {
        return (
            <div className="pbai-credits-display">
                <span className="pbai-credits-loading">Loading credits...</span>
            </div>
        )
    }


    if (credits === null) {
        // No API key configured
        return (
            <div className="pbai-credits-display">
                <a
                    href={`${window.location.origin}/wp-admin/options-general.php?page=photobooster-ai-settings`}
                    className="pbai-credits-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    ⚙️ Configure API Key
                </a>
            </div>
        )
    }

    if (credits === 0) {
        return (
            <div className="pbai-credits-display">
                <a
                    href="https://photoboosterai.com/pricing"
                    className="pbai-credits-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    🔄 Buy Credits ({credits})
                </a>
            </div>
        )
    }

    return (
        <div className="pbai-credits-display">
            <span className="pbai-credits-count">💳 {credits} credits</span>
        </div>
    )
}
