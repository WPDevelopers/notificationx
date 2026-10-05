import React from "react";
import { __ } from "@wordpress/i18n";
import { Chip, Icon, PRICING_URL, ProPill, Tone } from "./ui";

type Locked = { icon: string; tone: Tone; title: string; text: string; points: string[] };

const SCREENS: Record<string, Locked> = {
    audience: {
        icon: "globe", tone: "brand",
        title: __("See who your notifications reach", "notificationx"),
        text: __("Audience shows how often every notification is really seen, and by whom. It's recorded without cookies, and it's already collecting on your site.", "notificationx"),
        points: [
            __("Visitors reached, hover rate and close rate", "notificationx"),
            __("Visitors who arrive from AI assistants like ChatGPT and Perplexity", "notificationx"),
            __("Devices, countries, top pages and referrers", "notificationx"),
            __("Real views for Popup, Exit Intent and Cookie Notice", "notificationx"),
        ],
    },
    notifications: {
        icon: "trophy", tone: "amber",
        title: __("Compare every notification side by side", "notificationx"),
        text: __("Find your best and worst performers in one sortable table, then open any notification for its own trend.", "notificationx"),
        points: [
            __("Views, times seen, clicks, CTR and leads per notification", "notificationx"),
            __("Search, filter by type and status, sort by any column", "notificationx"),
            __("Daily trend for each notification", "notificationx"),
            __("Export to CSV", "notificationx"),
        ],
    },
    leads: {
        icon: "leads", tone: "mint",
        title: __("Turn form submissions into insight", "notificationx"),
        text: __("See how many leads your Popup and Exit Intent forms collect and how well they convert.", "notificationx"),
        points: [
            __("Leads over time and conversion rate", "notificationx"),
            __("Your top lead-collecting notifications", "notificationx"),
            __("Busiest weekday, and Popup vs Exit Intent", "notificationx"),
            __("Compare with the previous period", "notificationx"),
        ],
    },
};

/** Decorative, blurred sketch of the report behind the lock. No real data. */
const Sketch = ({ view }: { view: string }) => (
    <div className={`nxa-sketch nxa-sketch-${view}`} aria-hidden="true">
        <div className="nxa-sketch-kpis">{[0, 1, 2, 3].map((i) => <span key={i}><i /><b /></span>)}</div>
        <div className="nxa-sketch-chart">
            <svg viewBox="0 0 600 140" preserveAspectRatio="none">
                <path d="M0 120 C60 110 90 70 150 80 S250 40 310 60 S420 20 480 35 S560 15 600 20 L600 140 L0 140Z" fill="var(--nxa-brand-soft)" />
                <path d="M0 120 C60 110 90 70 150 80 S250 40 310 60 S420 20 480 35 S560 15 600 20" fill="none" stroke="var(--nxa-brand)" strokeWidth="3" />
            </svg>
        </div>
        <div className="nxa-sketch-rows">
            {view === "audience" ? <span className="nxa-sketch-donut" /> : null}
            <div>{[90, 72, 55, 40, 28].map((w) => <span key={w}><i /><b style={{ width: `${w}%` }} /></span>)}</div>
        </div>
    </div>
);

const ProScreen = ({ view }: { view: string }) => {
    const s = SCREENS[view] || SCREENS.audience;
    return (
        <div className="nxa-pro-screen">
            <Sketch view={view} />
            <div className="nxa-pro-card" role="region" aria-label={s.title}>
                <div className="nxa-pro-card-head">
                    <Chip icon={s.icon} tone={s.tone} size={20} />
                    <ProPill />
                </div>
                <h2>{s.title}</h2>
                <p>{s.text}</p>
                <ul>
                    {s.points.map((point) => <li key={point}><Icon name="check" size={16} />{point}</li>)}
                </ul>
                <a className="nxa-btn nxa-btn-primary nxa-btn-lg" href={PRICING_URL} target="_blank" rel="noopener noreferrer">
                    <Icon name="sparkle" size={16} />{__("Upgrade to Pro", "notificationx")}
                </a>
            </div>
        </div>
    );
};

/** Inline lock for one card on a Free screen. */
export const ProCard = ({ title, text, className = "" }: { title: string; text: string; className?: string }) => (
    <section className={`nxa-card nxa-pro-inline ${className}`}>
        <div className="nxa-pro-inline-sketch" aria-hidden="true">{[86, 64, 48, 30].map((w) => <span key={w}><i /><b style={{ width: `${w}%` }} /></span>)}</div>
        <div className="nxa-pro-inline-body">
            <ProPill />
            <h3>{title}</h3>
            <p>{text}</p>
            <a className="nxa-btn nxa-btn-primary" href={PRICING_URL} target="_blank" rel="noopener noreferrer">{__("Upgrade to Pro", "notificationx")}</a>
        </div>
    </section>
);

export default ProScreen;
