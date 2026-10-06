import { __, sprintf } from "@wordpress/i18n";

const locale = () => (document.documentElement.lang || "en-US").replace("_", "-");

/** 1234 -> "1,234". */
export const fmtNum = (n: number) => new Intl.NumberFormat(locale()).format(Number(n) || 0);

/** 48230 -> "48.2K" for headline numbers only. */
export const fmtCompact = (n: number) =>
    Math.abs(Number(n) || 0) < 10000
        ? fmtNum(n)
        : new Intl.NumberFormat(locale(), { notation: "compact", maximumFractionDigits: 1 }).format(Number(n) || 0);

/** Up to 2 decimals without trailing zeros (6.09%, 5.5%, 0%), like the all-time CTR card. */
export const fmtPct = (n: number) => `${new Intl.NumberFormat(locale(), { maximumFractionDigits: 2 }).format(Number(n) || 0)}%`;

/**
 * Stats are stored per UTC day as "YYYY-MM-DD". Format the date itself,
 * in UTC, so it never shifts a day for sites behind or ahead of UTC.
 */
export const fmtDate = (ymd: string, opts: Intl.DateTimeFormatOptions = { month: "short", day: "numeric" }) => {
    const [y, m, d] = String(ymd).split("-").map(Number);
    if (!y || !m || !d) return ymd;
    return new Intl.DateTimeFormat(locale(), { ...opts, timeZone: "UTC" }).format(new Date(Date.UTC(y, m - 1, d)));
};

export const fmtRange = (start: string, end: string) =>
    start === end ? fmtDate(start, { month: "short", day: "numeric", year: "numeric" })
        : `${fmtDate(start)} – ${fmtDate(end, { month: "short", day: "numeric", year: "numeric" })}`;

/** A WP "Y-m-d H:i:s" timestamp (no timezone) for display. */
export const fmtDateTime = (value: string) => {
    if (!value) return "";
    const d = new Date(String(value).replace(" ", "T") + "Z");
    return isNaN(d.getTime()) ? value : new Intl.DateTimeFormat(locale(), { dateStyle: "medium", timeStyle: "short", timeZone: "UTC" }).format(d);
};

export const RANGE_LABELS: Record<string, string> = {
    "7": __("Last 7 days", "notificationx"),
    "14": __("Last 14 days", "notificationx"),
    "30": __("Last 30 days", "notificationx"),
    "90": __("Last 90 days", "notificationx"),
    all: __("All time", "notificationx"),
};

export const rangeLabel = (token: string, window?: any) => {
    if (RANGE_LABELS[token]) return RANGE_LABELS[token];
    if (window?.start) return fmtRange(window.start, window.end);
    return __("Custom range", "notificationx");
};

/** "▲ 12.4%" style helper data; null when there is no baseline. */
export const deltaInfo = (value: number | null | undefined, unit: "pct" | "pp" = "pct") => {
    if (value === null || value === undefined) return null;
    const up = value > 0;
    const flat = value === 0;
    const abs = Math.abs(value).toFixed(1);
    return {
        dir: flat ? "flat" : up ? "up" : "down",
        text: unit === "pp" ? sprintf(__("%s pts", "notificationx"), abs) : `${abs}%`,
    };
};
