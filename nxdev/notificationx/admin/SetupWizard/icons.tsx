import React from "react";

/**
 * Compact inline icon set used across the Setup Wizard steps.
 * 20×20 viewBox, stroke = currentColor so colour is controlled by CSS.
 */
const PATHS: Record<string, React.ReactNode> = {
    // feature / generic
    conversions: <path d="M3 17l5-5 3 3 6-7M14 8h4v4" />,
    "social-proof": (
        <>
            <circle cx="7" cy="8" r="3" />
            <path d="M2 18a5 5 0 0110 0M14 5a3 3 0 010 6M13 18a5 5 0 015-5" />
        </>
    ),
    urgency: (
        <>
            <circle cx="10" cy="11" r="6" />
            <path d="M10 8v3l2 2M8 2h4" />
        </>
    ),
    engagement: (
        <path d="M10 17s-6-3.7-6-8a3.3 3.3 0 016-1.9A3.3 3.3 0 0116 9c0 4.3-6 8-6 8z" />
    ),
    // business types
    store: <path d="M3 8l1-4h12l1 4M3 8v8h14V8M3 8h14M8 16v-4h4v4" />,
    saas: <path d="M7 7l-4 3 4 3M13 7l4 3-4 3" />,
    agency: (
        <>
            <circle cx="7" cy="7" r="2.5" />
            <circle cx="13" cy="7" r="2.5" />
            <path d="M3 17a4 4 0 018 0M9 17a4 4 0 018 0" />
        </>
    ),
    course: <path d="M3 5l7 3 7-3-7-3-7 3zM6 9v4c0 1 4 2 4 2s4-1 4-2V9" />,
    coaching: (
        <>
            <circle cx="10" cy="6" r="2.5" />
            <path d="M5 17c0-3 2.5-5 5-5s5 2 5 5" />
        </>
    ),
    blog: <path d="M5 3h7l3 3v11H5zM12 3v3h3M7 9h6M7 12h6M7 15h4" />,
    local: (
        <>
            <path d="M10 18s5-4.5 5-9a5 5 0 00-10 0c0 4.5 5 9 5 9z" />
            <circle cx="10" cy="9" r="2" />
        </>
    ),
    nonprofit: (
        <path d="M10 16s-5-3.3-5-7a2.8 2.8 0 015-1.6A2.8 2.8 0 0115 9c0 3.7-5 7-5 7z" />
    ),
    other: (
        <>
            <circle cx="5" cy="10" r="1.2" />
            <circle cx="10" cy="10" r="1.2" />
            <circle cx="15" cy="10" r="1.2" />
        </>
    ),
    // goals
    leads: (
        <>
            <circle cx="8" cy="7" r="3" />
            <path d="M2 17a6 6 0 0112 0M15 6v4M13 8h4" />
        </>
    ),
    signups: (
        <>
            <circle cx="8" cy="7" r="3" />
            <path d="M2 17a6 6 0 0112 0M13 9l2 2 3-3" />
        </>
    ),
    webinar: <path d="M3 5h10v10H3zM13 8l4-2v8l-4-2" />,
    reviews: (
        <path d="M10 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L10 14.3 5.2 16.7l.9-5.4L2.2 7.7l5.4-.8L10 2z" />
    ),
    newsletter: <path d="M3 5h14v10H3zM3 6l7 5 7-5" />,
    offers: (
        <>
            <path d="M3 3h6l8 8-6 6-8-8V3z" />
            <circle cx="7" cy="7" r="1.3" />
        </>
    ),
    // misc
    check: <path d="M4 10l4 4 8-8" />,
    arrow: <path d="M4 10h11M11 5l5 5-5 5" />,
    // bare left-pointing chevron (rotate 180° for "next")
    chevron: <path d="M12.5 5l-5 5 5 5" />,
    rocket: (
        <path d="M5 15s-2 1-3 3c2-.5 3-1 3-1M5 15l3 .5.5 3M14 3c-4 1-7 4-9 9l3 .5.5 3c5-2 8-5 9-9 .3-1.4.3-2.7 0-3.5-.8-.3-2.1-.3-3.5 0zM12 8a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
    ),
    confetti: (
        <path d="M3 17l5-12 9 9-12 3zM10 5l1-2M14 7l2-1M15 10l2 0" />
    ),
};

export const WizardIcon = ({
    name,
    size = 20,
    className = "",
}: {
    name: string;
    size?: number;
    className?: string;
}) => (
    <svg
        className={className}
        width={size}
        height={size}
        viewBox="0 0 20 20"
        fill="none"
        stroke="currentColor"
        strokeWidth={1.6}
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
    >
        {PATHS[name] || PATHS.other}
    </svg>
);

export default WizardIcon;
