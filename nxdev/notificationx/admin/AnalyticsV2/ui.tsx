import React, { useEffect, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { deltaInfo } from "./format";
import { Sparkline } from "./charts";

export const PRICING_URL = "https://notificationx.com/#pricing";

export const Delta = ({ value, unit = "pct", invert = false }: { value: number | null | undefined; unit?: "pct" | "pp"; invert?: boolean }) => {
    const info = deltaInfo(value, unit);
    if (!info) return null;
    const good = info.dir === "flat" ? "flat" : (info.dir === "up") !== invert ? "good" : "bad";
    return (
        <span className={`nxa-delta nxa-delta-${good}`} title={__("Compared with the previous period of the same length", "notificationx")}>
            <span aria-hidden="true">{info.dir === "up" ? "▲" : info.dir === "down" ? "▼" : "•"}</span> {info.text}
        </span>
    );
};

export type Tone = "brand" | "mint" | "blue" | "pink" | "amber" | "slate";

const ICONS: Record<string, string> = {
    eye: "M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z",
    click: "M9 3l10 9-4.5 1.2L17 19l-2.5 1.2-2.5-5.6L9 18z",
    percent: "M19 5L5 19M7 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z",
    close: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9 9l6 6M15 9l-6 6",
    send: "M21 3L10 14M21 3l-6.5 18-4-8-8-4z",
    sparkle: "M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z",
    compass: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM15.5 8.5l-2 5-5 2 2-5z",
    devices: "M2 5h14v10H2zM6 19h6M18 9h4v11h-6v-3",
    globe: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3.5 9h17M3.5 15h17M12 3c2.5 2.7 3.5 5.7 3.5 9s-1 6.3-3.5 9c-2.5-2.7-3.5-5.7-3.5-9s1-6.3 3.5-9z",
    page: "M6 3h8l4 4v14H6zM14 3v4h4M9 13h6M9 17h4",
    link: "M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1",
    trend: "M3 17l6-6 4 4 8-8M15 7h6v6",
    search: "M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4.3-4.3",
    share: "M18 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8.6 13.5l6.8 4M15.4 6.5l-6.8 4",
    mail: "M4 6h16v12H4zM4 7l8 6 8-6",
    arrow: "M5 12h14M13 6l6 6-6 6",
    home: "M3 11l9-7 9 7v9H3zM9 20v-6h6v6",
    layers: "M12 3l9 5-9 5-9-5zM3 13l9 5 9-5",
    trophy: "M8 4h8v5a4 4 0 0 1-8 0zM8 6H4a3 3 0 0 0 4 4M16 6h4a3 3 0 0 1-4 4M12 13v4M8 21h8M10 17h4",
    check: "M5 12l5 5 9-10",
    hover: "M12 3a6 6 0 0 1 6 6v6a6 6 0 0 1-12 0V9a6 6 0 0 1 6-6zM12 7v3",
    download: "M12 4v11M7 10l5 5 5-5M5 20h14",
    moon: "M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z",
    sun: "M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4",
    info: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8h.01",
    leads: "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6",
};

/** Small info icon with a tooltip on hover and keyboard focus. */
export const InfoTip = ({ text }: { text: string }) => (
    <span className="nxa-info" tabIndex={0} role="note" aria-label={text}>
        <Icon name="info" size={14} />
        <span className="nxa-info-tip" aria-hidden="true">{text}</span>
    </span>
);

export const Icon = ({ name, size = 18 }: { name: string; size?: number }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d={ICONS[name] || ICONS.trend} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" />
    </svg>
);

/** Tinted square holding an icon; the tone sets both tint and icon color. */
export const Chip = ({ icon, tone = "brand", size = 18 }: { icon: string; tone?: Tone; size?: number }) => (
    <span className={`nxa-chip tone-${tone}`} aria-hidden="true"><Icon name={icon} size={size} /></span>
);

/** Round initial in a solid color, for sources and assistants without a logo. */
export const Avatar = ({ text, color }: { text: string; color?: string }) => (
    <span className="nxa-avatar" style={color ? { background: color } : undefined} aria-hidden="true">{(text || "?").replace(/^www\./, "").charAt(0).toUpperCase()}</span>
);

/** CTR as a pill; strong rates stand out, zero stays quiet. */
export const CtrPill = ({ value }: { value: number }) => (
    <span className={`nxa-pill ${value >= 10 ? "is-high" : value > 0 ? "is-mid" : "is-zero"}`}>{(Number(value) || 0).toFixed(1)}%</span>
);

export const Card = ({ title, action, children, className = "", icon, tone, subtitle }: { title?: React.ReactNode; action?: React.ReactNode; children: React.ReactNode; className?: string; icon?: string; tone?: Tone; subtitle?: React.ReactNode }) => (
    <section className={`nxa-card ${className}`}>
        {(title || action) && (
            <header className="nxa-card-head">
                <div className="nxa-card-title">
                    {icon && <Chip icon={icon} tone={tone} size={16} />}
                    <div>
                        {title && <h3>{title}</h3>}
                        {subtitle && <p className="nxa-card-sub">{subtitle}</p>}
                    </div>
                </div>
                {action}
            </header>
        )}
        <div className="nxa-card-body">{children}</div>
    </section>
);

/**
 * KPI tile. With `spark` it shows the trend; with `meter` (0–100) a slim
 * gauge, so rate tiles carry a visual too instead of a lone number.
 */
export const KpiCard = ({ label, value, delta, deltaUnit, spark, sparkColor, hint, invert, icon, tone = "brand", meter, foot }: any) => (
    <div className={`nxa-kpi tone-${tone}`}>
        <div className="nxa-kpi-top">
            <div className="nxa-kpi-label"><span className="nxa-kpi-label-text">{label}</span>{hint && <InfoTip text={hint} />}</div>
            {icon && <Chip icon={icon} tone={tone} size={16} />}
        </div>
        <div className="nxa-kpi-row">
            <div>
                <div className="nxa-kpi-value nxa-num">{value}</div>
                <Delta value={delta} unit={deltaUnit} invert={invert} />
            </div>
            {spark && <Sparkline values={spark} color={sparkColor} />}
        </div>
        {meter !== undefined && (
            <div className="nxa-kpi-meter" role="presentation"><span style={{ width: `${Math.max(0, Math.min(100, meter))}%` }} /></div>
        )}
        {foot && <div className="nxa-kpi-foot">{foot}</div>}
    </div>
);

export const Skeleton = ({ height = 16, width = "100%", radius = 8 }: { height?: number; width?: string | number; radius?: number }) => (
    <span className="nxa-skeleton" style={{ height, width, borderRadius: radius }} aria-hidden="true" />
);

export const EmptyState = ({ title, text, action }: { title: string; text?: string; action?: React.ReactNode }) => (
    <div className="nxa-empty">
        <svg width="56" height="56" viewBox="0 0 56 56" aria-hidden="true">
            <rect x="6" y="10" width="44" height="34" rx="8" fill="var(--nxa-brand-soft)" />
            <path d="M14 36l9-10 7 6 12-14" fill="none" stroke="var(--nxa-brand)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        <h4>{title}</h4>
        {text && <p>{text}</p>}
        {action}
    </div>
);

export const ErrorState = ({ onRetry }: { onRetry: () => void }) => (
    <div className="nxa-empty nxa-error" role="alert">
        <h4>{__("Couldn't load this report", "notificationx")}</h4>
        <p>{__("Check your connection and try again.", "notificationx")}</p>
        <button type="button" className="nxa-btn" onClick={onRetry}>{__("Try again", "notificationx")}</button>
    </div>
);

/** The plugin's Pro badge (the crown used across NotificationX), with a text label for screen readers. */
export const ProPill = () => (
    <span className="nxa-pro-pill" title={__("Pro feature", "notificationx")}>
        <span className="nxa-sr-only">{__("Pro", "notificationx")}</span>
    </span>
);

/**
 * A control that is locked in Free: clicking it explains what Pro adds and
 * links to pricing, instead of doing nothing.
 */
export const ProLocked = ({ children, feature, text }: { children: React.ReactNode; feature: string; text: string }) => {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (!open) return;
        const close = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false); };
        const esc = (e: KeyboardEvent) => { if (e.key === "Escape") setOpen(false); };
        document.addEventListener("mousedown", close);
        document.addEventListener("keydown", esc);
        return () => { document.removeEventListener("mousedown", close); document.removeEventListener("keydown", esc); };
    }, [open]);
    return (
        <div className="nxa-locked" ref={ref}>
            <button type="button" className="nxa-btn nxa-btn-ghost" aria-expanded={open} onClick={() => setOpen(!open)}>
                {children} <ProPill />
            </button>
            {open && (
                <div className="nxa-popover" role="dialog" aria-label={feature}>
                    <strong>{feature}</strong>
                    <p>{text}</p>
                    <a className="nxa-btn nxa-btn-primary" href={PRICING_URL} target="_blank" rel="noopener noreferrer">{__("Upgrade to Pro", "notificationx")}</a>
                </div>
            )}
        </div>
    );
};

