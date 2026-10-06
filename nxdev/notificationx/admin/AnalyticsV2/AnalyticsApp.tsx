import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { Header } from "../../components";
import AnalyticsHeader from "../Analytics/AnalyticsHeader";
import withDocumentTitle from "../../core/withDocumentTitle";
import { useNotificationXContext } from "../../hooks";
import { downloadCsv, getExport, getNotifications, resetData } from "./api";
import { RANGE_LABELS } from "./format";
import { ConfirmDialog, Icon, ProLocked, ProPill } from "./ui";
import Overview from "./Overview";
import Notifications from "./Notifications";
import Leads from "./Leads";
import Audience from "./Audience";
import Drawer from "./Drawer";
import ProScreen from "./ProScreen";

const VIEWS = ["overview", "audience", "notifications", "leads"] as const;
type View = typeof VIEWS[number];
const FREE_RANGES = ["7", "30"];
/** Screens that are Pro; Free sees an upgrade screen instead. */
// On Free every report is a preview; the all-time cards above stay real.
const LOCKED: string[] = ["overview", "audience", "notifications", "leads"];
const PRO_RANGES = ["14", "90", "all"];
const THEME_KEY = "nx-analytics-theme";

const readParam = (key: string, d = "") => new URLSearchParams(window.location.search).get(key) || d;

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
    const initialView = readParam("view", "overview") as View;
    const [view, setView] = useState<View>(VIEWS.includes(initialView) ? initialView : "overview");
    const [range, setRange] = useState(() => {
        const r = readParam("range", "7");
        return isPro || FREE_RANGES.includes(r) ? r : "7";
    });
    // Filtering by notification or type is Pro.
    const [nxId, setNxId] = useState(isPro ? Number(readParam("nx", "0")) || 0 : 0);
    const [type, setType] = useState(isPro ? readParam("type", "") : "");
    const [resetOpen, setResetOpen] = useState<number | null>(null);
    const [resetting, setResetting] = useState(false);
    const [compare, setCompare] = useState(readParam("compare", isPro ? "1" : "") === "1");
    const [dark, setDark] = useState(readTheme);
    const [drawer, setDrawer] = useState<number | null>(Number(readParam("open", "0")) || null);
    const [options, setOptions] = useState<any[]>([]);
    const [customOpen, setCustomOpen] = useState(false);
    const [custom, setCustom] = useState({ start: "", end: "" });
    const [exporting, setExporting] = useState(false);
    const [reloadKey, setReloadKey] = useState(0);
    const moreRef = useRef<HTMLDetailsElement>(null);
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
        writeParams({ view: view === "overview" ? "" : view, range: range === "7" ? "" : range, nx: nxId ? String(nxId) : "", type, compare: isPro && compare ? "1" : "", open: drawer ? String(drawer) : "" });
    }, [view, range, nxId, type, compare, drawer]);
    useEffect(() => { try { window.localStorage.setItem(THEME_KEY, dark ? "dark" : "light"); } catch (e) { /* storage blocked */ } }, [dark]);
    useEffect(() => { if (isPro) getNotifications("7").then((res) => res?.rows && setOptions(res.rows)); }, [reloadKey, isPro]);

    // <details> stays open until its summary is clicked again; close the
    // "More" date menu on any click outside it, or on Esc, like a dropdown.
    useEffect(() => {
        const close = (e: Event) => {
            const menu = moreRef.current;
            if (!menu || !menu.open) return;
            if (e instanceof KeyboardEvent) {
                if (e.key !== "Escape") return;
                menu.open = false;
                (menu.querySelector("summary") as HTMLElement | null)?.focus();
                return;
            }
            if (!menu.contains(e.target as Node)) menu.open = false;
        };
        document.addEventListener("mousedown", close);
        document.addEventListener("touchstart", close);
        document.addEventListener("keydown", close);
        return () => {
            document.removeEventListener("mousedown", close);
            document.removeEventListener("touchstart", close);
            document.removeEventListener("keydown", close);
        };
    }, []);

    const pickRange = (r: string) => {
        setRange(r);
        if (moreRef.current) moreRef.current.open = false;
    };
    const applyCustom = () => {
        if (!custom.start || !custom.end) return;
        pickRange(`custom:${custom.start}:${custom.end}`);
        setCustomOpen(false);
    };
    const doExport = () => {
        setExporting(true);
        getExport(view === "notifications" ? "notifications" : "overview", range, nxId).then((res) => {
            if (res?.csv) downloadCsv(res.filename, res.csv);
            setExporting(false);
        });
    };

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

    // Type filter: types that exist, and the notifications of the chosen one.
    const pool = view === "leads" ? options.filter((o) => o.is_lead) : options;
    const types = Array.from(new Map(pool.map((o) => [o.type, o.type_label])).entries()).sort((a, b) => String(a[1]).localeCompare(String(b[1])));
    const nxOptions = type ? pool.filter((o) => o.type === type) : pool;
    const pickType = (t: string) => {
        setType(t);
        if (t && nxId && options.find((o) => o.nx_id === nxId)?.type !== t) setNxId(0);
    };

    const isCustom = range.startsWith("custom:");
    const lockedView = !isPro && LOCKED.includes(view);
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
                            {isPro && (view === "overview" || view === "audience" || view === "leads") && (
                                <>
                                    <select className="nxa-input nxa-input-type" value={type} onChange={(e) => pickType(e.target.value)} aria-label={__("Notification type", "notificationx")}>
                                        <option value="">{__("All types", "notificationx")}</option>
                                        {types.map(([t, label]) => <option key={t} value={t}>{label}</option>)}
                                    </select>
                                    <select className="nxa-input" value={nxId} onChange={(e) => setNxId(Number(e.target.value))} aria-label={__("Notification", "notificationx")}>
                                        <option value={0}>{type ? __("All of this type", "notificationx") : __("All notifications", "notificationx")}</option>
                                        {nxOptions.map((o) => <option key={o.nx_id} value={o.nx_id}>{o.title}</option>)}
                                    </select>
                                </>
                            )}
                            {!lockedView && <div className="nxa-segmented" role="group" aria-label={__("Date range", "notificationx")}>
                                {FREE_RANGES.map((r) => (
                                    <button type="button" key={r} className={range === r ? "is-active" : ""} aria-pressed={range === r} onClick={() => pickRange(r)}>{r === "7" ? __("7 days", "notificationx") : __("30 days", "notificationx")}</button>
                                ))}
                                {isPro ? (
                                    <details className="nxa-more" ref={moreRef}>
                                        <summary className={!FREE_RANGES.includes(range) ? "is-active" : ""}>{!FREE_RANGES.includes(range) ? (isCustom ? __("Custom", "notificationx") : RANGE_LABELS[range]) : __("More", "notificationx")} ▾</summary>
                                        <div className="nxa-menu">
                                            {PRO_RANGES.map((r) => <button type="button" key={r} onClick={() => pickRange(r)} className={range === r ? "is-active" : ""}>{RANGE_LABELS[r]}</button>)}
                                            <button type="button" onClick={() => { setCustomOpen(true); if (moreRef.current) moreRef.current.open = false; }}>{__("Custom range…", "notificationx")}</button>
                                        </div>
                                    </details>
                                ) : (
                                    <ProLocked feature={__("More date ranges", "notificationx")} text={__("See 14 and 90 days, all time, or any custom range.", "notificationx")}>{__("More", "notificationx")}</ProLocked>
                                )}
                            </div>}
                            {!lockedView && (view === "overview" || view === "leads") && (isPro ? (
                                <label className="nxa-switch">
                                    <input type="checkbox" checked={compare} onChange={(e) => setCompare(e.target.checked)} />
                                    <span aria-hidden="true" />{__("Compare", "notificationx")}
                                </label>
                            ) : (
                                <ProLocked feature={__("Compare periods", "notificationx")} text={__("See how every number changed against the previous period.", "notificationx")}>{__("Compare", "notificationx")}</ProLocked>
                            ))}
                            {!lockedView && (view === "overview" || view === "notifications") && (isPro ? (
                                <button type="button" className="nxa-btn nxa-btn-ghost" onClick={doExport} disabled={exporting} title={__("Download this report as CSV", "notificationx")}>
                                    <Icon name="download" size={15} />{exporting ? __("Exporting…", "notificationx") : __("Export", "notificationx")}
                                </button>
                            ) : (
                                <ProLocked feature={__("CSV export", "notificationx")} text={__("Download the numbers behind any report.", "notificationx")}>{__("Export", "notificationx")}</ProLocked>
                            ))}
                            <button type="button" className="nxa-icon-btn nxa-theme" onClick={() => setDark(!dark)} aria-label={dark ? __("Switch to light mode", "notificationx") : __("Switch to dark mode", "notificationx")} title={dark ? __("Light mode", "notificationx") : __("Dark mode", "notificationx")}>
                                <Icon name={dark ? "sun" : "moon"} size={16} />
                            </button>
                        </div>
                    )}
                </div>

                {customOpen && (
                    <div className="nxa-custom">
                        <label>{__("From", "notificationx")}<input type="date" className="nxa-input" value={custom.start} max={custom.end || undefined} onChange={(e) => setCustom({ ...custom, start: e.target.value })} /></label>
                        <label>{__("To", "notificationx")}<input type="date" className="nxa-input" value={custom.end} min={custom.start || undefined} onChange={(e) => setCustom({ ...custom, end: e.target.value })} /></label>
                        <button type="button" className="nxa-btn nxa-btn-primary" onClick={applyCustom} disabled={!custom.start || !custom.end}>{__("Apply", "notificationx")}</button>
                        <button type="button" className="nxa-btn nxa-btn-ghost" onClick={() => setCustomOpen(false)}>{__("Cancel", "notificationx")}</button>
                    </div>
                )}

                <div className="nxa-shell">
                    <nav className="nxa-nav" aria-label={__("Analytics sections", "notificationx")}>
                        <div className="nxa-nav-group">{__("Analytics", "notificationx")}</div>
                        {VIEWS.map((v) => (
                            <button type="button" key={v} className={view === v ? "is-active" : ""} aria-current={view === v ? "page" : undefined} onClick={() => setView(v)}>
                                <NavIcon name={v} />
                                {{ overview: __("Overview", "notificationx"), audience: __("Audience", "notificationx"), notifications: __("Notifications", "notificationx"), leads: __("Leads", "notificationx") }[v]}
                                {!isPro && LOCKED.includes(v) && <ProPill />}
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
                        {isPro && view === "overview" && <Overview range={range} nxId={nxId} type={type} compare={compare} isPro={isPro} onOpen={setDrawer} reloadKey={reloadKey} />}
                        {!isPro && LOCKED.includes(view) && <ProScreen view={view} />}
                        {isPro && view === "audience" && <Audience range={range} nxId={nxId} type={type} reloadKey={reloadKey} />}
                        {isPro && view === "notifications" && <Notifications range={range} onOpen={setDrawer} reloadKey={reloadKey} />}
                        {isPro && view === "leads" && <Leads range={range} nxId={nxId} type={type} compare={compare} isPro={isPro} reloadKey={reloadKey} />}
                    </main>
                </div>
                {isPro && drawer && <Drawer id={drawer} range={range} reloadKey={reloadKey} onClose={() => setDrawer(null)} onReset={(id: number) => setResetOpen(id)} />}
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
