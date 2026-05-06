import React, { useState, useEffect } from 'react';

const useCountdown = (endDateStr: string) => {
    const calc = () => {
        const end = Date.parse(endDateStr);
        if (!end || isNaN(end)) return null;
        const diff = end - Date.now();
        if (diff <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return {
            days:    Math.floor(diff / (1000 * 60 * 60 * 24)),
            hours:   Math.floor((diff / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((diff / (1000 * 60)) % 60),
            seconds: Math.floor((diff / 1000) % 60),
        };
    };
    const [timeLeft, setTimeLeft] = useState(calc);
    useEffect(() => {
        if (!endDateStr) return;
        const timer = setInterval(() => setTimeLeft(calc()), 1000);
        return () => clearInterval(timer);
    }, [endDateStr]);
    return timeLeft;
};

const pad = (n: number) => String(n).padStart(2, '0');

const ExitIntentPopup = (props: any) => {
    const { nxExitIntent, dispatch } = props;
    const { config: settings }       = nxExitIntent;
    const [isVisible, setIsVisible]  = useState(true);
    const [reason, setReason]        = useState('');
    const [name, setName]            = useState('');
    const [email, setEmail]          = useState('');
    const [videoPlaying, setVideoPlaying] = useState(false);

    const theme     = settings?.themes?.replace(`${settings?.source}_`, '') || 'theme-one';
    const showClose = settings?.show_close_button !== false;

    const timeLeft = useCountdown(settings?.exit_intent_countdown_end || '');

    const handleClose = () => {
        sessionStorage.setItem(`notificationx_exit_intent_${settings?.nx_id}`, 'closed');
        setIsVisible(false);
        dispatch?.({ type: 'REMOVE_NOTIFICATION', payload: nxExitIntent.id });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleClose();
    };

    if (!isVisible) return null;

    // ─── Theme Four ───────────────────────────────────────────────────────────
    if (theme === 'theme-four') {
        const badge    = settings?.exit_intent_t4_badge    || 'Before you go...';
        const title    = settings?.exit_intent_t4_title    || 'Watch this short demo video';
        const subtitle = settings?.exit_intent_t4_subtitle || 'See how our product simplifies your workflow.';
        const imageUrl = settings?.exit_intent_image_url?.url || settings?.exit_intent_image_url || '';

        const rawVideoUrl = settings?.exit_intent_t4_video_url;
        const videoUrl = typeof rawVideoUrl === 'string'
            ? rawVideoUrl
            : (rawVideoUrl?.url || '');

        const getEmbedUrl = (url: string) => {
            if (typeof url !== 'string' || !url) return '';
            const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?/]+)/);
            if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}?autoplay=1`;
            const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) return `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`;
            return url;
        };

        const handlePlay = (e: React.MouseEvent) => {
            e.preventDefault();
            e.stopPropagation();
            if (videoUrl && !videoPlaying) setVideoPlaying(true);
        };

        return (
            <div className="nx-exit-intent-overlay" onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-four nx-exit-intent-${settings?.nx_id}`}
                >
                    {showClose && (
                        <button className="nx-exit-intent-close" onClick={handleClose} aria-label="Close">
                            &times;
                        </button>
                    )}

                    <span className="nx-exit-intent-t4-badge">{badge}</span>
                    <h2 className="nx-exit-intent-t4-title">{title}</h2>
                    {subtitle && <p className="nx-exit-intent-t4-subtitle">{subtitle}</p>}

                    {videoPlaying && videoUrl ? (
                        <iframe
                            className="nx-exit-intent-t4-iframe"
                            src={getEmbedUrl(videoUrl)}
                            allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                            allowFullScreen
                            title="Video"
                        />
                    ) : (
                        <div
                            className={`nx-exit-intent-t4-video-wrap${!imageUrl ? ' nx-exit-intent-t4-no-image' : ''}`}
                            onClick={videoUrl ? handlePlay : undefined}
                            role={videoUrl ? 'button' : undefined}
                            tabIndex={videoUrl ? 0 : undefined}
                        >
                            {imageUrl && <img src={imageUrl} alt="" />}
                            {videoUrl && (
                                <button
                                    type="button"
                                    className="nx-exit-intent-t4-play"
                                    aria-label="Play video"
                                    onClick={handlePlay}
                                >
                                    <div className="nx-exit-intent-t4-play-icon">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <polygon points="5,3 19,12 5,21" fill="#1a1a2e"/>
                                        </svg>
                                    </div>
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>
        );
    }

    // ─── Theme Two ────────────────────────────────────────────────────────────
    if (theme === 'theme-two') {
        const saleBadge      = settings?.exit_intent_sale_badge      || 'Flash Sale';
        const saleHeadline   = settings?.exit_intent_sale_headline   || '50% OFF';
        const saleDesc       = settings?.exit_intent_sale_desc       || 'ON ENTIRE ORDER';
        const countdownLabel = settings?.exit_intent_countdown_label || 'LIMITED-TIME OFFER! SALE ENDS IN';
        const buttonText     = settings?.exit_intent_button_text     || 'Shop The Flash Sale Now';
        const dismissText    = settings?.exit_intent_dismiss_text    || 'NO, THANKS!';
        const imageUrl       = settings?.exit_intent_image_url?.url || settings?.exit_intent_image_url || '';

        const adv = settings?.advance_edit;
        const overlayStyle: React.CSSProperties = adv
            ? { background: settings.exit_intent_overlay_color || 'rgba(0,0,0,0.5)' }
            : {};

        return (
            <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-two nx-exit-intent-${settings?.nx_id}`}
                >
                    {showClose && (
                        <button className="nx-exit-intent-close" onClick={handleClose} aria-label="Close">
                            &times;
                        </button>
                    )}

                    {/* Left column */}
                    <div className="nx-exit-intent-t2-left">
                        <span className="nx-exit-intent-t2-badge">{saleBadge}</span>
                        <h2 className="nx-exit-intent-t2-headline">{saleHeadline}</h2>
                        <p className="nx-exit-intent-t2-desc">{saleDesc}</p>

                        {timeLeft !== null && (
                            <>
                                <p className="nx-exit-intent-t2-countdown-label">{countdownLabel}</p>
                                <div className="nx-exit-intent-t2-countdown">
                                    {(['days', 'hours', 'minutes', 'seconds'] as const).map((unit, i) => (
                                        <div key={unit} className="nx-exit-intent-t2-countdown-unit">
                                            <span className="nx-exit-intent-t2-countdown-num">
                                                {pad(timeLeft[unit])}
                                            </span>
                                            <span className="nx-exit-intent-t2-countdown-lbl">
                                                {['DAYS', 'HRS', 'MIN', 'SEC'][i]}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}

                        <button type="button" className="nx-exit-intent-t2-btn" onClick={handleClose}>
                            {buttonText}
                        </button>
                        <button type="button" className="nx-exit-intent-t2-dismiss" onClick={handleClose}>
                            {dismissText}
                        </button>
                    </div>

                    {/* Right column — image panel */}
                    <div
                        className="nx-exit-intent-t2-right"
                        style={imageUrl ? { backgroundImage: `url(${imageUrl})` } : undefined}
                    />
                </div>
            </div>
        );
    }

    // ─── Theme Three ─────────────────────────────────────────────────────────
    if (theme === 'theme-three') {
        const title      = settings?.exit_intent_t3_title      || "Wait, don't go!";
        const subtitle   = settings?.exit_intent_t3_subtitle   || 'Before you leave, we have a special offer just for you!';
        const offerText  = settings?.exit_intent_t3_offer      || 'Get 15% off your next purchase!';
        const couponText = settings?.exit_intent_t3_coupon_text || "Use code STAY15 at checkout. Don't miss out on this limited-time offer.";
        const buttonText = settings?.exit_intent_button_text   || 'Claim Offer';
        const dismissText = settings?.exit_intent_dismiss_text || 'No, thanks!';
        const imageUrl   = settings?.exit_intent_image_url?.url || settings?.exit_intent_image_url || '';

        return (
            <div className="nx-exit-intent-overlay" onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-three nx-exit-intent-${settings?.nx_id}`}
                >
                    {imageUrl && (
                        <div className="nx-exit-intent-t3-character" aria-hidden="true">
                            <img src={imageUrl} alt="" />
                        </div>
                    )}

                    <div className="nx-exit-intent-t3-body">
                        {showClose && (
                            <button className="nx-exit-intent-close" onClick={handleClose} aria-label="Close">
                                &times;
                            </button>
                        )}
                        <h2 className="nx-exit-intent-t3-title">{title}</h2>
                        <p  className="nx-exit-intent-t3-subtitle">{subtitle}</p>
                        <p  className="nx-exit-intent-t3-offer">{offerText}</p>
                        <p  className="nx-exit-intent-t3-coupon-text">{couponText}</p>
                        <button type="button" className="nx-exit-intent-t3-btn" onClick={handleClose}>
                            {buttonText}
                        </button>
                        <button type="button" className="nx-exit-intent-t3-dismiss" onClick={handleClose}>
                            {dismissText}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // ─── Theme One (default) ──────────────────────────────────────────────────
    const title    = settings?.exit_intent_title    || 'Wait! Before You Go...';
    const subtitle = settings?.exit_intent_subtitle || "We'd love to understand what's holding you back";
    const productName = settings?.exit_intent_product_name || 'our product';
    const question = (settings?.exit_intent_question || "What's stopping you from getting {product} today?")
        .replace('{product}', productName);
    const reasons: { reason_label: string }[] = settings?.exit_intent_reasons || [
        { reason_label: "It's too expensive" },
        { reason_label: 'I need more information' },
        { reason_label: "I'm not ready yet" },
        { reason_label: 'Found a better alternative' },
    ];
    const reasonPlaceholder = settings?.exit_intent_reason_placeholder || 'Select Reason';
    const buttonText        = settings?.exit_intent_button_text        || 'SUBMIT';
    const showReason        = settings?.exit_intent_show_reason  !== false;
    const showName          = settings?.exit_intent_show_name    !== false;
    const showEmail         = settings?.exit_intent_show_email   !== false;
    const namePlaceholder   = settings?.exit_intent_name_label   || 'Name *';
    const emailPlaceholder  = settings?.exit_intent_email_label  || 'Enter Your Email *';

    const adv = settings?.advance_edit;
    const popupStyle: React.CSSProperties = adv ? {
        background:   settings.exit_intent_bg_color      || '#EDE7FF',
        borderRadius: settings.exit_intent_border_radius ? `${settings.exit_intent_border_radius}px` : undefined,
        maxWidth:     settings.exit_intent_max_width     ? `${settings.exit_intent_max_width}px`     : undefined,
    } : {};
    const overlayStyle: React.CSSProperties = adv ? {
        background: settings.exit_intent_overlay_color || 'rgba(0,0,0,0.5)',
    } : {};
    const titleStyle: React.CSSProperties = adv ? {
        color:      settings.exit_intent_title_color       || undefined,
        fontSize:   settings.exit_intent_title_font_size   ? `${settings.exit_intent_title_font_size}px`   : undefined,
        fontWeight: settings.exit_intent_title_font_weight || undefined,
    } : {};
    const subtitleStyle: React.CSSProperties = adv ? {
        color:    settings.exit_intent_subtitle_color     || undefined,
        fontSize: settings.exit_intent_subtitle_font_size ? `${settings.exit_intent_subtitle_font_size}px` : undefined,
    } : {};
    const questionStyle: React.CSSProperties = adv ? {
        color:    settings.exit_intent_question_color     || undefined,
        fontSize: settings.exit_intent_question_font_size ? `${settings.exit_intent_question_font_size}px` : undefined,
    } : {};
    const inputStyle: React.CSSProperties = adv ? {
        background:   settings.exit_intent_input_bg              || undefined,
        borderColor:  settings.exit_intent_input_border_color    || undefined,
        borderRadius: settings.exit_intent_input_border_radius   ? `${settings.exit_intent_input_border_radius}px` : undefined,
        color:        settings.exit_intent_input_text_color      || undefined,
    } : {};
    const btnStyle: React.CSSProperties = adv ? {
        background:   settings.exit_intent_btn_bg            || undefined,
        color:        settings.exit_intent_btn_color         || undefined,
        borderRadius: settings.exit_intent_btn_border_radius ? `${settings.exit_intent_btn_border_radius}px` : undefined,
        fontSize:     settings.exit_intent_btn_font_size     ? `${settings.exit_intent_btn_font_size}px`     : undefined,
        fontWeight:   settings.exit_intent_btn_font_weight   || undefined,
    } : {};

    const showPattern = !adv || settings?.exit_intent_show_pattern !== false;

    return (
        <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
            <div
                className={`nx-exit-intent-popup nx-exit-intent-theme-one nx-exit-intent-${settings?.nx_id}`}
                style={popupStyle}
            >
                {showPattern && (
                    <div className="nx-exit-intent-pattern" aria-hidden="true">
                        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                            <polygon points="100,10 190,190 10,190" fill="none" stroke="currentColor" strokeWidth="18" strokeLinejoin="round"/>
                            <polygon points="100,55 158,170 42,170"  fill="none" stroke="currentColor" strokeWidth="12" strokeLinejoin="round"/>
                        </svg>
                    </div>
                )}

                {showClose && (
                    <button className="nx-exit-intent-close" onClick={handleClose} aria-label="Close">
                        &times;
                    </button>
                )}

                <div className="nx-exit-intent-body">
                    <h2 className="nx-exit-intent-title" style={titleStyle}>{title}</h2>
                    <p  className="nx-exit-intent-subtitle" style={subtitleStyle}>{subtitle}</p>

                    <form className="nx-exit-intent-form" onSubmit={handleSubmit}>
                        {showReason && (
                            <>
                                <p className="nx-exit-intent-question" style={questionStyle}>{question}</p>
                                <div className="nx-exit-intent-select-wrap">
                                    <select
                                        className="nx-exit-intent-select"
                                        style={inputStyle}
                                        value={reason}
                                        onChange={(e) => setReason(e.target.value)}
                                    >
                                        <option value="">{reasonPlaceholder}</option>
                                        {reasons.map((r, i) => (
                                            <option key={i} value={r.reason_label}>{r.reason_label}</option>
                                        ))}
                                    </select>
                                    <span className="nx-exit-intent-chevron" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M3 6l5 5 5-5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            </>
                        )}

                        {(showName || showEmail) && (
                            <div className={`nx-exit-intent-fields ${showName && showEmail ? 'nx-exit-intent-two-col' : ''}`}>
                                {showName && (
                                    <input
                                        type="text"
                                        className="nx-exit-intent-input"
                                        style={inputStyle}
                                        placeholder={namePlaceholder}
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                    />
                                )}
                                {showEmail && (
                                    <input
                                        type="email"
                                        className="nx-exit-intent-input"
                                        style={inputStyle}
                                        placeholder={emailPlaceholder}
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                    />
                                )}
                            </div>
                        )}

                        <button type="submit" className="nx-exit-intent-submit" style={btnStyle}>
                            {buttonText}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default ExitIntentPopup;