export const TypeBadge = ({ label }: { label: string }) => <span className="nxa-badge">{label}</span>;

export const StatusDot = ({ enabled }: { enabled: boolean }) => (
    <span className={`nxa-status ${enabled ? "is-on" : "is-off"}`} title={enabled ? __("Enabled", "notificationx") : __("Disabled", "notificationx")} />
);

/**
 * What the chart shows: "All" (the default lines) or one metric. Each
 * option always gives the same chart — clicking it twice changes nothing —
 * because on/off toggles that flip on every click were confusing.
 */
export const LegendToggle = ({ items, focus, onFocus, defaultHidden = [] }: { items: { key: string; label: string; color: string; value?: React.ReactNode }[]; focus: string | null; onFocus: (key: string | null) => void; defaultHidden?: string[] }) => (
    <div className="nxa-series-picker" role="radiogroup" aria-label={__("Show on chart", "notificationx")}>
        <button type="button" role="radio" aria-checked={!focus} className={!focus ? "is-active" : ""} onClick={() => onFocus(null)}>
            <span className="nxa-series-dots" aria-hidden="true">
                {items.filter((i) => !defaultHidden.includes(i.key)).map((i) => <i key={i.key} style={{ background: i.color }} />)}
            </span>
            {__("All", "notificationx")}
        </button>
        {items.map((item) => (
            <button type="button" role="radio" key={item.key} aria-checked={focus === item.key} className={focus === item.key ? "is-active" : ""} onClick={() => onFocus(item.key)}>
                <i style={{ background: item.color }} aria-hidden="true" />
                {item.label}
                {item.value !== undefined && <strong className="nxa-num">{item.value}</strong>}
            </button>
        ))}
    </div>
);

