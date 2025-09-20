"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select"
import { createPortal } from "react-dom"

const presets = [
    {
        id: "white-infinity",
        name: "White Infinity",
        description: "Classic clean studio setup with seamless white background",
        prompt: "Transform the product into a professional studio photo on a pure seamless white background, soft even lighting, crisp details, no shadows, commercial catalog style.",
        image: "preset/white-infinity-preset.png",
    },
    {
        id: "minimal-shadow",
        name: "Minimal Shadow",
        description: "Light gray background with subtle natural shadows for depth",
        prompt: "Enhance the product with a soft studio setup, light gray background, gentle diffused shadows beneath and around the object, modern e-commerce look.",
        image: "preset/minimal-shadow-preset.png",
    },
    {
        id: "color-pop",
        name: "Color Pop",
        description: "Bold background color that highlights the product",
        prompt: "Place the product in a vibrant studio scene with a single bold background color, soft light reflections, high contrast to emphasize the product, editorial style.",
        image: "preset/color-pop-preset.png",
    },
    {
        id: "lifestyle-neutral",
        name: "Lifestyle Neutral",
        description: "Product staged in a minimal home environment",
        prompt: "Render the product in a styled lifestyle scene with neutral tones, clean surfaces, natural daylight effect, minimal furniture or props, calm and aspirational mood.",
        image: "preset/lifestyle-neutral-preset.png",
    },
    {
        id: "glossy-reflection",
        name: "Glossy Reflection",
        description: "Studio table with reflective surface",
        prompt: "Place the product on a glossy reflective surface, clean studio lighting from above, subtle reflection visible underneath, premium catalog feel.",
        image: "preset/glossy-reflection-preset.png",
    },
    {
        id: "soft-pastel",
        name: "Soft Pastel",
        description: "Pastel background with soft highlights",
        prompt: "Transform into a studio shot with pastel background (choose color), diffused lighting, dreamy highlights, playful and elegant atmosphere.",
        image: "preset/soft-pastel-preset.png",
    },
    {
        id: "natural-light-desk",
        name: "Natural Light Desk",
        description: "Bright desk/tabletop scene near a window",
        prompt: "Show the product on a modern tabletop with sunlight filtering in from the side, natural shadows, lifestyle photo with airy and authentic feel.",
        image: "preset/natural-light-desk-preset.png",
    },
    {
        id: "dramatic-dark",
        name: "Dramatic Dark",
        description: "Black background with spotlight and contrast",
        prompt: "Place the product in a dramatic studio scene with deep black background, spotlight glow, strong contrast, cinematic and luxurious mood.",
        image: "preset/dramatic-dark-preset.png",
    },
    {
        id: "plant-props",
        name: "Plant & Props",
        description: "Studio photo with greenery or props for lifestyle accent",
        prompt: "Render the product in a styled studio scene with minimal props (green plants, books, small decor), soft neutral background, aspirational lifestyle aesthetic.",
        image: "preset/plant-props-preset.png",
    },
    {
        id: "gradient-glow",
        name: "Gradient Glow",
        description: "Smooth gradient background for a modern look",
        prompt: "Enhance the product with a soft studio gradient background (two tones), diffused top lighting, clean balanced shadows, futuristic product showcase.",
        image: "preset/gradient-glow-preset.png",
    },
]

interface PresetSelectorProps {
    selectedPreset: string
    onPresetChange: (preset: string) => void
}

export function PresetSelector({ selectedPreset, onPresetChange }: PresetSelectorProps) {
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

    const handleSelectPreset = (preset: string) => {
        onPresetChange(preset)
        setHoveredPreset(null)
    }

    const hoveredPresetData = presets.find((p) => p.id === hoveredPreset)

    return (
        <div className="w-full max-w-md space-y-4">
            <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Choose Preset</label>
                <Select value={selectedPreset} onValueChange={handleSelectPreset}>
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
                        <div className="aspect-[1/1] relative overflow-hidden rounded-md bg-muted mb-2">
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
        </div>
    )
}
