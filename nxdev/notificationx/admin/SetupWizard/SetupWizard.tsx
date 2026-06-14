import { __ } from "@wordpress/i18n";
import React, { useState, useRef, useEffect, useCallback } from "react";
import useNotificationXContext from "../../hooks/useNotificationXContext";
import nxHelper from "../../core/functions";
import WizardIcon from "./icons";
import Illustration from "./Illustration";

/**
 * NotificationX onboarding "Setup Wizard".
 *
 * Standalone full-screen experience (the WP sidebar + admin bar are hidden via
 * the `nx-setup-wizard-active` body class — see SetupWizard.php / _setup_wizard.scss).
 * The shared shell — horizontal stepper + page footer — is identical on every
 * step; only the card content changes. The Figma's own left rail and top app bar
 * are intentionally dropped per the design brief.
 */

type Option = { id: string; label: string; icon: string; desc?: string };

const STEPS = [
    { id: "welcome", label: __("Welcome", "notificationx") },
    { id: "business", label: __("Business Details", "notificationx") },
    { id: "recommended", label: __("Recommended", "notificationx") },
    { id: "finish", label: __("Finish", "notificationx") },
];

const FEATURES: Option[] = [
    {
        id: "conversions",
        icon: "conversions",
        label: __("Increase Conversions", "notificationx"),
    },
    {
        id: "social-proof",
        icon: "social-proof",
        label: __("Build Social Proof", "notificationx"),
    },
    {
        id: "urgency",
        icon: "urgency",
        label: __("Create Urgency", "notificationx"),
    },
    {
        id: "engagement",
        icon: "engagement",
        label: __("Boost Engagement", "notificationx"),
    },
];

const BUSINESS_TYPES: Option[] = [
    { id: "ecommerce", icon: "store", label: __("Ecommerce Store", "notificationx") },
    { id: "saas", icon: "saas", label: __("SaaS Product", "notificationx") },
    { id: "agency", icon: "agency", label: __("Agency", "notificationx") },
    { id: "course", icon: "course", label: __("Online Course", "notificationx") },
    { id: "coaching", icon: "coaching", label: __("Coaching Center", "notificationx") },
    { id: "blog", icon: "blog", label: __("Blog & Content Website", "notificationx") },
];

const GOALS: Option[] = [
    { id: "sales", icon: "conversions", label: __("Increase Sales", "notificationx") },
    { id: "leads", icon: "leads", label: __("Collect Leads", "notificationx") },
    { id: "signups", icon: "signups", label: __("Get More Signups", "notificationx") },
    { id: "promote-courses", icon: "course", label: __("Promote Courses", "notificationx") },
    { id: "reviews", icon: "reviews", label: __("Showcase Reviews", "notificationx") },
    { id: "user-engagement", icon: "engagement", label: __("User Engagement", "notificationx") },
];

/**
 * Real NotificationX campaigns used for recommendations. GIFs + types/sources
 * are sourced from the Dashboard's `NotificationType` catalog (core/constants).
 * `goals` maps each campaign to the onboarding goals it serves; `isPro` marks
 * Pro-gated types so the Recommended step shows a Free + Pro combination.
 */
type Campaign = {
    id: string;
    title: string;
    desc: string;
    img: string;
    type: string;
    source: string;
    isPro: boolean;
    goals: string[];
};

const CDN = "https://notificationx.com/wp-content/uploads";

