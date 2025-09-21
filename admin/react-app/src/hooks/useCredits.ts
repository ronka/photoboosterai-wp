import { useState, useEffect } from 'react'

interface CreditsData {
    credits: number
    lastResetDate?: string
    userId?: string
}

interface UseCreditsReturn {
    credits: number | null
    creditsLoading: boolean
    creditsError: string | null
}

export function useCredits(): UseCreditsReturn {
    const [credits, setCredits] = useState<number | null>(null)
    const [creditsLoading, setCreditsLoading] = useState(true)
    const [creditsError, setCreditsError] = useState<string | null>(null)

    useEffect(() => {
        const fetchCredits = async () => {
            try {
                setCreditsLoading(true)
                setCreditsError(null)

                // Get WordPress REST API settings
                const wpSettings = (window as any).PBAIEnhance
                if (!wpSettings) {
                    throw new Error('WordPress API settings not found')
                }

                // Make request to WordPress REST API for credits
                const response = await fetch(`${wpSettings.restBase}/credits`, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': wpSettings.nonce,
                    },
                })

                const result = await response.json()

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'Failed to fetch credits')
                }

                setCredits(result.credits)

            } catch (error) {
                console.error('Credits fetch error:', error)
                setCreditsError(error instanceof Error ? error.message : 'Unknown error')
                setCredits(null)
            } finally {
                setCreditsLoading(false)
            }
        }

        fetchCredits()
    }, [])

    return {
        credits,
        creditsLoading,
        creditsError
    }
}
