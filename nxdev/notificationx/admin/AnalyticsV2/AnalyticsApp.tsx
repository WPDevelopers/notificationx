import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { applyFilters } from "@wordpress/hooks";
import { Header } from "../../components";
import AnalyticsHeader from "../Analytics/AnalyticsHeader";
import withDocumentTitle from "../../core/withDocumentTitle";
import { useNotificationXContext } from "../../hooks";
import { resetData } from "./api";
import { ConfirmDialog, Icon, ProPill } from "./ui";
import ProScreen from "./ProScreen";

const VIEWS = ["overview", "audience", "notifications", "leads"] as const;
type View = typeof VIEWS[number];
const THEME_KEY = "nx-analytics-theme";

const readReports = () => !!applyFilters("nx_analytics_reports", false);

/** Keep the dashboard state in the URL so it survives reloads and can be shared. */
const writeParams = (params: Record<string, string>) => {
    const url = new URL(window.location.href);
    Object.keys(params).forEach((k) => (params[k] ? url.searchParams.set(k, params[k]) : url.searchParams.delete(k)));
    window.history.replaceState(window.history.state, "", url.toString());
};

const readTheme = () => { try { return window.localStorage.getItem(THEME_KEY) === "dark"; } catch (e) { return false; } };

const NavIcon = ({ name }: { name: View }) => {
    const d = {
        overview: "M3 13h4v6H3zM10 9h4v10h-4zM17 4h4v15h-4z",
        audience: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3.5 9h17M3.5 15h17M12 3c2.5 2.7 3.5 5.7 3.5 9s-1 6.3-3.5 9c-2.5-2.7-3.5-5.7-3.5-9s1-6.3 3.5-9z",
        notifications: "M12 3a6 6 0 0 0-6 6v4l-2 3h16l-2-3V9a6 6 0 0 0-6-6zm-2 15a2 2 0 0 0 4 0",
        leads: "M4 6h16v12H4zM4 7l8 6 8-6",
    }[name];
    return <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d={d} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" /></svg>;
};