const CAMPAIGN_CATALOG: Campaign[] = [
    {
        id: "sales",
        title: __("Sales Notification", "notificationx"),
        desc: __("Display your latest sales to boost credibility and drive more conversions.", "notificationx"),
        img: `${CDN}/2024/09/sales_notification.gif`,
        type: "conversions",
        source: "woocommerce",
        isPro: false,
        goals: ["sales", "signups", "reviews", "promote-courses", "user-engagement"],
    },
    {
        id: "bar",
        title: __("Notification Bar", "notificationx"),
        desc: __("Display latest sales, discounts, or announcements to boost sales.", "notificationx"),
        img: `${CDN}/2024/09/notification_bar.gif`,
        type: "notification_bar",
        source: "press_bar",
        isPro: false,
        goals: ["offers", "newsletter", "leads", "webinar", "sales"],
    },
    {
        id: "gdpr",
        title: __("Cookie Notice", "notificationx"),
        desc: __("Inform users and stay privacy compliant quickly and effortlessly.", "notificationx"),
        img: `${CDN}/2025/06/GDPR.gif`,
        type: "gdpr",
        source: "gdpr",
        isPro: false,
        goals: ["leads", "newsletter"],
    },
    {
        id: "growth",
        title: __("Growth Alert", "notificationx"),
        desc: __("Show sales count & low-stock alerts to influence instant purchases.", "notificationx"),
        img: `${CDN}/2024/09/growth_alert.gif`,
        type: "inline",
        source: "woo_inline",
        isPro: true,
        goals: ["sales", "signups", "user-engagement"],
    },
    {
        id: "flashing",
        title: __("Flashing Tab", "notificationx"),
        desc: __("Grab attention with a flashing browser tab and turn browsing into buying.", "notificationx"),
        img: `${CDN}/2024/09/flash_tab.gif`,
        type: "flashing_tab",
        source: "flashing_tab",
        isPro: true,
        goals: ["offers", "webinar", "promote-courses", "user-engagement"],
    },
    {
        id: "crossdomain",
        title: __("Cross-Domain Notice", "notificationx"),
        desc: __("Display live alerts across multiple WordPress sites to build trust everywhere.", "notificationx"),
        img: `${CDN}/2024/09/cross_domain.gif`,
        type: "cross-domain",
        source: "cross-domain",
        isPro: true,
        goals: ["leads", "newsletter", "offers", "user-engagement"],
    },
];

/**
 * Campaigns always shown first, for every goal selection (the two universal
 * free notifications). The 3rd card onwards is goal-driven.
 */
const DEFAULT_CAMPAIGN_IDS = ["sales", "bar"];

/**
 * Recommend campaigns for the selected goals: the two fixed defaults always
 * lead, followed by the goal-matched campaigns (which change with the
 * selection). Padded to a minimum of three so the step always has content; the
 * slider only kicks in when there are more than three (see `StepRecommended`).
 */
const recommendFor = (goalIds: string[]): Campaign[] => {
    const defaults = DEFAULT_CAMPAIGN_IDS
        .map((id) => CAMPAIGN_CATALOG.find((c) => c.id === id))
        .filter((c): c is Campaign => Boolean(c));

    const matched = CAMPAIGN_CATALOG.filter(
        (c) =>
            !DEFAULT_CAMPAIGN_IDS.includes(c.id) &&
            c.goals.some((g) => goalIds.includes(g))
    );

    const recs = [...defaults, ...matched];

    // Always show at least 3 — pad from the rest of the catalog if needed.
    for (const c of CAMPAIGN_CATALOG) {
        if (recs.length >= 3) break;
        if (!recs.includes(c)) recs.push(c);
    }
    return recs;
};

