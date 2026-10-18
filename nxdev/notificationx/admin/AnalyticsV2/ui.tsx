import React from "react";
import { __ } from "@wordpress/i18n";

export const PRICING_URL = "https://notificationx.com/#pricing";

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

export const Icon = ({ name, size = 18 }: { name: string; size?: number }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d={ICONS[name] || ICONS.trend} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" />
    </svg>
);

/** Tinted square holding an icon; the tone sets both tint and icon color. */
export const Chip = ({ icon, tone = "brand", size = 18 }: { icon: string; tone?: Tone; size?: number }) => (
    <span className={`nxa-chip tone-${tone}`} aria-hidden="true"><Icon name={icon} size={size} /></span>
);

/** The plugin's Pro badge (the crown used across NotificationX), with a text label for screen readers. */
export const ProPill = () => (
    <span className="nxa-pro-pill" title={__("Pro feature", "notificationx")}>
        <span className="nxa-sr-only">{__("Pro", "notificationx")}</span>
    </span>
);

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
