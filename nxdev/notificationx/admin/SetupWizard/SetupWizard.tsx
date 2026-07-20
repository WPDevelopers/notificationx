import { __ } from "@wordpress/i18n";
import React, { useState, useRef, useEffect, useMemo } from "react";
import useNotificationXContext from "../../hooks/useNotificationXContext";
import nxHelper from "../../core/functions";
import WizardIcon from "./icons";
import CampaignPreview from "./CampaignPreview";
import Illustration from "./Illustration";
import BrandLogo from "../../frontend/themes/helpers/BrandLogo";

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

// Each goal must map to ≥3 campaigns in CAMPAIGN_CATALOG so the Recommended step
// always has a full row. "Build Social Proof" replaces the narrower "Showcase
// Reviews" — only one campaign truly showcases reviews, but three genuinely
// build social proof (Sales Notification, Reviews, Growth Alert).
const GOALS: Option[] = [
    { id: "sales", icon: "conversions", label: __("Increase Sales", "notificationx") },
    { id: "leads", icon: "leads", label: __("Collect Leads", "notificationx") },
    { id: "signups", icon: "signups", label: __("Get More Signups", "notificationx") },
    { id: "promote-courses", icon: "course", label: __("Promote Courses", "notificationx") },
    { id: "social-proof", icon: "social-proof", label: __("Build Social Proof", "notificationx") },
    { id: "user-engagement", icon: "engagement", label: __("Boost Engagement", "notificationx") },
];

// The Primary Goals pre-selected for each business type. Picking a business
// reshapes the goal selection to what that business typically cares about (the
// user can still toggle any goal afterward). Every id must exist in GOALS.
const BUSINESS_GOAL_DEFAULTS: Record<string, string[]> = {
    ecommerce: ["sales", "social-proof"],
    saas: ["signups", "leads"],
    agency: ["leads", "social-proof"],
    course: ["promote-courses", "signups"],
    coaching: ["leads", "promote-courses"],
    blog: ["user-engagement", "leads"],
};

const DEFAULT_BUSINESS = "ecommerce";

/**
 * Real NotificationX campaigns used for recommendations. `type`/`source` are the
 * valid builder identifiers (TypesFactory + each Type's default source) that the
 * "Configure" deep-link presets; `goals` maps each campaign to the onboarding
 * goals it serves; `isPro` marks Pro-gated types so the step mixes Free + Pro.
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

// `goals` lists the onboarding goals (see GOALS) each campaign genuinely serves
// — recommendations are driven purely by this, with NO always-on defaults, so a
// goal like "Collect Leads" never surfaces an unrelated Sales Notification.
// Every goal below maps to at least three campaigns (verified in recommendFor).
const CAMPAIGN_CATALOG: Campaign[] = [
    {
        id: "sales",
        title: __("Sales Notification", "notificationx"),
        desc: __("Show real purchases as they happen to boost credibility and conversions.", "notificationx"),
        img: `${CDN}/2024/09/sales_notification.gif`,
        type: "conversions",
        source: "woocommerce",
        isPro: false,
        // Recent purchases drive sales and act as social proof.
        goals: ["sales", "social-proof"],
    },
    {
        id: "bar",
        title: __("Notification Bar", "notificationx"),
        desc: __("Announce latest sales, discounts, or important updates to every visitor.", "notificationx"),
        img: `${CDN}/2024/09/notification_bar.gif`,
        type: "notification_bar",
        source: "press_bar",
        isPro: false,
        // The all-purpose announcer: discounts, CTAs, signups, updates.
        goals: ["sales", "leads", "signups", "user-engagement"],
    },
    {
        id: "elearning",
        title: __("eLearning Notification", "notificationx"),
        desc: __("Show live course enrollments to build momentum and drive more sign-ups.", "notificationx"),
        // Local plugin asset (resolved against assets.admin at render — see StepRecommended).
        img: "images/extensions/themes/elearning/elearning-theme-1.jpg",
        type: "elearning",
        source: "tutor",
        isPro: false,
        // Recent enrollments promote courses and act as social proof.
        goals: ["promote-courses", "social-proof"],
    },
    {
        id: "reviews",
        title: __("Reviews Notification", "notificationx"),
        desc: __("Turn customer reviews into trust signals that convince hesitant buyers.", "notificationx"),
        img: `${CDN}/2024/09/review_notification.gif`,
        type: "reviews",
        source: "wp_reviews",
        isPro: false,
        // Reviews are social proof, drive sales, and reassure prospective course buyers.
        goals: ["social-proof", "sales", "promote-courses"],
    },
    {
        id: "exit_intent",
        title: __("Exit Intent Popup", "notificationx"),
        desc: __("Catch leaving visitors with a targeted popup and capture leads before they go.", "notificationx"),
        img: `${CDN}/2026/05/exit-intend-theme-two.jpg`,
        type: "exit_intent",
        source: "exit_intent_custom",
        isPro: false,
        // Last-chance capture: leads, signups, or a save-the-sale discount.
        goals: ["leads", "signups", "sales"],
    },
    {
        id: "announcement",
        title: __("Announcement", "notificationx"),
        desc: __("Show a centered announcement popup to capture leads, promote offers, and grab attention.", "notificationx"),
        img: `${CDN}/2025/09/notificationAnimation.gif`,
        type: "popup",
        source: "popup_notification",
        isPro: false,
        // Centered popup: capture leads/signups, or engage with an offer.
        goals: ["leads", "signups", "user-engagement"],
    },
    {
        id: "discount",
        title: __("Discount Alert", "notificationx"),
        desc: __("Display limited-time course offers and discounts to nudge instant enrollments.", "notificationx"),
        // Local plugin asset (resolved against assets.admin at render — see StepRecommended).
        img: "images/extensions/themes/announcements/theme-1.png",
        type: "offer_announcement",
        source: "announcements",
        isPro: true,
        // Offer/discount popups drive course sign-ups and sales.
        goals: ["promote-courses", "sales"],
    },
    {
        id: "growth",
        title: __("Growth Alert", "notificationx"),
        desc: __("Show sales count & low-stock alerts to influence instant purchases.", "notificationx"),
        img: `${CDN}/2024/09/growth_alert.gif`,
        type: "inline",
        source: "woo_inline",
        isPro: true,
        // Live sales counts are social proof; urgency drives sales, engagement, and enrollments.
        goals: ["sales", "social-proof", "user-engagement", "promote-courses"],
    },
    {
        id: "flashing",
        title: __("Flashing Tab", "notificationx"),
        desc: __("Grab attention with a flashing browser tab and turn browsing into buying.", "notificationx"),
        img: `${CDN}/2024/09/flash_tab.gif`,
        type: "flashing_tab",
        source: "flashing_tab",
        isPro: true,
        // Re-grabs attention — for a sale or general re-engagement.
        goals: ["sales", "user-engagement"],
    },
];

/**
 * Recommend campaigns for the selected goals — purely goal-driven, no fixed
 * defaults: a campaign shows only when it serves one of the chosen goals.
 *
 * Deterministic and relevance-ranked: campaigns are ordered by how many of the
 * selected goals they serve (most on-point first), with a stable catalog-order
 * tie-break. Because there is no randomness, the same selection always yields
 * the same list — so changing a Primary Goal visibly re-filters the Recommended
 * step instead of just reshuffling it.
 *
 * Each goal maps to at least three related campaigns, so the result is naturally
 * ≥3; the padding loop is a safety net for any future narrow goal. The slider
 * only kicks in when there are more than three (see `StepRecommended`).
 */