const SetupWizard = (props) => {
    const builder = useNotificationXContext();
    const [active, setActive] = useState(0);
    const [busy, setBusy] = useState(false);
    const [businessType, setBusinessType] = useState<string>("ecommerce");
    const [goals, setGoals] = useState<string[]>(["sales"]);

    const adminUrl = builder?.admin_url || "/wp-admin/";

    const next = () => setActive((i) => Math.min(i + 1, STEPS.length - 1));
    const back = () => setActive((i) => Math.max(i - 1, 0));

    /**
     * Leave the Welcome step. Proceeding is the consent point (see the Welcome
     * fine print), so we opt the site into usage tracking and let the server
     * send the data to the insights API — then advance. Fire-and-forget.
     */
    const startWizard = () => {
        nxHelper
            .post("miscellaneous", { action: "setup_wizard_optin" })
            .catch(() => {});
        next();
    };

    const toggleGoal = (id: string) =>
        setGoals((prev) =>
            prev.includes(id) ? prev.filter((g) => g !== id) : [...prev, id]
        );

    /** Persist completion + the collected onboarding choices (fire-and-forget). */
    const persist = () =>
        nxHelper
            .post("miscellaneous", {
                action: "complete_setup_wizard",
                business_type: businessType,
                goals: goals.join(","),
            })
            .catch(() => {});

    /**
     * Persist completion, then leave the wizard.
     * `redirect` controls where we land (dashboard or the builder).
     */
    const finish = (redirect: "dashboard" | "builder" = "dashboard") => {
        if (busy) return;
        setBusy(true);
        persist().finally(() => {
            const page = redirect === "builder" ? "nx-edit" : "nx-dashboard";
            window.location.href = `${adminUrl}admin.php?page=${page}`;
        });
    };

    /**
     * "Configure" a recommended campaign: Pro campaigns send non-Pro users to
     * pricing; otherwise mark onboarding done and open the builder in a NEW TAB
     * with the type/source preset via URL params (AddNewNotification reads them)
     * so the wizard stays open in this tab.
     */
    const configureCampaign = (c: Campaign) => {
        if (c.isPro && !builder?.is_pro_active) {
            window.open("https://notificationx.com/#pricing", "_blank");
            return;
        }
        persist();
        const url =
            `${adminUrl}admin.php?page=nx-edit` +
            `&type=${encodeURIComponent(c.type)}` +
            `&source=${encodeURIComponent(c.source)}`;
        // Keep this a synchronous call within the click handler so the browser
        // treats it as a user-initiated open (not a blocked popup).
        window.open(url, "_blank");
    };

    const selectedBusiness = BUSINESS_TYPES.find((b) => b.id === businessType);
    const selectedGoals = GOALS.filter((g) => goals.includes(g.id));

    return (
        <div className="nx-sw">
            <div className="nx-sw__container nx-sw__container--center">
                {/* Every step renders its own in-card header (wordmark +
                    stepper) at the top of its card — see WizardCardHeader. */}
                {active === 0 && (
                    <StepWelcome
                        onStart={startWizard}
                        onSkip={() => finish("dashboard")}
                    />
                )}

                {active === 1 && (
                    <StepBusiness
                        businessType={businessType}
                        goals={goals}
                        onBusiness={setBusinessType}
                        onToggleGoal={toggleGoal}
                        onBack={back}
                        onNext={next}
                    />
                )}

                {active === 2 && (
                    <StepRecommended
                        goals={goals}
                        isProActive={!!builder?.is_pro_active}
                        onConfigure={configureCampaign}
                        onBack={back}
                        onNext={next}
                    />
                )}

                {active === 3 && (
                    <StepFinish
                        business={selectedBusiness}
                        goals={selectedGoals}
                        busy={busy}
                        onDashboard={() => finish("dashboard")}
                        onCreate={() => finish("builder")}
                    />
                )}
            </div>
        </div>
    );
};

/* ------------------------------------------------------------------ */
/* Step 1 — Welcome                                                    */
/* ------------------------------------------------------------------ */
/**
 * Welcome step: an in-card header (wordmark + stepper) on top, then the
 * two-column body (content + the lavender illustration panel). The same
 * in-card header is reused on every step via `WizardCardHeader`.
 */
const StepWelcome = ({ onStart, onSkip }) => (
    <section className="nx-sw__welcome-card">
        <WizardCardHeader active={0} />
        <div className="nx-sw__welcome-body">
            <div className="nx-sw__welcome-main">
                <h1 className="nx-sw__welcome-title">
                    {__("Welcome to NotificationX", "notificationx")} 🚀
                </h1>
                <p className="nx-sw__welcome-sub">
                    {__(
                        "Let's personalize your experience and recommend the best notification campaigns for your business.",
                        "notificationx"
                    )}
                </p>

                <div className="nx-sw__feature-grid">
                    {FEATURES.map((f) => (
                        <div key={f.id} className="nx-sw__feature">
                            <span className="nx-sw__feature-icon">
                                <WizardIcon name={f.icon} />
                            </span>
                            <span className="nx-sw__feature-label">{f.label}</span>
                        </div>
                    ))}
                </div>

                <div className="nx-sw__welcome-actions">
                    <button className="nx-sw__cta" onClick={onStart}>
                        {__("Get Started", "notificationx")}
                    </button>
                    <button className="nx-sw__cta-skip" onClick={onSkip}>
                        {__("Skip Setup", "notificationx")}
                    </button>
                </div>

                <p className="nx-sw__welcome-fineprint">
                    {__(
                        "By proceeding, you agree that we will collect your email address to personalize your setup experience.",
                        "notificationx"
                    )}
                </p>
            </div>

            <aside className="nx-sw__welcome-aside">
                <Illustration />
            </aside>
        </div>
    </section>
);