const AnalyticsApp = () => {
    const ctx = useNotificationXContext();
    const isPro = !!ctx?.is_pro_active;
    // The URL as the page opened, before this page rewrites it.
    const [initialParams] = useState(() => new URLSearchParams(window.location.search));
    const readParam = (key: string, d = "") => initialParams.get(key) || d;
    // The reports are rendered by NotificationX Pro through the filters below.
    // Without them every view is a preview; the all-time cards above stay real.
    // Pro's script loads after free's and this page can mount before it runs,
    // so the answer is final only once the document is parsed.
    const [parsed, setParsed] = useState(document.readyState !== "loading");
    const [hasReports, setHasReports] = useState(readReports);
    const initialView = readParam("view", "overview") as View;
    const [view, setView] = useState<View>(VIEWS.includes(initialView) ? initialView : "overview");
    const [range, setRange] = useState(() => (hasReports ? readParam("range", "7") : "7"));
    const [nxId, setNxId] = useState(hasReports ? Number(readParam("nx", "0")) || 0 : 0);
    const [type, setType] = useState(hasReports ? readParam("type", "") : "");
    const [resetOpen, setResetOpen] = useState<number | null>(null);
    const [resetting, setResetting] = useState(false);
    const [compare, setCompare] = useState(readParam("compare", hasReports ? "1" : "") === "1");
    const [dark, setDark] = useState(readTheme);
    const [drawer, setDrawer] = useState<number | null>(Number(readParam("open", "0")) || null);
    const [options, setOptions] = useState<any[]>([]);
    const [reloadKey, setReloadKey] = useState(0);
    // Below the bar: Pro's filter bar renders its custom date panel here.
    const [slot, setSlot] = useState<HTMLDivElement | null>(null);
    const rootRef = useRef<HTMLDivElement>(null);
    const headRef = useRef<HTMLDivElement>(null);
    const sentinelRef = useRef<HTMLDivElement>(null);
    const [stuck, setStuck] = useState(false);
    // Publish the bar's height so the side menu can pin right below it. Pinning
    // doesn't change that height (only a shadow is added).
    const syncHead = () => {
        const head = headRef.current, root = rootRef.current;
        if (head && root) root.style.setProperty("--nxa-head-h", `${head.getBoundingClientRect().height}px`);
    };
    useLayoutEffect(syncHead, [view]);

    // Sticky filter bar: know when it is pinned (to compact it), and publish
    // its height so the side menu can stick right below it.
    useEffect(() => {
        const sentinel = sentinelRef.current, head = headRef.current, root = rootRef.current;
        if (!sentinel || !head || !root || typeof IntersectionObserver === "undefined") return;
        const bar = parseInt(getComputedStyle(document.documentElement).getPropertyValue("--wp-admin--admin-bar--height"), 10) || 32;
        const io = new IntersectionObserver(([entry]) => setStuck(!entry.isIntersecting && entry.boundingClientRect.top < bar + 1), { rootMargin: `-${bar + 1}px 0px 0px 0px` });
        io.observe(sentinel);
        const setHeight = () => syncHead();
        setHeight();
        const ro = typeof ResizeObserver !== "undefined" ? new ResizeObserver(setHeight) : null;
        ro?.observe(head);
        return () => { io.disconnect(); ro?.disconnect(); };
    }, []);

    useEffect(() => {
        if (parsed) return;
        // Not batched here (React 17): set the filters first and `parsed`
        // last, so no render fetches the defaults or flashes the preview.
        const onParsed = () => {
            if (readReports()) {
                setRange(readParam("range", "7"));
                setNxId(Number(readParam("nx", "0")) || 0);
                setType(readParam("type", ""));
                setCompare(readParam("compare", "1") === "1");
                setHasReports(true);
            }
            setParsed(true);
        };
        document.addEventListener("DOMContentLoaded", onParsed);
        return () => document.removeEventListener("DOMContentLoaded", onParsed);
    }, []);

    useEffect(() => {
        if (!parsed) return; // Keep the URL until the filters are known.
        writeParams({ view: view === "overview" ? "" : view, range: range === "7" ? "" : range, nx: nxId ? String(nxId) : "", type, compare: hasReports && compare ? "1" : "", open: drawer ? String(drawer) : "" });
    }, [parsed, view, range, nxId, type, compare, drawer]);
    useEffect(() => { try { window.localStorage.setItem(THEME_KEY, dark ? "dark" : "light"); } catch (e) { /* storage blocked */ } }, [dark]);
    const doReset = () => {
        if (resetOpen === null) return;
        setResetting(true);
        resetData(resetOpen).then(() => {
            setResetting(false);
            setResetOpen(null);
            setReloadKey((k) => k + 1);
        });
    };
    const resetTitle = (id: number) => options.find((o) => o.nx_id === id)?.title || `#${id}`;

    const titles: Record<View, [string, string]> = {
        overview: [__("Analytics overview", "notificationx"), __("How your notifications perform with your visitors.", "notificationx")],
        audience: [__("Audience", "notificationx"), __("Who sees your notifications: devices, countries, pages and where visitors came from.", "notificationx")],
        notifications: [__("Notifications", "notificationx"), __("Every notification's views, clicks and click-through rate.", "notificationx")],
        leads: [__("Leads", "notificationx"), __("How many leads your Popup and Exit Intent forms collect, and how well they convert.", "notificationx")],
    };

    return (
        <div className="notificationx-items">
            <Header addNew={true} />
            {/* All-time totals, the same cards as Dashboard, All NotificationX and Settings. */}
            <AnalyticsHeader assetsURL={ctx?.assets} />
            <div className={`nxa ${dark ? "nxa-dark" : ""}`} ref={rootRef}>
                <div className="nxa-head-sentinel" ref={sentinelRef} aria-hidden="true" />
                <div className={`nxa-page-head ${stuck ? "is-stuck" : ""}`} ref={headRef}>
                    <div>
                        <h1>{titles[view][0]}</h1>
                        <p>{titles[view][1]}</p>
                    </div>
                    {(
                        <div className="nxa-controls">
                            {hasReports && applyFilters("nx_analytics_controls", null, { view, range, setRange, nxId, setNxId, type, setType, compare, setCompare, options, setOptions, reloadKey, slot }) as React.ReactNode}
                            <button type="button" className="nxa-icon-btn nxa-theme" onClick={() => setDark(!dark)} aria-label={dark ? __("Switch to light mode", "notificationx") : __("Switch to dark mode", "notificationx")} title={dark ? __("Light mode", "notificationx") : __("Dark mode", "notificationx")}>
                                <Icon name={dark ? "sun" : "moon"} size={16} />
                            </button>
                        </div>
                    )}
                </div>
                <div ref={setSlot} />

                <div className="nxa-shell">
                    <nav className="nxa-nav" aria-label={__("Analytics sections", "notificationx")}>
                        <div className="nxa-nav-group">{__("Analytics", "notificationx")}</div>
                        {VIEWS.map((v) => (
                            <button type="button" key={v} className={view === v ? "is-active" : ""} aria-current={view === v ? "page" : undefined} onClick={() => setView(v)}>
                                <NavIcon name={v} />
                                {{ overview: __("Overview", "notificationx"), audience: __("Audience", "notificationx"), notifications: __("Notifications", "notificationx"), leads: __("Leads", "notificationx") }[v]}
                                {parsed && !hasReports && <ProPill />}
                            </button>
                        ))}
                        <div className="nxa-nav-group">{__("Configuration", "notificationx")}</div>
                        <a href="admin.php?page=nx-settings&tab=email-analytics-reporting">
                            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" strokeWidth="1.8" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" /></svg>
                            {__("Settings", "notificationx")}
                        </a>
                        <button type="button" className="nxa-nav-danger" onClick={() => setResetOpen(view === "overview" || view === "audience" ? nxId : 0)}>
                            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.6M4 4v4h4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            {__("Reset data", "notificationx")}
                        </button>
                    </nav>
                    <main className="nxa-main">
                        {hasReports
                            ? applyFilters("nx_analytics_view", null, { view, range, nxId, type, compare, isPro, reloadKey, onOpen: setDrawer }) as React.ReactNode
                            : parsed && <ProScreen view={view} needsUpdate={isPro} />}
                    </main>
                </div>
                {hasReports && drawer && applyFilters("nx_analytics_drawer", null, { id: drawer, range, reloadKey, onClose: () => setDrawer(null), onReset: (id: number) => setResetOpen(id) }) as React.ReactNode}
                {resetOpen !== null && (
                    <ConfirmDialog
                        busy={resetting}
                        title={resetOpen ? sprintf(__("Reset data of “%s”?", "notificationx"), resetTitle(resetOpen)) : __("Reset all analytics data?", "notificationx")}
                        text={<>
                            <p>{resetOpen
                                ? __("This deletes the views, clicks and Audience data of this notification. The notification itself stays.", "notificationx")
                                : __("This deletes the views, clicks and Audience data of every notification. Your notifications stay.", "notificationx")}</p>
                            <p>{__("Leads (form submissions) are kept. This can't be undone.", "notificationx")}</p>
                        </>}
                        confirmLabel={resetOpen ? __("Reset this notification", "notificationx") : __("Reset all data", "notificationx")}
                        onConfirm={doReset}
                        onCancel={() => setResetOpen(null)}
                    />
                )}
            </div>
        </div>
    );
};

export default withDocumentTitle(AnalyticsApp, __("Analytics", "notificationx"));
