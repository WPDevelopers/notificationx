import React, { useState, useCallback, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { useBuilderContext } from 'quickbuilder'
import PressbarAdminPreview from './helpers/PressbarAdminPreview'

interface PresetDesign {
  id: string
  title: string
  description: string
  config: any // NotificationX bar configuration
  data?: any // Additional data for the bar
  elementor_data?: any
  gutenberg_data?: any
  style: 'modern' | 'classic' | 'minimal' | 'bold' | 'gradient'
  colors: {
    primary: string
    secondary: string
    text: string
    background: string
  }
}

// Cookie utility functions
const setCookie = (name: string, value: string, hours: number) => {
  const expires = new Date()
  expires.setTime(expires.getTime() + (hours * 60 * 60 * 1000))
  document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/`
}

const getCookie = (name: string): string | null => {
  const nameEQ = name + "="
  const ca = document.cookie.split(';')
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i]
    while (c.charAt(0) === ' ') c = c.substring(1, c.length)
    if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length))
  }
  return null
}

const deleteCookie = (name: string) => {
  document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`
}

const BuildWithAI = () => {
  const builderContext = useBuilderContext()
  const [prompt, setPrompt] = useState('')
  const [isGenerating, setIsGenerating] = useState(false)
  const [generatedPresets, setGeneratedPresets] = useState<PresetDesign[]>([])
  const [currentPage, setCurrentPage] = useState(1)
  const [hasMorePresets, setHasMorePresets] = useState(true)
  const [selectedPresetId, setSelectedPresetId] = useState<string | null>(null)
  const [isPromptModalOpen, setIsPromptModalOpen] = useState(false)
  const [isLoadingMore, setIsLoadingMore] = useState(false)
  const [hasCachedData, setHasCachedData] = useState(false)
  
  // Predefined prompt suggestions
  const predefinedPrompts = [
    "I need notification bar designs for a big New Year celebration with 50% off on all products for the next 5 days.",
    "Give me creative notification bar ideas for a Black Friday mega sale lasting 7 days.",
    "Show me some bold and colorful banner designs for a flash sale ending tonight.",
    "I want modern, minimal banners to announce a new product launch with a signup button.",
    "Design elegant countdown banners for our Christmas holiday sale.",
    "Create playful and fun banner ideas with emojis for a summer clearance sale.",
    "Give me stylish bar designs to promote free shipping for orders above $50.",
    "Make festive banners for a Halloween special offer with spooky colors.",
    "I need simple announcement bars for a website maintenance notice — no countdown.",
    "Generate bright and colorful banners for a Diwali celebration sale with a countdown.",
    "Give me corporate-looking announcement bars for a business webinar registration.",
    "I want gradient-based banners for a Valentine's Day buy-one-get-one offer.",
    "Show me dark mode notification bars for a tech product discount.",
    "Create banners for a 24-hour flash deal on electronics with bold call-to-action buttons.",
    "Make retro-style banners for a vintage clothing store weekend sale.",
    "I need sliding text banners for promoting multiple limited-time offers.",
    "Design banners for a 10th anniversary store celebration with giveaways.",
    "Give me luxury-style bars for a premium subscription offer with gold and black colors.",
    "Make cheerful banners for a spring season sale with soft pastel colors.",
    "Create exclusive countdown bars for a pre-order campaign ending in 48 hours."
  ]

  // Sample preset data - In real implementation, this would come from AI API
  const samplePresets: PresetDesign[] = [];

  // Cache management functions
  const CACHE_KEY = 'buildWithAI_presets'
  const CACHE_PROMPT_KEY = 'buildWithAI_prompt'
  const CACHE_HOURS = 3

  const savePresetsToCache = useCallback((presets: PresetDesign[], promptUsed: string) => {
    const cacheData = {
      presets,
      prompt: promptUsed,
      timestamp: Date.now(),
      currentPage: currentPage
    }
    setCookie(CACHE_KEY, JSON.stringify(cacheData), CACHE_HOURS)
    setHasCachedData(true)
  }, [currentPage])

  const loadPresetsFromCache = useCallback(() => {
    const cachedData = getCookie(CACHE_KEY)
    if (cachedData) {
      try {
        const parsed = JSON.parse(cachedData)
        const cacheAge = Date.now() - parsed.timestamp
        const maxAge = CACHE_HOURS * 60 * 60 * 1000 // 3 hours in milliseconds

        if (cacheAge < maxAge && parsed.presets && Array.isArray(parsed.presets)) {
          setGeneratedPresets(parsed.presets)
          setPrompt(parsed.prompt || '')
          setCurrentPage(parsed.currentPage || 1)
          setHasCachedData(true)
          console.log('Loaded presets from cache:', parsed.presets.length, 'presets')
          return true
        } else {
          // Cache expired, clear it
          clearPresetsCache()
        }
      } catch (error) {
        console.error('Error parsing cached presets:', error)
        clearPresetsCache()
      }
    }
    return false
  }, [])

  const clearPresetsCache = useCallback(() => {
    deleteCookie(CACHE_KEY)
    deleteCookie(CACHE_PROMPT_KEY)
    setHasCachedData(false)
    setGeneratedPresets([])
    setCurrentPage(1)
    setHasMorePresets(true)
    console.log('Cleared presets cache')
  }, [])

  // Load cached data on component mount
  useEffect(() => {
    loadPresetsFromCache()
  }, [loadPresetsFromCache])

  const handleGenerateDesigns = useCallback(async () => {
    if (!prompt.trim()) return
    setIsGenerating(true)

    try {
      // Add timeout and better error handling
      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), 30000) // 30 second timeout

      const response = await fetch('https://shakibn8n-uvjo.xc1.app/webhook/c9fad03f-dba9-4f04-9624-036ce3d6b0e3', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          prompt: prompt,
          action: 'generate_designs',
          page: 1,
          timestamp: Date.now()
        }),
        signal: controller.signal,
        mode: 'cors', // Explicitly set CORS mode
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`)
      }

      const data = await response.json()
      console.log('AI API Response:', data)

      let newPresets: PresetDesign[] = [];

      // Handle different response formats
      if (data.output && typeof data.output === 'string') {
        try {
          newPresets = eval(data.output); // Parse the string response
        } catch (e) {
          console.error("Error parsing AI presets from string:", e);
          newPresets = [];
        }
      } else if (Array.isArray(data)) {
        newPresets = data;
      } else if (data.presets && Array.isArray(data.presets)) {
        newPresets = data.presets;
      } else {
        console.warn('Unexpected API response format:', data);
        newPresets = [];
      }

      if (newPresets.length > 0) {
        // Use AI-generated presets
        const aiPresets = newPresets.map((preset, index) => ({
          ...preset,
          id: `ai-preset-${Date.now()}-${index}`,
          title: preset.title || `AI Generated Design ${index + 1}`,
          description: preset.description || `Generated based on: "${prompt}"`
        }))
        setGeneratedPresets(aiPresets)
        savePresetsToCache(aiPresets, prompt)
        console.log('Successfully loaded AI-generated presets:', aiPresets.length)
      } else {
        // Fallback to demo presets if no AI presets
        throw new Error('No presets received from AI API')
      }

      setCurrentPage(1)
      setHasMorePresets(true)

    } catch (error) {
      console.error('Error generating designs:', error)

      // Determine error type for better user feedback
      let errorMessage = 'Unable to generate designs'
      if (error.name === 'AbortError') {
        errorMessage = 'Request timed out - using demo designs'
      } else if (error.message.includes('Failed to fetch')) {
        errorMessage = 'Network error - using demo designs'
      } else if (error.message.includes('CORS')) {
        errorMessage = 'CORS error - using demo designs'
      }

      console.warn(errorMessage)

      // Create demo presets based on prompt
      const demoPresets = createDemoPresets(prompt)
      setGeneratedPresets(demoPresets)
      setCurrentPage(1)
      setHasMorePresets(true)

      // Don't save demo presets to cache
      // savePresetsToCache(demoPresets, prompt)
    } finally {
      setIsGenerating(false)
    }
  }, [prompt, savePresetsToCache])

  // Helper function to create demo presets based on prompt
  const createDemoPresets = useCallback((userPrompt: string) => {
    const demoTemplates = [
      {
        id: 'demo-1',
        title: 'Modern Sale Banner',
        description: 'Clean modern design with gradient background',
        style: 'modern' as const,
        colors: {
          primary: '#6366f1',
          secondary: '#8b5cf6',
          text: '#ffffff',
          background: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)'
        },
        config: {
          nx_id: 'demo-preview-1',
          themes: 'theme-one',
          position: 'top',
          sticky_bar: true,
          advance_edit: true,
          enable_countdown: true,
          countdown_text: 'Limited Time Offer!',
          countdown_start_date: new Date().toISOString(),
          countdown_end_date: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
          evergreen_timer: false,
          press_content: `🔥 <strong>Special Offer!</strong> ${userPrompt.slice(0, 50)}...`,
          bar_content_type: 'static',
          button_text: 'Shop Now',
          button_url: '#',
          bar_bg_color: '#6366f1',
          bar_text_color: '#ffffff',
          bar_btn_bg: '#fbbf24',
          bar_btn_text_color: '#1f2937',
          bar_counter_bg: '#4f46e5',
          bar_counter_text_color: '#ffffff',
          bar_font_size: '16px',
          bar_close_position: 'right',
          bar_close_color: '#ffffff',
          bar_close_button_size: '12px'
        }
      },
      {
        id: 'demo-2',
        title: 'Bold Announcement',
        description: 'High-impact design for maximum attention',
        style: 'bold' as const,
        colors: {
          primary: '#dc2626',
          secondary: '#b91c1c',
          text: '#ffffff',
          background: '#dc2626'
        },
        config: {
          nx_id: 'demo-preview-2',
          themes: 'theme-two',
          position: 'bottom',
          sticky_bar: true,
          advance_edit: true,
          enable_countdown: false,
          press_content: `🚨 <strong>Important!</strong> ${userPrompt.slice(0, 50)}...`,
          bar_content_type: 'static',
          button_text: 'Learn More',
          button_url: '#',
          bar_bg_color: '#dc2626',
          bar_text_color: '#ffffff',
          bar_btn_bg: '#fbbf24',
          bar_btn_text_color: '#1f2937',
          bar_font_size: '16px',
          bar_close_position: 'right',
          bar_close_color: '#ffffff',
          bar_close_button_size: '12px'
        }
      },
      {
        id: 'demo-3',
        title: 'Minimal Design',
        description: 'Clean and subtle notification bar',
        style: 'minimal' as const,
        colors: {
          primary: '#f8fafc',
          secondary: '#e2e8f0',
          text: '#1e293b',
          background: '#f8fafc'
        },
        config: {
          nx_id: 'demo-preview-3',
          themes: 'theme-three',
          position: 'top',
          sticky_bar: false,
          advance_edit: true,
          enable_countdown: false,
          press_content: `📢 ${userPrompt.slice(0, 60)}...`,
          bar_content_type: 'static',
          button_text: 'Get Started',
          button_url: '#',
          bar_bg_color: '#f8fafc',
          bar_text_color: '#1e293b',
          bar_btn_bg: '#0f172a',
          bar_btn_text_color: '#ffffff',
          bar_font_size: '14px',
          bar_close_position: 'right',
          bar_close_color: '#64748b',
          bar_close_button_size: '10px'
        }
      }
    ]

    return demoTemplates.map((template, index) => ({
      ...template,
      id: `demo-preset-${Date.now()}-${index}`,
      description: `Demo design based on: "${userPrompt}" (Network unavailable)`
    }))
  }, [])

  const handleLoadMore = useCallback(async () => {
    if (!hasMorePresets || !prompt.trim() || isLoadingMore) return

    setIsLoadingMore(true)
    try {
      // Call n8n webhook for more AI-generated designs
      const response = await fetch('https://shakibn8n-uvjo.xc1.app/webhook/c9fad03f-dba9-4f04-9624-036ce3d6b0e3', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          prompt: prompt,
          action: 'load_more_designs',
          page: currentPage + 1,
          timestamp: Date.now()
        })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
       const data = await response.json()
        let newPresets;
        try {
          newPresets = eval(data.output); // now it's an actual array
        } catch (e) {
          console.error("Error parsing AI presets:", e);
          newPresets = [];
        }
        samplePresets.push(...newPresets);
        const fallbackPresets = samplePresets.map((preset, index) => ({
          ...preset,
          id: `ai-preset-${Date.now()}-${index}`,
          title: `${preset.title} - ${prompt.slice(0, 20)}...`,
          description: `Generated based on: "${prompt}" (Fallback)`
        }))
        setGeneratedPresets(fallbackPresets)
      setCurrentPage(prev => prev + 1)
    } catch (error) {
      console.error('Error loading more presets:', error)

      // Fallback to sample presets on error
      const morePresets = samplePresets.map((preset, index) => ({
        ...preset,
        id: `ai-preset-page${currentPage + 1}-${index}`,
        title: `${preset.title} - Variation ${currentPage + 1} (Fallback)`,
        description: `Generated based on: "${prompt}" (Page ${currentPage + 1} - Fallback)`
      }))
      setGeneratedPresets(prev => [...prev, ...morePresets])
      setCurrentPage(prev => prev + 1)
      setHasMorePresets(currentPage < 2) // Limit fallback to 3 pages
    } finally {
      setIsLoadingMore(false)
    }
  }, [currentPage, hasMorePresets, prompt, samplePresets, isLoadingMore])

  const handleSelectPreset = useCallback((preset: PresetDesign) => {
    // Set the selected preset
    setSelectedPresetId(preset.id)

    // Apply the preset configuration to the main builder context
    if (builderContext?.setValues) {
      // Merge the preset configuration with existing values
      const updatedValues = {
        ...builderContext.values,
        ...preset.config,
        // Ensure we maintain the current nx_id if it exists
        nx_id: builderContext.values?.nx_id || preset.config.nx_id,
        // Ensure advance_edit is true so background colors are applied
        advance_edit: true,
        // Ensure the background color is properly set
        bar_bg_color: preset.config.bar_bg_color || preset.colors.primary,
        // Ensure other color settings are applied
        bar_text_color: preset.config.bar_text_color || preset.colors.text,
        bar_btn_bg: preset.config.bar_btn_bg || preset.colors.secondary,
        bar_btn_text_color: preset.config.bar_btn_text_color || preset.colors.text,
        bar_counter_bg: preset.config.bar_counter_bg || preset.colors.secondary,
        bar_counter_text_color: preset.config.bar_counter_text_color || preset.colors.text,
      }

      builderContext.setValues(updatedValues)

      console.log('Applied preset configuration:', {
        presetTitle: preset.title,
        presetId: preset.id,
        appliedConfig: updatedValues,
        backgroundColorApplied: updatedValues.bar_bg_color
      })
    }
  }, [builderContext, setSelectedPresetId])

  const handleUsePrompt = useCallback((selectedPrompt: string) => {
    setPrompt(selectedPrompt)
    setIsPromptModalOpen(false)
  }, [])

  const handleOpenPromptModal = useCallback(() => {
    setIsPromptModalOpen(true)
  }, [])

  const handleClosePromptModal = useCallback(() => {
    setIsPromptModalOpen(false)
  }, [])

  

  return (
    <div className="build-with-ai">
      <div className="build-with-ai__header">
        <div className="build-with-ai__title">
          <h3>{__('Build Notification Bar with AI', 'notificationx')}</h3>
          <p>{__('Describe your ideal notification bar and let AI generate beautiful designs for you.', 'notificationx')}</p>
        </div>
      </div>

      <div className="build-with-ai__prompt-section">
        <div className="build-with-ai__prompt-wrapper">
          <div className="build-with-ai__prompt-header">
            <label htmlFor="ai-prompt" className="build-with-ai__prompt-label">
              {__('Describe your notification bar', 'notificationx')}
            </label>
          </div>
          <textarea
            id="ai-prompt"
            className="build-with-ai__prompt-input"
            placeholder={__('E.g., "Create a modern sale notification bar with purple gradient background, white text, and a call-to-action button for Black Friday deals"', 'notificationx')}
            value={prompt}
            onChange={(e) => setPrompt(e.target.value)}
            rows={4}
          />
        </div>

        {/* Predefined Prompts Section */}
        <div className="build-with-ai__predefined-prompts">
          <h4 className="build-with-ai__predefined-title">
            {__('Quick Start Prompts', 'notificationx')}
          </h4>
          <p className="build-with-ai__predefined-description">
            {__('Click on any prompt below to use it, or get inspired to create your own:', 'notificationx')}
          </p>

          <div className="build-with-ai__prompts-grid">
            {predefinedPrompts.map((promptText, index) => (
              <button
                key={index}
                className="build-with-ai__prompt-card"
                onClick={() => setPrompt(promptText)}
                title={__('Click to use this prompt', 'notificationx')}
              >
                <div className="build-with-ai__prompt-card-content">
                  {promptText}
                </div>
                <div className="build-with-ai__prompt-card-action">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
              </button>
            ))}
          </div>
        </div>

        <div className="build-with-ai__action-buttons">
          <button
            className="build-with-ai__generate-btn"
            onClick={handleGenerateDesigns}
            disabled={!prompt.trim() || isGenerating}
          >
            {isGenerating ? (
              <>
                <span className="build-with-ai__spinner"></span>
                {__('Generating Designs...', 'notificationx')}
              </>
            ) : (
              <>
                <svg className="build-with-ai__ai-icon" viewBox="0 0 24 24" fill="none">
                  <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
                </svg>
                {__('Generate Designs', 'notificationx')}
              </>
            )}
          </button>

          {hasCachedData && (
            <button
              className="build-with-ai__clear-btn"
              onClick={clearPresetsCache}
              title={__('Clear cached designs and start fresh', 'notificationx')}
            >
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
              {__('Clear Cache', 'notificationx')}
            </button>
          )}
        </div>
      </div>

      {generatedPresets.length > 0 && (
        <div className="build-with-ai__presets-section">
          {hasCachedData && (
            <div className="build-with-ai__cache-indicator">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
              </svg>
              <span>{__('Loaded from cache (expires in 3 hours)', 'notificationx')}</span>
            </div>
          )}
          <div className="build-with-ai__presets-list">
            {generatedPresets.map((preset) => {
              const isSelected = selectedPresetId === preset.id

              return (
                <div
                  key={preset.id}
                  className={`build-with-ai__preset-row ${isSelected ? 'build-with-ai__preset-row--selected' : ''}`}
                  onClick={() => handleSelectPreset(preset)}
                  role="button"
                  tabIndex={0}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault()
                      handleSelectPreset(preset)
                    }
                  }}
                  title={__('Click to apply this design to your notification bar', 'notificationx')}
                >
                  <div className="build-with-ai__preset-preview">
                    <div className="build-with-ai__live-preview">
                      <PressbarAdminPreview
                        key={`preset-${preset.id}-${preset.config.bar_bg_color}`}
                        position={preset.config.position || 'top'}
                        nxBar={{
                          config: {
                            ...preset.config,
                            advance_edit: true, // Ensure advance_edit is true for color application
                            bar_bg_color: preset.config.bar_bg_color || preset.colors.primary,
                            bar_text_color: preset.config.bar_text_color || preset.colors.text,
                            bar_btn_bg: preset.config.bar_btn_bg || preset.colors.secondary,
                            bar_btn_text_color: preset.config.bar_btn_text_color || preset.colors.text,
                          },
                          data: preset.data || {}
                        }}
                        dispatch={() => {}} // Empty dispatch for preview only
                      />
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}

      {generatedPresets.length === 0 && !isGenerating && (
        <div className="build-with-ai__empty-state">
          <div className="build-with-ai__empty-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
            </svg>
          </div>
          <h4>{__('Ready to Create Amazing Designs?', 'notificationx')}</h4>
          <p>{__('Enter your requirements above and let AI generate beautiful notification bar designs tailored to your needs.', 'notificationx')}</p>
        </div>
      )}

      {/* Prompt Selection Modal */}
      {isPromptModalOpen && (
        <div className="build-with-ai__modal-overlay" onClick={handleClosePromptModal}>
          <div className="build-with-ai__modal" onClick={(e) => e.stopPropagation()}>
            <div className="build-with-ai__modal-header">
              <h3>{__('Choose a Prompt', 'notificationx')}</h3>
              <button
                className="build-with-ai__modal-close"
                onClick={handleClosePromptModal}
                title={__('Close', 'notificationx')}
              >
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </button>
            </div>

            <div className="build-with-ai__modal-content">
              <p className="build-with-ai__modal-description">
                {__('Select a predefined prompt to get started quickly, or use it as inspiration for your own custom prompt.', 'notificationx')}
              </p>

              <div className="build-with-ai__prompt-list">
                {predefinedPrompts.map((promptText, index) => (
                  <button
                    key={index}
                    className="build-with-ai__prompt-item"
                    onClick={() => handleUsePrompt(promptText)}
                  >
                    <div className="build-with-ai__prompt-text">
                      {promptText}
                    </div>
                    <div className="build-with-ai__prompt-action">
                      <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                      </svg>
                    </div>
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default BuildWithAI
