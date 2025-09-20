"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select"
import { createPortal } from "react-dom"

const presets = [
    {
        id: "modern",
        name: "Modern Design",
        description: "Clean and minimalist aesthetic",
        image: "/modern-minimalist-design-interface.jpg",
    },
    {
        id: "vintage",
        name: "Vintage Style",
        description: "Classic retro appearance",
        image: "/vintage-retro-design-with-warm-colors.jpg",
    },
    {
        id: "dark",
        name: "Dark Theme",
        description: "Sleek dark mode styling",
        image: "/dark-theme-interface-with-neon-accents.jpg",
    },
    {
        id: "colorful",
        name: "Colorful Gradient",
        description: "Vibrant and energetic colors",
        image: "/colorful-gradient-design-with-bright-colors.jpg",
    },
    {
        id: "nature",
        name: "Nature Inspired",
        description: "Earth tones and organic shapes",
        image: "/nature-inspired-design-with-green-earth-tones.jpg",
    },
]

export function PresetSelector() {
    const [selectedPreset, setSelectedPreset] = useState<string>("")
    const [hoveredPreset, setHoveredPreset] = useState<string | null>(null)
    const [previewPosition, setPreviewPosition] = useState({ x: 0, y: 0 })

    const handleMouseEnter = (preset: string, event: React.MouseEvent) => {
        const rect = event.currentTarget.getBoundingClientRect()
        setPreviewPosition({
            x: rect.right + 8,
            y: rect.top,
        })
        setHoveredPreset(preset)
    }

    const hoveredPresetData = presets.find((p) => p.id === hoveredPreset)

    return (
        <div className="w-full max-w-md space-y-4">
            <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Choose Preset</label>
                <Select value={selectedPreset} onValueChange={setSelectedPreset}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Select a preset..." />
                    </SelectTrigger>
                    <SelectContent>
                        {presets.map((preset) => (
                            <SelectItem
                                key={preset.id}
                                value={preset.id}
                                onMouseEnter={(e) => handleMouseEnter(preset.id, e)}
                                onMouseLeave={() => setHoveredPreset(null)}
                                className="cursor-pointer transition-colors"
                            >
                                <div className="flex flex-col">
                                    <span className="font-medium">{preset.name}</span>
                                    <span className="text-xs text-muted-foreground">{preset.description}</span>
                                </div>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {
                hoveredPresetData &&
                createPortal(
                    <div
                        className="fixed z-[9999] w-64 bg-background border border-border rounded-lg shadow-lg p-3 pointer-events-none"
                        style={{
                            left: `${previewPosition.x}px`,
                            top: `${previewPosition.y}px`,
                        }}
                    >
                        <div className="aspect-[3/2] relative overflow-hidden rounded-md bg-muted mb-2">
                            <img
                                src={hoveredPresetData.image || "/placeholder.svg"}
                                alt={`${hoveredPresetData.name} preview`}
                                width={256}
                                height={256}
                                className="object-cover"
                                sizes="256px"
                            />
                        </div>
                        <div className="space-y-1">
                            <h4 className="font-semibold text-sm text-foreground">{hoveredPresetData.name}</h4>
                            <p className="text-xs text-muted-foreground">{hoveredPresetData.description}</p>
                        </div>
                    </div>,
                    document.querySelector('.pbai-overlay') as HTMLElement,
                )
            }

            {selectedPreset && (
                <div className="text-center">
                    <p className="text-sm text-muted-foreground">
                        Selected:{" "}
                        <span className="font-medium text-foreground">{presets.find((p) => p.id === selectedPreset)?.name}</span>
                    </p>
                </div>
            )}
        </div>
    )
}