/**
 * Shared in-card header — the NotificationX wordmark + the progress stepper —
 * rendered at the top of every step's card (the "Step 1" header design, reused
 * everywhere for a consistent flow).
 */
const WizardCardHeader = ({ active }: { active: number }) => (
    <div className="nx-sw__cardhead">
        <header className="nx-sw__brandbar">
            <span className="nx-sw__wordmark">NotificationX</span>
        </header>
        <WizardStepper active={active} />
    </div>
);

/**
 * In-card progress stepper (filled+ringed active dot, dimmed upcoming dots,
 * flexible dividers, uppercase labels). Wired to the real 4-step flow so the
 * labels stay in sync with the wizard.
 */
const WizardStepper = ({ active }: { active: number }) => (
    <nav className="nx-sw__wzsteps" aria-label="progress">
        {STEPS.map((step, i) => {
            const state =
                i < active ? "is-done" : i === active ? "is-active" : "is-upcoming";
            return (
                <React.Fragment key={step.id}>
                    <div className={`nx-sw__wzstep ${state}`}>
                        <span className="nx-sw__wzstep-dot">
                            {i < active ? (
                                <WizardIcon name="check" size={14} />
                            ) : (
                                i + 1
                            )}
                        </span>
                        <span className="nx-sw__wzstep-label">{step.label}</span>
                    </div>
                    {i < STEPS.length - 1 && (
                        <span className="nx-sw__wzstep-divider" />
                    )}
                </React.Fragment>
            );
        })}
    </nav>
);

/* ------------------------------------------------------------------ */
/* Step 2 — Business Details                                           */
/* ------------------------------------------------------------------ */
const StepBusiness = ({
    businessType,
    goals,
    onBusiness,
    onToggleGoal,
    onBack,
    onNext,
}) => (
    <section className="nx-sw__panel">
        <WizardCardHeader active={1} />
        <div className="nx-sw__columns">
            <div className="nx-sw__col">
                <h2 className="nx-sw__col-title">
                    {__("Business Type", "notificationx")}
                </h2>
                <p className="nx-sw__col-sub">
                    {__("Tell us about your industry.", "notificationx")}
                </p>
                <div className="nx-sw__options">
                    {BUSINESS_TYPES.map((b) => (
                        <SelectableRow
                            key={b.id}
                            option={b}
                            selected={businessType === b.id}
                            onClick={() => onBusiness(b.id)}
                        />
                    ))}
                </div>
            </div>

            <div className="nx-sw__col">
                <h2 className="nx-sw__col-title">
                    {__("Primary Goals", "notificationx")}
                </h2>
                <p className="nx-sw__col-sub">
                    {__("Select what you want to achieve.", "notificationx")}
                </p>
                <div className="nx-sw__options">
                    {GOALS.map((g) => (
                        <SelectableRow
                            key={g.id}
                            option={g}
                            selected={goals.includes(g.id)}
                            multi
                            onClick={() => onToggleGoal(g.id)}
                        />
                    ))}
                </div>
            </div>
        </div>

        <div className="nx-sw__panel-footer">
            <NavRow step={2} onBack={onBack} onNext={onNext} />
        </div>
    </section>
);