/** Which single metric the chart is focused on (null = the default lines). */
export const useChartFocus = () => React.useState<string | null>(null);

/**
 * Series to draw: the focused one alone (scaled to itself, so small numbers
 * stay readable), or every series with the default-hidden ones switched off.
 */
export const chartSeries = <T extends { key: string }>(all: T[], focus: string | null, defaultHidden: string[] = []) =>
    focus ? { series: all.filter((s) => s.key === focus), hidden: [] as string[] } : { series: all.filter((s) => !defaultHidden.includes(s.key)), hidden: [] as string[] };

/** Small modal for destructive actions. Focuses Cancel; Esc cancels. */
export const ConfirmDialog = ({ title, text, confirmLabel, busy, onConfirm, onCancel }: { title: string; text: React.ReactNode; confirmLabel: string; busy?: boolean; onConfirm: () => void; onCancel: () => void }) => {
    const cancelRef = React.useRef<HTMLButtonElement>(null);
    React.useEffect(() => {
        cancelRef.current?.focus();
        const esc = (e: KeyboardEvent) => e.key === "Escape" && onCancel();
        document.addEventListener("keydown", esc);
        return () => document.removeEventListener("keydown", esc);
    }, []);
    return (
        <div className="nxa-modal-layer" onMouseDown={(e) => e.target === e.currentTarget && !busy && onCancel()}>
            <div className="nxa-modal" role="alertdialog" aria-modal="true" aria-labelledby="nxa-modal-title" aria-describedby="nxa-modal-text">
                <h3 id="nxa-modal-title">{title}</h3>
                <div id="nxa-modal-text" className="nxa-modal-text">{text}</div>
                <div className="nxa-modal-actions">
                    <button type="button" className="nxa-btn nxa-btn-ghost" ref={cancelRef} onClick={onCancel} disabled={busy}>{__("Cancel", "notificationx")}</button>
                    <button type="button" className="nxa-btn nxa-btn-danger" onClick={onConfirm} disabled={busy}>{busy ? __("Resetting…", "notificationx") : confirmLabel}</button>
                </div>
            </div>
        </div>
    );
};
