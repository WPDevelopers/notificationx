import React from "react";

// Line icons for the admin UI (24px grid, stroke drawn with currentColor).
const ICONS: Record<string, React.ReactNode> = {
    dashboard: (
        <>
            <rect x="3" y="3" width="7" height="9" rx="1.5" />
            <rect x="14" y="3" width="7" height="5" rx="1.5" />
            <rect x="14" y="12" width="7" height="9" rx="1.5" />
            <rect x="3" y="16" width="7" height="5" rx="1.5" />
        </>
    ),
    bell: (
        <>
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </>
    ),
    chart: (
        <>
            <path d="M3 3v18h18" />
            <path d="M18 17V9" />
            <path d="M13 17V5" />
            <path d="M8 17v-3" />
        </>
    ),
    zap: <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />,
    sliders: (
        <>
            <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3" />
            <path d="M1 14h6M9 8h6M17 16h6" />
        </>
    ),
    sparkles: (
        <>
            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z" />
            <path d="M19 3v4M17 5h4M5 17v4M3 19h4" />
        </>
    ),
    inbox: (
        <>
            <path d="M22 12h-6l-2 3h-4l-2-3H2" />
            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
        </>
    ),
    plus: <path d="M12 5v14M5 12h14" />,
    eye: (
        <>
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
            <circle cx="12" cy="12" r="3" />
        </>
    ),
    pointer: (
        <>
            <path d="m9 9 5 12 1.8-5.2L21 14z" />
            <path d="M7.2 2.2 8 5.1M5.1 8l-2.9-.8M14 4.1 12 6M6 12l-1.9 2" />
        </>
    ),
    percent: (
        <>
            <path d="M19 5 5 19" />
            <circle cx="6.5" cy="6.5" r="2.5" />
            <circle cx="17.5" cy="17.5" r="2.5" />
        </>
    ),
    layers: (
        <>
            <path d="m12 2 10 5-10 5L2 7z" />
            <path d="m2 17 10 5 10-5" />
            <path d="m2 12 10 5 10-5" />
        </>
    ),
    external: (
        <>
            <path d="M15 3h6v6" />
            <path d="M10 14 21 3" />
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
        </>
    ),
    arrowRight: <path d="M5 12h14M13 6l6 6-6 6" />,
    play: <path d="M8 5.5v13a.5.5 0 0 0 .77.42l10.2-6.5a.5.5 0 0 0 0-.84L8.77 5.08A.5.5 0 0 0 8 5.5z" fill="currentColor" />,
    book: (
        <>
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
        </>
    ),
    users: (
        <>
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
        </>
    ),
    heart: <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z" />,
    lifebuoy: (
        <>
            <circle cx="12" cy="12" r="10" />
            <circle cx="12" cy="12" r="4" />
            <path d="m4.93 4.93 4.24 4.24M14.83 9.17l4.24-4.24M14.83 14.83l4.24 4.24M9.17 14.83l-4.24 4.24" />
        </>
    ),
    bug: (
        <>
            <rect x="8" y="6" width="8" height="14" rx="4" />
            <path d="m19 7-3 2M5 7l3 2M19 19l-3-2M5 19l3-2M20 13h-4M4 13h4M10 4l1 2M14 4l-1 2" />
        </>
    ),
    close: <path d="M18 6 6 18M6 6l12 12" />,
    crown: (
        <>
            <path d="m2 6 4 11h12l4-11-6 5-4-7-4 7z" />
            <path d="M6 21h12" />
        </>
    ),
    bag: (
        <>
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
            <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
        </>
    ),
    cookie: (
        <>
            <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5" />
            <path d="M8.5 8.5v.01M16 15.5v.01M12 12v.01M11 17v.01M7 14v.01" />
        </>
    ),
    megaphone: (
        <>
            <path d="m3 11 18-5v12L3 14v-3z" />
            <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
        </>
    ),
    trending: (
        <>
            <path d="m22 7-8.5 8.5-5-5L2 17" />
            <path d="M16 7h6v6" />
        </>
    ),
    browserTab: (
        <>
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M3 9h18M9 4v5" />
        </>
    ),
    globe: (
        <>
            <circle cx="12" cy="12" r="10" />
            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </>
    ),
    file: (
        <>
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
        </>
    ),
    message: <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />,
    plug: (
        <>
            <path d="M12 22v-5M9 8V2M15 8V2" />
            <path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8z" />
        </>
    ),
    help: (
        <>
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01" />
        </>
    ),
    wand: (
        <>
            <path d="m15 4-1 1M19 8l1-1M9 3v2M21 15h-2M17.8 2.2l-1.4 1.4M21.8 6.2l-1.4 1.4" />
            <path d="m3 21 11-11 1.5 1.5L4.5 22.5z" />
        </>
    ),
    bulb: (
        <>
            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
            <path d="M9 18h6M10 22h4" />
        </>
    ),
    star: <path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z" />,
    circle: <circle cx="12" cy="12" r="9" />,
};

const ICON_BY_PAGE: Record<string, string> = {
    "nx-dashboard"        : "dashboard",
    "nx-admin"            : "bell",
    "nx-edit"             : "plus",
    "nx-analytics"        : "chart",
    "nx-builder"          : "zap",
    "nx-settings"         : "sliders",
    "nx-setup-wizard"     : "wand",
    "nx-feedback-entries" : "inbox",
};

export const iconForPage = (slug: string) => ICON_BY_PAGE[slug] || "circle";

const NxIcon = ({ name, size = 18, className = "", strokeWidth = 1.75 }: {
    name: string;
    size?: number;
    className?: string;
    strokeWidth?: number;
}) => (
    <svg
        className={`nx-icon ${className}`.trim()}
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth={strokeWidth}
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        {ICONS[name] || ICONS.circle}
    </svg>
);

export default NxIcon;
