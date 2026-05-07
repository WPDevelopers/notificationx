import React, { useState, useEffect, useRef } from 'react';
import nxHelper from './functions';

const useCountdown = (endDateStr: string, fallbackDurationMs?: number) => {
    const fallbackEndRef = useRef<number | null>(null);
    if (!endDateStr && fallbackDurationMs && fallbackEndRef.current === null) {
        fallbackEndRef.current = Date.now() + fallbackDurationMs;
    }

    const calc = () => {
        let end: number | null = null;
        if (endDateStr) {
            const parsed = Date.parse(endDateStr) || Date.parse(endDateStr.replace(' ', 'T'));
            if (parsed && !isNaN(parsed)) end = parsed;
        } else if (fallbackEndRef.current) {
            end = fallbackEndRef.current;
        }
        if (end === null) return null;
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
        if (!endDateStr && !fallbackDurationMs) return;
        setTimeLeft(calc());
        const timer = setInterval(() => setTimeLeft(calc()), 1000);
        return () => clearInterval(timer);
    }, [endDateStr, fallbackDurationMs]);
    return timeLeft;
};

const pad = (n: number) => String(n).padStart(2, '0');
const px = (v: any): string | undefined => (v || v === 0) ? `${v}px` : undefined;

const ExitIntentPopup = (props: any) => {
    const { nxExitIntent, dispatch, rest } = props;
    const { config: settings }             = nxExitIntent;
    const [isVisible, setIsVisible]        = useState(true);
    const [name, setName]                  = useState('');
    const [email, setEmail]                = useState('');
    const [message, setMessage]            = useState('');
    const [submitting, setSubmitting]      = useState(false);
    const [submitted, setSubmitted]        = useState(false);
    const [videoPlaying, setVideoPlaying]  = useState(false);

    const theme     = settings?.themes?.replace(`${settings?.source}_`, '') || 'theme-one';
    const showClose = settings?.show_close_button !== false;
    const adv       = !!settings?.advance_edit;
    const s         = settings || {};

    // Theme-five fallback duration so timer ticks even without an end date.
    const t5FallbackMs = ((1 * 24 + 14) * 3600 + 30 * 60 + 26) * 1000;
    const fallbackDuration = theme === 'theme-five' ? t5FallbackMs : undefined;
    const timeLeft = useCountdown(s.exit_intent_countdown_end || '', fallbackDuration);

    const handleClose = () => {
        const _theme = settings?.themes || '';
        sessionStorage.setItem(`notificationx_exit_intent_${settings?.nx_id}_${_theme}`, 'closed');
        setIsVisible(false);
        dispatch?.({ type: 'REMOVE_NOTIFICATION', payload: nxExitIntent.id });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (submitting) return;

        if (!rest) {
            handleClose();
            return;
        }

        setSubmitting(true);
        try {
            const _showName    = s.exit_intent_show_name    !== false;
            const _showEmail   = s.exit_intent_show_email   !== false;
            const _showMessage = s.exit_intent_show_message === true;

            const payload: Record<string, any> = {
                nx_id: String(settings?.nx_id || ''),
                theme: settings?.themes        || '',
                title: s.exit_intent_title || '',
            };

            if (_showName    && name)    payload.name    = name;
            if (_showEmail   && email)   payload.email   = email;
            if (_showMessage && message) payload.message = message;

            const submitUrl = nxHelper.getPath(rest, 'popup-submit');
            await nxHelper.post(submitUrl, payload, { credentials: 'same-origin' });

            setSubmitted(true);
            setTimeout(() => handleClose(), 2500);
        } catch {
            handleClose();
        } finally {
            setSubmitting(false);
        }
    };

    if (!isVisible) return null;

    // Shared close button style — used by every theme.
    const closeStyle: React.CSSProperties = adv ? {
        color:    s.exit_intent_close_color || undefined,
        fontSize: px(s.exit_intent_close_size),
    } : {};

    // ─── Theme Four ───────────────────────────────────────────────────────────
    if (theme === 'theme-four') {
        const badge    = s.exit_intent_t4_badge    || 'Before you go...';
        const title    = s.exit_intent_t4_title    || 'Watch this short demo video';
        const subtitle = s.exit_intent_t4_subtitle || 'See how our product simplifies your workflow.';
        const imageUrl = s.exit_intent_image_url?.url || s.exit_intent_image_url || '';

        const rawVideoUrl = s.exit_intent_t4_video_url;
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

        const overlayStyle: React.CSSProperties = adv
            ? { background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)' } : {};
        const popupStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t4_bg_color   || undefined,
            borderRadius: px(s.exit_intent_t4_border_radius),
            maxWidth:     px(s.exit_intent_t4_max_width),
        } : {};
        const badgeStyle: React.CSSProperties = adv ? {
            background: s.exit_intent_t4_badge_bg    || undefined,
            color:      s.exit_intent_t4_badge_color || undefined,
            fontSize:   px(s.exit_intent_t4_badge_font_size),
        } : {};
        const titleStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t4_title_color       || undefined,
            fontSize:   px(s.exit_intent_t4_title_font_size),
            fontWeight: s.exit_intent_t4_title_font_weight || undefined,
        } : {};
        const subtitleStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t4_subtitle_color || undefined,
            fontSize: px(s.exit_intent_t4_subtitle_font_size),
        } : {};
        const videoWrapStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t4_video_bg || undefined,
            borderRadius: px(s.exit_intent_t4_video_radius),
        } : {};
        const playIconStyle: React.CSSProperties = adv ? {
            background: s.exit_intent_t4_play_bg || undefined,
        } : {};
        const playFill = adv ? (s.exit_intent_t4_play_color || '#1a1a2e') : '#1a1a2e';

        return (
            <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-four nx-exit-intent-${settings?.nx_id}`}
                    style={popupStyle}
                >
                    {showClose && (
                        <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">
                            &times;
                        </button>
                    )}

                    <span className="nx-exit-intent-t4-badge" style={badgeStyle}>{badge}</span>
                    <h2 className="nx-exit-intent-t4-title" style={titleStyle}>{title}</h2>
                    {subtitle && <p className="nx-exit-intent-t4-subtitle" style={subtitleStyle}>{subtitle}</p>}

                    {videoPlaying && videoUrl ? (
                        <iframe
                            className="nx-exit-intent-t4-iframe"
                            src={getEmbedUrl(videoUrl)}
                            allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                            allowFullScreen
                            title="Video"
                            style={{ borderRadius: px(s.exit_intent_t4_video_radius) }}
                        />
                    ) : (
                        <div
                            className={`nx-exit-intent-t4-video-wrap${!imageUrl ? ' nx-exit-intent-t4-no-image' : ''}`}
                            style={videoWrapStyle}
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
                                    <div className="nx-exit-intent-t4-play-icon" style={playIconStyle}>
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <polygon points="5,3 19,12 5,21" fill={playFill}/>
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

    // ─── Theme Five ───────────────────────────────────────────────────────────
    if (theme === 'theme-five') {
        const t5Title        = s.exit_intent_t5_title           || 'Flash Sale';
        const t5Headline     = s.exit_intent_t5_headline        || '50% OFF';
        const t5Desc         = s.exit_intent_t5_desc            || 'ON ENTIRE ORDER';
        const showTimer      = s.exit_intent_t5_show_timer      !== false;
        const countdownLabel = s.exit_intent_t5_countdown_label || 'LIMITED-TIME OFFER! SALE ENDS IN';
        const daysLbl        = s.exit_intent_t5_days_label      || 'DAYS';
        const hoursLbl       = s.exit_intent_t5_hours_label     || 'HRS';
        const minutesLbl     = s.exit_intent_t5_minutes_label   || 'MIN';
        const secondsLbl     = s.exit_intent_t5_seconds_label   || 'SEC';
        const timerBg        = s.exit_intent_t5_timer_bg        || '#fff0f5';
        const timerColor     = s.exit_intent_t5_timer_color     || '#e91e63';
        const buttonText     = s.exit_intent_button_text        || 'Shop The Flash Sale Now';
        const dismissText    = s.exit_intent_dismiss_text       || 'NO, THANKS!';
        const imageUrl       = s.exit_intent_image_url?.url || s.exit_intent_image_url || '';

        const overlayStyle: React.CSSProperties = adv
            ? { background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.6)' } : {};
        const popupStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t5_bg_color   || undefined,
            borderRadius: px(s.exit_intent_t5_border_radius),
            maxWidth:     px(s.exit_intent_t5_max_width),
        } : {};
        const titleStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t5_title_color       || undefined,
            fontSize:   px(s.exit_intent_t5_title_font_size),
            fontWeight: s.exit_intent_t5_title_font_weight || undefined,
        } : {};
        const headlineStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t5_headline_color       || undefined,
            fontSize:   px(s.exit_intent_t5_headline_font_size),
            fontWeight: s.exit_intent_t5_headline_font_weight || undefined,
        } : {};
        const descStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t5_desc_color || undefined,
            fontSize: px(s.exit_intent_t5_desc_font_size),
        } : {};
        const cdLabelStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t5_cd_label_color || undefined,
            fontSize: px(s.exit_intent_t5_cd_label_font_size),
        } : {};
        const cdNumStyle: React.CSSProperties = {
            background:   adv ? (s.exit_intent_t5_cd_num_bg    || timerBg)    : timerBg,
            color:        adv ? (s.exit_intent_t5_cd_num_color || timerColor) : timerColor,
            fontSize:     adv ? px(s.exit_intent_t5_cd_num_font_size) : undefined,
            borderRadius: adv ? px(s.exit_intent_t5_cd_num_radius)    : undefined,
        };
        const cdUnitStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t5_cd_unit_color || undefined,
            fontSize: px(s.exit_intent_t5_cd_unit_font_size),
        } : {};
        const btnStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t5_btn_bg            || undefined,
            color:        s.exit_intent_t5_btn_color         || undefined,
            borderRadius: px(s.exit_intent_t5_btn_border_radius),
            fontSize:     px(s.exit_intent_t5_btn_font_size),
            fontWeight:   s.exit_intent_t5_btn_font_weight   || undefined,
        } : {};
        const dismissStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t5_dismiss_color || undefined,
            fontSize: px(s.exit_intent_t5_dismiss_font_size),
        } : {};

        const display = timeLeft || { days: 0, hours: 0, minutes: 0, seconds: 0 };
        const unitMeta: Array<{ key: 'days' | 'hours' | 'minutes' | 'seconds'; lbl: string }> = [
            { key: 'days',    lbl: daysLbl },
            { key: 'hours',   lbl: hoursLbl },
            { key: 'minutes', lbl: minutesLbl },
            { key: 'seconds', lbl: secondsLbl },
        ];

        return (
            <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-five nx-exit-intent-${settings?.nx_id}`}
                    style={popupStyle}
                >
                    {showClose && (
                        <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">
                            &times;
                        </button>
                    )}

                    <div className="nx-exit-intent-t5-left">
                        <span className="nx-exit-intent-t5-decor" aria-hidden="true" />

                        <h2 className="nx-exit-intent-t5-title" style={titleStyle}>{t5Title}</h2>
                        <div className="nx-exit-intent-t5-headline" style={headlineStyle}>{t5Headline}</div>
                        <p className="nx-exit-intent-t5-desc" style={descStyle}>{t5Desc}</p>

                        {showTimer && (
                            <>
                                <p className="nx-exit-intent-t5-countdown-label" style={cdLabelStyle}>{countdownLabel}</p>
                                <div className="nx-exit-intent-t5-countdown">
                                    {unitMeta.map(({ key, lbl }) => (
                                        <div key={key} className="nx-exit-intent-t5-countdown-unit">
                                            <span className="nx-exit-intent-t5-countdown-num" style={cdNumStyle}>
                                                {pad(display[key])}
                                            </span>
                                            <span className="nx-exit-intent-t5-countdown-lbl" style={cdUnitStyle}>{lbl}</span>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}

                        <button type="button" className="nx-exit-intent-t5-btn" style={btnStyle} onClick={handleClose}>
                            {buttonText}
                        </button>
                        <button type="button" className="nx-exit-intent-t5-dismiss" style={dismissStyle} onClick={handleClose}>
                            {dismissText}
                        </button>
                    </div>

                    <div
                        className="nx-exit-intent-t5-right"
                        style={imageUrl ? { backgroundImage: `url(${imageUrl})` } : undefined}
                    />
                </div>
            </div>
        );
    }

    // ─── Theme Two ────────────────────────────────────────────────────────────
    if (theme === 'theme-two') {
        const saleBadge    = s.exit_intent_sale_badge    || 'Flash Sale';
        const saleHeadline = s.exit_intent_sale_headline || '50% OFF';
        const saleDesc     = s.exit_intent_sale_desc     || 'ON ENTIRE ORDER';
        const buttonText   = s.exit_intent_button_text   || 'Shop The Flash Sale Now';
        const dismissText  = s.exit_intent_dismiss_text  || 'NO, THANKS!';
        const imageUrl     = s.exit_intent_image_url?.url || s.exit_intent_image_url || '';

        const overlayStyle: React.CSSProperties = adv
            ? { background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)' } : {};
        const popupStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t2_bg_color   || undefined,
            borderRadius: px(s.exit_intent_t2_border_radius),
            maxWidth:     px(s.exit_intent_t2_max_width),
        } : {};
        const badgeStyle: React.CSSProperties = adv ? {
            background: s.exit_intent_t2_badge_bg    || undefined,
            color:      s.exit_intent_t2_badge_color || undefined,
            fontSize:   px(s.exit_intent_t2_badge_font_size),
        } : {};
        const headlineStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t2_headline_color       || undefined,
            fontSize:   px(s.exit_intent_t2_headline_font_size),
            fontWeight: s.exit_intent_t2_headline_font_weight || undefined,
        } : {};
        const descStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t2_desc_color || undefined,
            fontSize: px(s.exit_intent_t2_desc_font_size),
        } : {};
        const btnStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t2_btn_bg            || undefined,
            color:        s.exit_intent_t2_btn_color         || undefined,
            borderRadius: px(s.exit_intent_t2_btn_border_radius),
            fontSize:     px(s.exit_intent_t2_btn_font_size),
            fontWeight:   s.exit_intent_t2_btn_font_weight   || undefined,
        } : {};
        const dismissStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t2_dismiss_color || undefined,
            fontSize: px(s.exit_intent_t2_dismiss_font_size),
        } : {};

        return (
            <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-two nx-exit-intent-${settings?.nx_id}`}
                    style={popupStyle}
                >
                    {showClose && (
                        <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">
                            &times;
                        </button>
                    )}

                    <div className="nx-exit-intent-t2-left">
                        <span className="nx-exit-intent-t2-badge" style={badgeStyle}>{saleBadge}</span>
                        <h2 className="nx-exit-intent-t2-headline" style={headlineStyle}>{saleHeadline}</h2>
                        <p className="nx-exit-intent-t2-desc" style={descStyle}>{saleDesc}</p>

                        <button type="button" className="nx-exit-intent-t2-btn" style={btnStyle} onClick={handleClose}>
                            {buttonText}
                        </button>
                        <button type="button" className="nx-exit-intent-t2-dismiss" style={dismissStyle} onClick={handleClose}>
                            {dismissText}
                        </button>
                    </div>

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
        const title       = s.exit_intent_t3_title       || "Wait, don't go!";
        const subtitle    = s.exit_intent_t3_subtitle    || 'Before you leave, we have a special offer just for you!';
        const offerText   = s.exit_intent_t3_offer       || 'Get 15% off your next purchase!';
        const couponText  = s.exit_intent_t3_coupon_text || "Use code STAY15 at checkout. Don't miss out on this limited-time offer.";
        const buttonText  = s.exit_intent_button_text    || 'Claim Offer';
        const dismissText = s.exit_intent_dismiss_text   || 'No, thanks!';
        const imageUrl    = s.exit_intent_image_url?.url || s.exit_intent_image_url || '';

        const overlayStyle: React.CSSProperties = adv
            ? { background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)' } : {};
        const popupStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t3_bg_color   || undefined,
            borderRadius: px(s.exit_intent_t3_border_radius),
            maxWidth:     px(s.exit_intent_t3_max_width),
        } : {};
        const titleStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t3_title_color       || undefined,
            fontSize:   px(s.exit_intent_t3_title_font_size),
            fontWeight: s.exit_intent_t3_title_font_weight || undefined,
        } : {};
        const subtitleStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t3_subtitle_color || undefined,
            fontSize: px(s.exit_intent_t3_subtitle_font_size),
        } : {};
        const offerStyle: React.CSSProperties = adv ? {
            color:      s.exit_intent_t3_offer_color       || undefined,
            fontSize:   px(s.exit_intent_t3_offer_font_size),
            fontWeight: s.exit_intent_t3_offer_font_weight || undefined,
        } : {};
        const couponStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t3_coupon_bg    || undefined,
            color:        s.exit_intent_t3_coupon_color || undefined,
            fontSize:     px(s.exit_intent_t3_coupon_font_size),
            borderRadius: px(s.exit_intent_t3_coupon_border_radius),
        } : {};
        const btnStyle: React.CSSProperties = adv ? {
            background:   s.exit_intent_t3_btn_bg            || undefined,
            color:        s.exit_intent_t3_btn_color         || undefined,
            borderRadius: px(s.exit_intent_t3_btn_border_radius),
            fontSize:     px(s.exit_intent_t3_btn_font_size),
            fontWeight:   s.exit_intent_t3_btn_font_weight   || undefined,
        } : {};
        const dismissStyle: React.CSSProperties = adv ? {
            color:    s.exit_intent_t3_dismiss_color || undefined,
            fontSize: px(s.exit_intent_t3_dismiss_font_size),
        } : {};

        return (
            <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
                <div
                    className={`nx-exit-intent-popup nx-exit-intent-theme-three nx-exit-intent-${settings?.nx_id}`}
                    style={popupStyle}
                >
                    {imageUrl && (
                        <div className="nx-exit-intent-t3-character" aria-hidden="true">
                            <img src={imageUrl} alt="" />
                        </div>
                    )}

                    <div className="nx-exit-intent-t3-body">
                        {showClose && (
                            <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">
                                &times;
                            </button>
                        )}
                        <h2 className="nx-exit-intent-t3-title" style={titleStyle}>{title}</h2>
                        <p  className="nx-exit-intent-t3-subtitle" style={subtitleStyle}>{subtitle}</p>
                        <p  className="nx-exit-intent-t3-offer" style={offerStyle}>{offerText}</p>
                        <p  className="nx-exit-intent-t3-coupon-text" style={couponStyle}>{couponText}</p>
                        <button type="button" className="nx-exit-intent-t3-btn" style={btnStyle} onClick={handleClose}>
                            {buttonText}
                        </button>
                        <button type="button" className="nx-exit-intent-t3-dismiss" style={dismissStyle} onClick={handleClose}>
                            {dismissText}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // ─── Theme One (default) ──────────────────────────────────────────────────
    const title       = s.exit_intent_title    || 'Wait! Before You Go...';
    const subtitle    = s.exit_intent_subtitle || "We'd love to understand what's holding you back";
    const buttonText  = s.exit_intent_button_text || 'SUBMIT';
    const showName    = s.exit_intent_show_name  !== false;
    const showEmail   = s.exit_intent_show_email !== false;
    const showMessage = s.exit_intent_show_message === true;
    const namePlaceholder    = s.exit_intent_name_label          || 'Name *';
    const emailPlaceholder   = s.exit_intent_email_label         || 'Enter Your Email *';
    const messagePlaceholder = s.exit_intent_message_placeholder || 'Your message...';

    const popupStyle: React.CSSProperties = adv ? {
        background:   s.exit_intent_bg_color      || '#EDE7FF',
        borderRadius: px(s.exit_intent_border_radius),
        maxWidth:     px(s.exit_intent_max_width),
    } : {};
    const overlayStyle: React.CSSProperties = adv ? {
        background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)',
    } : {};
    const patternStyle: React.CSSProperties = adv ? {
        color: s.exit_intent_pattern_color || undefined,
    } : {};
    const titleStyle: React.CSSProperties = adv ? {
        color:      s.exit_intent_title_color       || undefined,
        fontSize:   px(s.exit_intent_title_font_size),
        fontWeight: s.exit_intent_title_font_weight || undefined,
    } : {};
    const subtitleStyle: React.CSSProperties = adv ? {
        color:    s.exit_intent_subtitle_color || undefined,
        fontSize: px(s.exit_intent_subtitle_font_size),
    } : {};
    const inputStyle: React.CSSProperties = adv ? {
        background:   s.exit_intent_input_bg              || undefined,
        borderColor:  s.exit_intent_input_border_color    || undefined,
        borderRadius: px(s.exit_intent_input_border_radius),
        color:        s.exit_intent_input_text_color      || undefined,
    } : {};
    const btnStyle: React.CSSProperties = adv ? {
        background:   s.exit_intent_btn_bg            || undefined,
        color:        s.exit_intent_btn_color         || undefined,
        borderRadius: px(s.exit_intent_btn_border_radius),
        fontSize:     px(s.exit_intent_btn_font_size),
        fontWeight:   s.exit_intent_btn_font_weight   || undefined,
    } : {};

    const showPattern = !adv || s.exit_intent_show_pattern !== false;

    return (
        <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
            <div
                className={`nx-exit-intent-popup nx-exit-intent-theme-one nx-exit-intent-${settings?.nx_id}`}
                style={popupStyle}
            >
                {showPattern && (
                    <div className="nx-exit-intent-pattern" style={patternStyle} aria-hidden="true">
                        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                            <polygon points="100,10 190,190 10,190" fill="none" stroke="currentColor" strokeWidth="18" strokeLinejoin="round"/>
                            <polygon points="100,55 158,170 42,170"  fill="none" stroke="currentColor" strokeWidth="12" strokeLinejoin="round"/>
                        </svg>
                    </div>
                )}

                {showClose && (
                    <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">
                        &times;
                    </button>
                )}

                <div className="nx-exit-intent-body">
                    <h2 className="nx-exit-intent-title" style={titleStyle}>{title}</h2>
                    <p  className="nx-exit-intent-subtitle" style={subtitleStyle}>{subtitle}</p>

                    {submitted ? (
                        <div className="nx-exit-intent-success">
                            <div className="nx-exit-intent-success-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 13l4 4L19 7" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                            </div>
                            <p className="nx-exit-intent-success-text">Thank you! We received your message.</p>
                        </div>
                    ) : (
                        <form className="nx-exit-intent-form" onSubmit={handleSubmit}>
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

                            {showMessage && (
                                <textarea
                                    className="nx-exit-intent-input nx-exit-intent-textarea"
                                    style={inputStyle}
                                    placeholder={messagePlaceholder}
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                />
                            )}

                            <button
                                type="submit"
                                className="nx-exit-intent-submit"
                                style={btnStyle}
                                disabled={submitting}
                            >
                                {submitting ? '...' : buttonText}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ExitIntentPopup;