const recommendFor = (goalIds: string[]): Campaign[] => {
    const matchCount = (c: Campaign) =>
        c.goals.filter((g) => goalIds.includes(g)).length;

    const recs = CAMPAIGN_CATALOG
        .map((c, i) => ({ c, i, score: matchCount(c) }))
        .filter((x) => x.score > 0)
        // most relevant first; stable catalog order breaks ties
        .sort((a, b) => b.score - a.score || a.i - b.i)
        .map((x) => x.c);

    // Safety net: guarantee at least three cards even for a narrow selection.
    for (const c of CAMPAIGN_CATALOG) {
        if (recs.length >= 3) break;
        if (!recs.includes(c)) recs.push(c);
    }

    return recs;
};

const SetupWizard = (props) => {
    const builder = useNotificationXContext();
    const [active, setActive] = useState(0);

    /**
     * Keep the full-screen body class in sync with the component lifecycle.
     * PHP adds `nx-setup-wizard-active` via `admin_body_class` only on a full
     * page load — but Route.tsx intercepts NX submenu clicks and navigates
     * client-side (history.push), so reaching the wizard from another NX page
     * would otherwise leave the WP sidebar/admin bar visible.
     */
    useEffect(() => {
        document.body.classList.add("nx-setup-wizard-active");
        return () => {
            document.body.classList.remove("nx-setup-wizard-active");
        };
    }, []);
    const [busy, setBusy] = useState(false);
    const [businessType, setBusinessType] = useState<string>(DEFAULT_BUSINESS);
    const [goals, setGoals] = useState<string[]>(
        BUSINESS_GOAL_DEFAULTS[DEFAULT_BUSINESS]
    );

    /**
     * Switch business type and reshape the Primary Goals to that type's
     * recommended defaults — so the goal selection always reflects the chosen
     * business (the user can still toggle individual goals afterward).
     */
    const selectBusiness = (id: string) => {
        setBusinessType(id);
        setGoals(BUSINESS_GOAL_DEFAULTS[id] ?? ["sales"]);
    };

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
        setGoals((prev) => {
            if (!prev.includes(id)) return [...prev, id];
            // Keep at least one goal selected (mirrors the single-select
            // business type) — deselecting the last remaining goal is a no-op.
            if (prev.length === 1) return prev;
            return prev.filter((g) => g !== id);
        });

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
                        onBusiness={selectBusiness}
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
                    {__("Welcome to NotificationX — Let’s Turn Activity Into Sales", "notificationx")} 🚀
                </h1>
                <p className="nx-sw__welcome-sub">
                    {__(
                        "We\'ll recommend the highest-converting notification campaigns for your store — fully set up in under 2 minutes.",
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
                        {__("Get My Recommendations", "notificationx")}
                    </button>
                    <button className="nx-sw__cta-skip" onClick={onSkip}>
                        {__("I\'ll do this Manually", "notificationx")}
                    </button>
                </div>

                <p className="nx-sw__welcome-fineprint">
                    {__(
                        "By continuing, you agree to let us use your email to tailor your setup. No spam, ever.",
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
            <BrandLogo />
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
                    {__("Pick what describes you best — we\'ll tailor everything to it.", "notificationx")}
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
                    {__("Choose your goals & we\'ll match the right campaigns. Pick as many as you like.", "notificationx")}
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
            <NavRow step={2} onBack={onBack} onNext={onNext} hideBack />
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
    // Re-filter whenever the selected goals change (memoised so the cards stay
    // put while the user scrolls within the step). Deterministic — same goals
    // always yield the same ranked list.
    const baseRecs = useMemo(() => recommendFor(goals), [goals]);
    // Each campaign renders a built HTML/CSS mockup of its notification type
    // (see CampaignPreview) instead of a marketing GIF — crisper and animatable.
    // The slider (arrows + scrolling) only activates when there are more than
    // the three cards that fit a row.
    const hasSlider = baseRecs.length > 3;
    // Seamless infinite loop: render three identical copies of the track and
    // keep the viewport parked in the middle copy. Each arrow click smooth-
    // scrolls one card; once that scroll settles we instantly hop back by one
    // copy-width whenever we've crossed into an outer clone. The clones are
    // identical and the hop is instant, so the loop looks continuous and the
    // arrows work forever in both directions.
    const recs = hasSlider ? [...baseRecs, ...baseRecs, ...baseRecs] : baseRecs;
    const viewportRef = useRef<HTMLDivElement>(null);
    const settleTimer = useRef<ReturnType<typeof setTimeout>>();

    /**
     * Set scrollLeft without animating. The viewport has `scroll-behavior:
     * smooth` in CSS, so a plain assignment would glide across the whole hop
     * (the visible "slide back to start"); overriding it to `auto` for the
     * assignment keeps the re-center invisible.
     */
    const jumpTo = (left: number) => {
        const el = viewportRef.current;
        if (!el) return;
        const prev = el.style.scrollBehavior;
        el.style.scrollBehavior = "auto";
        el.scrollLeft = left;
        el.style.scrollBehavior = prev;
    };

    // Park in the middle copy on mount and whenever the recommendation set
    // changes. Clones are identical, so this is never visible.
    useEffect(() => {
        const el = viewportRef.current;
        if (!el || !hasSlider) return;
        jumpTo(el.scrollWidth / 3);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [hasSlider, baseRecs.length]);

    /** Hop back into the middle copy once a (button-driven) scroll has rested. */
    const recenter = () => {
        const el = viewportRef.current;
        if (!el || !hasSlider) return;
        const copy = el.scrollWidth / 3;
        if (el.scrollLeft >= copy * 2) {
            jumpTo(el.scrollLeft - copy);
        } else if (el.scrollLeft < copy) {
            jumpTo(el.scrollLeft + copy);
        }
    };

    const onScroll = () => {
        if (!hasSlider) return;
        // Re-center only after the scroll stops emitting events, so the instant
        // hop never interrupts the in-flight smooth animation.
        clearTimeout(settleTimer.current);
        settleTimer.current = setTimeout(recenter, 140);
    };

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
                            "Handpicked for your goals — start with these and you’re live in minutes.",
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
                            aria-label={__("Previous", "notificationx")}
                        >
                            <WizardIcon name="chevron" size={20} />
                        </button>
                    )}

                    <div
                        className="nx-sw__slider-viewport"
                        ref={viewportRef}
                        onScroll={onScroll}
                    >
                        {recs.map((c, i) => {
                            const locked = c.isPro && !isProActive;
                            return (
                                <div key={`${c.id}-${i}`} className="nx-sw__campaign">
                                    <div className="nx-sw__campaign-media">
                                        <CampaignPreview type={c.type} />
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
const NavRow = ({ step, onBack, onNext, hideBack = false }) => (
    <div className="nx-sw__nav">
        <button
            className={`nx-sw__btn nx-sw__btn--ghost${
                hideBack ? " nx-sw__btn--hidden" : ""
            }`}
            onClick={hideBack ? undefined : onBack}
            aria-hidden={hideBack || undefined}
            tabIndex={hideBack ? -1 : undefined}
        >
            <WizardIcon name="arrow" size={16} className="nx-sw__arrow-left" />
            {__("Back", "notificationx")}
        </button>
        <span className="nx-sw__step-count">
            {/* translators: %d is the current step number */}
            {`${__("Step", "notificationx")} ${step} ${__("of", "notificationx")} 4`}
        </span>
        <button className="nx-sw__btn nx-sw__btn--primary" onClick={onNext}>
            {__("See My Recommendations", "notificationx")}
            <WizardIcon name="arrow" size={18} />
        </button>
    </div>
);

export default SetupWizard;