const SelectableRow = ({
    option,
    selected,
    onClick,
    multi = false,
}: {
    option: Option;
    selected: boolean;
    onClick: () => void;
    multi?: boolean;
}) => (
    <button
        type="button"
        className={`nx-sw__option ${selected ? "is-selected" : ""}`}
        aria-pressed={selected}
        role={multi ? "checkbox" : "radio"}
        aria-checked={selected}
        onClick={onClick}
    >
        <span className="nx-sw__option-icon">
            <WizardIcon name={option.icon} />
        </span>
        <span className="nx-sw__option-label">{option.label}</span>
        {/* Per the Figma, only the selected row shows a trailing check. */}
        {selected && (
            <span className="nx-sw__option-check">
                <WizardIcon name="check" size={14} />
            </span>
        )}
    </button>
);

/* ------------------------------------------------------------------ */
/* Step 3 — Recommended campaigns                                      */
/* ------------------------------------------------------------------ */
const StepRecommended = ({ goals, isProActive, onConfigure, onBack, onNext }) => {
    const recs = recommendFor(goals);
    // The slider (arrows + scrolling) only activates when there are more than
    // the three cards that fit a row.
    const hasSlider = recs.length > 3;
    const viewportRef = useRef<HTMLDivElement>(null);
    // atStart/atEnd disable the arrows at the ends of the track.
    const [edges, setEdges] = useState({ atStart: true, atEnd: false });

    const updateEdges = useCallback(() => {
        const el = viewportRef.current;
        if (!el) return;
        const atStart = el.scrollLeft <= 1;
        const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
        setEdges({ atStart, atEnd });
    }, []);

    useEffect(() => {
        updateEdges();
        window.addEventListener("resize", updateEdges);
        return () => window.removeEventListener("resize", updateEdges);
    }, [updateEdges, recs.length]);

    /** Scroll the slider by one card in the given direction (-1 prev, +1 next). */
    const slide = (dir: number) => {
        const el = viewportRef.current;
        if (!el) return;
        const card = el.querySelector<HTMLElement>(".nx-sw__campaign");
        const gap = 22; // keep in sync with $slider gap in _setup_wizard.scss
        const step = (card ? card.offsetWidth : el.clientWidth / 3) + gap;
        el.scrollBy({ left: dir * step, behavior: "smooth" });
    };

    return (
        <section className="nx-sw__panel">
            <WizardCardHeader active={2} />
            <div className="nx-sw__panel-body">
                <div className="nx-sw__heading-center">
                    <h1 className="nx-sw__title">
                        {__("Recommended Campaigns for Your Business", "notificationx")}
                    </h1>
                    <p className="nx-sw__subtitle">
                        {__(
                            "Based on your goals, here's a mix of free and premium campaigns to get you started.",
                            "notificationx"
                        )}
                    </p>
                </div>

                <div className="nx-sw__slider">
                    {hasSlider && (
                        <button
                            type="button"
                            className="nx-sw__slider-nav nx-sw__slider-nav--prev"
                            onClick={() => slide(-1)}
                            disabled={edges.atStart}
                            aria-label={__("Previous", "notificationx")}
                        >
                            <WizardIcon name="chevron" size={20} />
                        </button>
                    )}

                    <div
                        className="nx-sw__slider-viewport"
                        ref={viewportRef}
                        onScroll={updateEdges}
                    >
                        {recs.map((c) => {
                            const locked = c.isPro && !isProActive;
                            return (
                                <div key={c.id} className="nx-sw__campaign">
                                    <div className="nx-sw__campaign-media">
                                        <img src={c.img} alt={c.title} loading="lazy" />
                                        <span
                                            className={`nx-sw__campaign-badge ${
                                                c.isPro ? "is-pro" : "is-free"
                                            }`}
                                        >
                                            {c.isPro
                                                ? __("Pro", "notificationx")
                                                : __("Free", "notificationx")}
                                        </span>
                                    </div>
                                    <div className="nx-sw__campaign-body">
                                        <h3>{c.title}</h3>
                                        <p>{c.desc}</p>
                                        <button
                                            className={`nx-sw__btn ${
                                                locked
                                                    ? "nx-sw__btn--primary"
                                                    : "nx-sw__btn--outline"
                                            }`}
                                            onClick={() => onConfigure(c)}
                                        >
                                            {locked
                                                ? __("Upgrade to Pro", "notificationx")
                                                : __("Configure", "notificationx")}
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {hasSlider && (
                        <button
                            type="button"
                            className="nx-sw__slider-nav nx-sw__slider-nav--next"
                            onClick={() => slide(1)}
                            disabled={edges.atEnd}
                            aria-label={__("Next", "notificationx")}
                        >
                            <WizardIcon name="chevron" size={20} />
                        </button>
                    )}
                </div>
            </div>

            <div className="nx-sw__panel-footer">
                <div className="nx-sw__nav">
                    <button className="nx-sw__btn nx-sw__btn--text" onClick={onBack}>
                        <WizardIcon name="arrow" size={16} className="nx-sw__arrow-left" />
                        {__("Back to Business Details", "notificationx")}
                    </button>
                    <button className="nx-sw__btn nx-sw__btn--primary" onClick={onNext}>
                        {__("Continue to Final Step", "notificationx")}
                        <WizardIcon name="arrow" size={18} />
                    </button>
                </div>
            </div>
        </section>
    );
};

/* ------------------------------------------------------------------ */
/* Step 4 — Finish                                                     */
/* ------------------------------------------------------------------ */
const StepFinish = ({ business, goals = [], busy, onDashboard, onCreate }) => (
    <section className="nx-sw__panel">
        <WizardCardHeader active={3} />
        <div className="nx-sw__finish">
        <span className="nx-sw__finish-icon">
            <WizardIcon name="confetti" size={34} />
        </span>
        <h1 className="nx-sw__title">{__("You're All Set!", "notificationx")}</h1>
        <p className="nx-sw__subtitle">
            {__(
                "NotificationX is ready to grow your business. Your configuration is saved and your account is fully optimized.",
                "notificationx"
            )}
        </p>

        <div className="nx-sw__summary">
            <div className="nx-sw__summary-card">
                <small>{__("BUSINESS TYPE", "notificationx")}</small>
                <span>
                    <WizardIcon name={business?.icon || "store"} size={18} />
                    {business?.label || __("E-commerce Store", "notificationx")}
                </span>
            </div>
            <div className="nx-sw__summary-card">
                <small>{__("YOUR GOALS", "notificationx")}</small>
                {goals.length ? (
                    <div className="nx-sw__goal-chips">
                        {goals.map((g) => (
                            <span key={g.id} className="nx-sw__goal-chip">
                                <WizardIcon name={g.icon} size={14} />
                                {g.label}
                            </span>
                        ))}
                    </div>
                ) : (
                    <span>
                        <WizardIcon name="conversions" size={18} />
                        {__("Boost Conversions", "notificationx")}
                    </span>
                )}
            </div>
        </div>

        <div className="nx-sw__nav nx-sw__nav--center">
            <button
                className="nx-sw__btn nx-sw__btn--primary"
                onClick={onDashboard}
                disabled={busy}
            >
                {busy
                    ? __("Saving…", "notificationx")
                    : __("Go to Dashboard", "notificationx")}
                <WizardIcon name="arrow" size={18} />
            </button>
            <button
                className="nx-sw__btn nx-sw__btn--outline"
                onClick={onCreate}
                disabled={busy}
            >
                {__("Create My First Campaign", "notificationx")}
            </button>
        </div>
        </div>
    </section>
);

/* ------------------------------------------------------------------ */
/* Shared back / continue row (steps 2)                                */
/* ------------------------------------------------------------------ */
const NavRow = ({ step, onBack, onNext }) => (
    <div className="nx-sw__nav">
        <button className="nx-sw__btn nx-sw__btn--ghost" onClick={onBack}>
            <WizardIcon name="arrow" size={16} className="nx-sw__arrow-left" />
            {__("Back", "notificationx")}
        </button>
        <span className="nx-sw__step-count">
            {/* translators: %d is the current step number */}
            {`${__("Step", "notificationx")} ${step} ${__("of", "notificationx")} 4`}
        </span>
        <button className="nx-sw__btn nx-sw__btn--primary" onClick={onNext}>
            {__("Continue", "notificationx")}
            <WizardIcon name="arrow" size={18} />
        </button>
    </div>
);

export default SetupWizard;
