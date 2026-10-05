import React, { useEffect, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { getNotification } from "./api";
import { AreaChart } from "./charts";
import { Delta, ErrorState, Skeleton, StatusDot, TypeBadge } from "./ui";
import { fmtNum, fmtPct, rangeLabel } from "./format";

/** Slide-over with one notification's numbers for the active range. */
const Drawer = ({ id, range, reloadKey, onClose, onReset }: { id: number; range: string; reloadKey?: number; onClose: () => void; onReset?: (id: number) => void }) => {
    const [data, setData] = useState<any>(null);
    const [error, setError] = useState(false);
    const closeRef = useRef<HTMLButtonElement>(null);

    const load = () => {
        setData(null);
        setError(false);
        getNotification(id, range).then((res) => (res && res.totals ? setData(res) : setError(true)));
    };
    useEffect(load, [id, range, reloadKey]);
    useEffect(() => {
        closeRef.current?.focus();
        const esc = (e: KeyboardEvent) => e.key === "Escape" && onClose();
        document.addEventListener("keydown", esc);
        return () => document.removeEventListener("keydown", esc);
    }, []);

    const n = data?.notification;
    return (
        <div className="nxa-drawer-layer" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
            <aside className="nxa-drawer" role="dialog" aria-modal="true" aria-label={n ? n.title : __("Notification analytics", "notificationx")}>
                <header className="nxa-drawer-head">
                    <div>
                        <div className="nxa-eyebrow">{data ? rangeLabel(data.window.token, data.window) : " "}</div>
                        <h2>{n ? n.title : <Skeleton width={220} height={22} />}</h2>
                        {n && <div className="nxa-drawer-meta"><StatusDot enabled={n.enabled} />{n.enabled ? __("Enabled", "notificationx") : __("Disabled", "notificationx")}<TypeBadge label={n.type_label} /><span className="nxa-id">#{n.nx_id}</span></div>}
                    </div>
                    <button type="button" className="nxa-icon-btn" ref={closeRef} onClick={onClose} aria-label={__("Close", "notificationx")}>×</button>
                </header>
                {error ? <ErrorState onRetry={load} /> : !data ? (
                    <div className="nxa-drawer-body"><Skeleton height={80} /><div style={{ height: 16 }} /><Skeleton height={220} /></div>
                ) : (
                    <div className="nxa-drawer-body">
                        <div className="nxa-mini-kpis">
                            <div><span>{__("Views", "notificationx")}</span><strong className="nxa-num">{n.is_lead && !data.totals.views ? "—" : fmtNum(data.totals.views)}</strong><Delta value={data.changes?.views} /></div>
                            <div><span>{__("Clicks", "notificationx")}</span><strong className="nxa-num">{fmtNum(data.totals.clicks)}</strong><Delta value={data.changes?.clicks} /></div>
                            <div><span>{__("CTR", "notificationx")}</span><strong className="nxa-num">{data.totals.views ? fmtPct(data.totals.ctr) : "—"}</strong><Delta value={data.changes?.ctr} unit="pp" /></div>
                            {n.is_lead && <div><span>{__("Leads", "notificationx")}</span><strong className="nxa-num">{fmtNum(data.totals.leads)}</strong></div>}
                        </div>
                        {n.is_lead && !data.totals.views && (
                            <p className="nxa-note">{__("Views for Popup and Exit Intent notifications aren't recorded yet. Leads are their form submissions.", "notificationx")}</p>
                        )}
                        <h4 className="nxa-section-title">{__("Daily trend", "notificationx")}</h4>
                        <AreaChart
                            height={220}
                            labels={data.series.map((r: any) => r.date)}
                            ariaLabel={sprintf(__("Views and clicks per day for %s", "notificationx"), n.title)}
                            series={[
                                { key: "views", label: __("Views", "notificationx"), values: data.series.map((r: any) => r.views), color: "var(--nxa-brand)" },
                                { key: "clicks", label: __("Clicks", "notificationx"), values: data.series.map((r: any) => r.clicks), color: "var(--nxa-mint)" },
                            ]}
                        />
                        <div className="nxa-drawer-actions">
                            <a className="nxa-btn nxa-btn-primary" href={data.edit_url}>{__("Edit notification", "notificationx")}</a>
                            {onReset && <button type="button" className="nxa-btn nxa-btn-ghost nxa-btn-danger-ghost" onClick={() => onReset(id)}>{__("Reset data", "notificationx")}</button>}
                        </div>
                    </div>
                )}
            </aside>
        </div>
    );
};

export default Drawer;
