import React, { useEffect, useMemo, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import { toBase64 } from 'js-base64';
import NxIcon from './NxIcon';

// The front-end preview (Preview.php) renders at this size; the widget then
// zooms in on the notification itself.
const VIEWPORT = { width: 1280, height: 720 };
const NOTIFICATION_SELECTOR = '.nx-notification, .nx-popup, .nx-exit-intent-popup, .nx-gdpr-card, .nx-bar';
const PADDING = 16;
const MAX_STAGE_HEIGHT = 320;
const REFRESH_DELAY = 700;

// Still mode: show the notification at once, without animation, and keep it
// on screen while the settings are edited.
const STILL_SETTINGS = {
    delay_before: 0.1,
    display_for: 86400,
    delay_between: 0.1,
    cookie_visibility_delay_before: 0.1,
    animation_notification_show: 'default',
    animation_notification_hide: 'default',
};

// Types without a notification box to show here: the Notification Bar has its
// own full-width preview, and Flashing Tab changes the browser tab.
const UNSUPPORTED_TYPES = ['notification_bar', 'flashing_tab'];

type Box = { x: number; y: number; width: number; height: number };

const findNotification = (frame: HTMLIFrameElement | null): Box | null => {
    try {
        const doc = frame?.contentDocument;
        if (!doc) {
            return null;
        }
        const el = Array.from(doc.querySelectorAll(NOTIFICATION_SELECTOR)).find((node) => {
            const rect = node.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        });
        if (!el) {
            return null;
        }
        // Include parts that stick out of the box (tabs, badges, close buttons).
        let { left, top, right, bottom } = el.getBoundingClientRect();
        el.querySelectorAll('*').forEach((child) => {
            const rect = child.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                left = Math.min(left, rect.left);
                top = Math.min(top, rect.top);
                right = Math.max(right, rect.right);
                bottom = Math.max(bottom, rect.bottom);
            }
        });
        const x = Math.max(Math.floor(left) - PADDING, 0);
        const y = Math.max(Math.floor(top) - PADDING, 0);
        const width = Math.min(Math.ceil(right) + PADDING, VIEWPORT.width) - x;
        const height = Math.min(Math.ceil(bottom) + PADDING, VIEWPORT.height) - y;
        // Still outside the page (sliding in).
        if (width <= PADDING * 2 || height <= PADDING * 2) {
            return null;
        }
        return { x, y, width, height };
    } catch (e) {
        return null;
    }
};

/**
 * Sidebar preview of the selected notification. It shows the notification
 * still and updates as the settings change; Play runs it with the timing and
 * animation settings.
 */
const PreviewWidget = () => {
    const context = useBuilderContext();
    const values = context?.values || {};
    const activeStep = context?.config?.active;
    const previewConfig = context?.config?.content_heading?.preview || (window as any).notificationxTabs?.config?.content_heading?.preview;
    const urls = previewConfig?.urls || {};
    const needsOwnPage = values.type === 'inline' || values.source === 'woocommerce_sales_inline';
    const url = urls[values.source] || (needsOwnPage ? '' : urls.default);
    const isShown = !!activeStep && activeStep !== 'source_tab' && !!values.source && !!url && !UNSUPPORTED_TYPES.includes(values.type);

    const stageRef = useRef<HTMLDivElement>(null);
    const frameRefs = [useRef<HTMLIFrameElement>(null), useRef<HTMLIFrameElement>(null)];
    const frameNames = useRef([0, 1].map((i) => `nx-preview-widget-${i}-${Math.random().toString(36).slice(2)}`));
    const pendingFrame = useRef<number | null>(null);
    const lastPayload = useRef('');
    const measureTimer = useRef<number>();

    const [visibleFrame, setVisibleFrame] = useState(0);
    const [box, setBox] = useState<Box | null>(null);
    const [stageWidth, setStageWidth] = useState(0);
    const [playing, setPlaying] = useState(false);
    const [loading, setLoading] = useState(false);

    // Settings changes show in still mode; playing again picks them up.
    const settingsKey = useMemo(() => JSON.stringify(values), [values]);
    const payload = useMemo(() => {
        const settings = playing ? { ...values } : { ...values, ...STILL_SETTINGS };
        return toBase64(JSON.stringify({ ...settings, previewType: 'desktop', nx_preview_isolated: true }));
    }, [settingsKey, playing]);

    useEffect(() => {
        setPlaying(false);
    }, [settingsKey]);

    useEffect(() => {
        const el = stageRef.current;
        if (!el) {
            return;
        }
        setStageWidth(el.clientWidth);
        if (typeof ResizeObserver === 'undefined') {
            return;
        }
        const observer = new ResizeObserver(([entry]) => setStageWidth(entry.contentRect.width));
        observer.observe(el);
        return () => observer.disconnect();
    }, [isShown]);

    // Load the new settings in the hidden frame; it is swapped in once loaded.
    useEffect(() => {
        if (!isShown || payload === lastPayload.current) {
            return;
        }
        const timer = window.setTimeout(() => {
            const target = lastPayload.current ? 1 - visibleFrame : visibleFrame;
            const frame = frameRefs[target].current;
            if (!frame) {
                return;
            }
            lastPayload.current = payload;
            pendingFrame.current = target;
            setLoading(true);

            const form = document.createElement('form');
            form.action = url;
            form.method = 'post';
            form.target = frameNames.current[target];
            const fields = {
                'nx-preview': payload,
                // Without it the front end ignores the payload (Preview::is_authorized_preview_request()).
                'nx-preview-nonce': (window as any).notificationxTabs?.preview_nonce || '',
            };
            Object.keys(fields).forEach((key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }, lastPayload.current && !playing ? REFRESH_DELAY : 0);
        return () => window.clearTimeout(timer);
    }, [isShown, payload, url]);

    // Hidden while on Source: load again when the preview comes back.
    useEffect(() => {
        if (!isShown) {
            lastPayload.current = '';
            pendingFrame.current = null;
            setPlaying(false);
        }
    }, [isShown]);

    useEffect(() => () => window.clearTimeout(measureTimer.current), []);

    const onFrameLoad = (index: number) => {
        if (pendingFrame.current !== index) {
            return;
        }
        try {
            // The empty frame fires `load` too; wait for the preview page.
            if (frameRefs[index].current?.contentWindow?.location.href === 'about:blank') {
                return;
            }
        } catch (e) {
            // Not readable: let the timeout below swap it in.
        }
        const startedAt = Date.now();
        const isPlaying = playing;
        let previous: Box | null = null;
        window.clearTimeout(measureTimer.current);
        const show = (found: Box | null) => {
            if (found && (!isPlaying || !box)) {
                setBox(found);
            }
            pendingFrame.current = null;
            setVisibleFrame(index);
            setLoading(false);
        };
        // The runtime renders after the page loads and slides the notification
        // in; measure once it has stopped moving.
        const measure = () => {
            const found = findNotification(frameRefs[index].current);
            const settled = !!found && !!previous && found.x === previous.x && found.y === previous.y
                && found.width === previous.width && found.height === previous.height;
            if (isPlaying || settled) {
                show(found);
                return;
            }
            if (Date.now() - startedAt > 4000) {
                show(found);
                return;
            }
            previous = found;
            measureTimer.current = window.setTimeout(measure, 150);
        };
        measure();
    };

    if (!isShown) {
        return null;
    }

    const area = box || { x: 0, y: 0, width: VIEWPORT.width, height: VIEWPORT.height };
    const scale = stageWidth ? Math.min(stageWidth / area.width, MAX_STAGE_HEIGHT / area.height, 1) : 0;
    const stageHeight = scale ? Math.round(area.height * scale) : 160;
    const offsetX = (stageWidth - area.width * scale) / 2;

    return (
        <div className="sidebar-widget nx-widget nx-preview-widget">
            <div className="nx-widget-title">
                <h4>{__('Preview', 'notificationx')}</h4>
                <div className="nx-preview-widget__actions">
                    {playing && <span className="nx-preview-widget__status">{__('Playing', 'notificationx')}</span>}
                    <button
                        type="button"
                        className={`nx-preview-widget__play${playing ? ' is-playing' : ''}`}
                        onClick={() => setPlaying(!playing)}
                        title={playing ? __('Stop', 'notificationx') : __('Play', 'notificationx')}
                        aria-label={playing ? __('Stop', 'notificationx') : __('Play', 'notificationx')}
                        aria-pressed={playing}
                    >
                        <NxIcon name={playing ? 'stop' : 'play'} size={16} strokeWidth={2} />
                    </button>
                </div>
            </div>
            <div className="nx-widget-content">
                <div
                    ref={stageRef}
                    className={`nx-preview-widget__stage${loading ? ' is-loading' : ''}`}
                    style={{ height: stageHeight }}
                >
                    {[0, 1].map((index) => (
                        <div
                            key={index}
                            className={`nx-preview-widget__screen${index === visibleFrame ? ' is-visible' : ''}`}
                            style={{
                                width: VIEWPORT.width,
                                height: VIEWPORT.height,
                                transform: `translate(${offsetX - area.x * scale}px, ${-area.y * scale}px) scale(${scale || 0.2})`,
                            }}
                            aria-hidden={index !== visibleFrame}
                        >
                            <iframe
                                ref={frameRefs[index]}
                                name={frameNames.current[index]}
                                title={__('Preview', 'notificationx')}
                                width={VIEWPORT.width}
                                height={VIEWPORT.height}
                                tabIndex={-1}
                                onLoad={() => onFrameLoad(index)}
                            />
                        </div>
                    ))}
                    <span className="nx-preview-widget__spinner" aria-hidden="true" />
                </div>
                <p className="nx-preview-widget__hint">
                    {playing
                        ? __('Showing it with your timing and animation settings.', 'notificationx')
                        : __('Changes show here as you edit. Press play to see it in action.', 'notificationx')}
                </p>
            </div>
        </div>
    );
};

export default PreviewWidget;
