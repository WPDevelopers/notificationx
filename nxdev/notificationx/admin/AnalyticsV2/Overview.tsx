import React, { useEffect, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { getSummary } from "./api";
import { AreaChart, BarList } from "./charts";
import { Card, CtrPill, Delta, EmptyState, ErrorState, KpiCard, LegendToggle, Skeleton, StatusDot, TypeBadge, chartSeries, useChartFocus } from "./ui";
import { fmtCompact, fmtNum, fmtPct, rangeLabel } from "./format";
import { ProCard } from "./ProScreen";

const Overview = ({ range, nxId, type, compare, isPro, onOpen, reloadKey }: any) => {
    const [data, setData] = useState<any>(null);
    const [error, setError] = useState(false);
    const [loading, setLoading] = useState(true);
    const [showTable, setShowTable] = useState(false);
    const [focus, setFocus] = useChartFocus();

    const load = () => {
        setLoading(true);
        setError(false);
        getSummary(range, nxId, compare && isPro, type).then((res) => {
            if (!res || !res.totals) { setError(true); } else { setData(res); }
            setLoading(false);
        });
    };
    useEffect(load, [range, nxId, type, compare, isPro, reloadKey]);

    if (error) return <ErrorState onRetry={load} />;
    if (loading && !data) return <OverviewSkeleton />;

    const { totals, changes, series, types, top, window } = data;
    const labels = series.map((r: any) => r.date);
    const views = series.map((r: any) => r.views);
    const clicks = series.map((r: any) => r.clicks);
    const ctrSeries = series.map((r: any) => (r.views ? (r.clicks / r.views) * 100 : 0));
    const avg = window.days ? totals.views / window.days : 0;
    const empty = !totals.views && !totals.clicks && !totals.leads;

    return (
        <div className={`nxa-overview ${loading ? "is-refreshing" : ""}`}>
            <section className="nxa-hero nxa-card">
                <div className="nxa-hero-rail">
                    <div className="nxa-eyebrow">{sprintf(__("Views · %s", "notificationx"), rangeLabel(window.token, window).toLowerCase())}</div>
                    <div className="nxa-hero-value nxa-num">{fmtCompact(totals.views)}</div>
                    <Delta value={changes?.views} />
                    {changes && changes.views === null && (
                        <span className="nxa-muted nxa-small">{__("No earlier data to compare with", "notificationx")}</span>
                    )}
                    <dl className="nxa-hero-facts">
                        <div><dt>{__("Clicks", "notificationx")}</dt><dd className="nxa-num">{fmtNum(totals.clicks)}</dd></div>
                        <div><dt>{__("Click-through rate", "notificationx")}</dt><dd className="nxa-num">{fmtPct(totals.ctr)}</dd></div>
                        <div><dt>{__("Avg. views / day", "notificationx")}</dt><dd className="nxa-num">{fmtNum(Math.round(avg))}</dd></div>
                    </dl>
                </div>
                <div className="nxa-hero-chart">
                    <div className="nxa-legend">
                        <LegendToggle focus={focus} onFocus={setFocus} defaultHidden={["ctr"]} items={[
                            { key: "views", label: __("Views", "notificationx"), color: "var(--nxa-brand)", value: fmtNum(totals.views) },
                            { key: "clicks", label: __("Clicks", "notificationx"), color: "var(--nxa-mint)", value: fmtNum(totals.clicks) },
                            { key: "ctr", label: __("CTR", "notificationx"), color: "var(--nxa-blue)", value: fmtPct(totals.ctr) },
                        ]} />
                        <button type="button" className="nxa-link" onClick={() => setShowTable(!showTable)} aria-expanded={showTable}>
                            {showTable ? __("View chart", "notificationx") : __("View data", "notificationx")}
                        </button>
                    </div>
                    {empty ? (
                        <EmptyState title={__("No activity in this period", "notificationx")} text={__("Views and clicks appear here once your notifications are shown to visitors.", "notificationx")} />
                    ) : showTable ? (
                        <div className="nxa-table-wrap nxa-data-table">
                            <table className="nxa-table">
                                <thead><tr><th>{__("Date (UTC)", "notificationx")}</th><th className="num">{__("Views", "notificationx")}</th><th className="num">{__("Clicks", "notificationx")}</th><th className="num">{__("CTR", "notificationx")}</th></tr></thead>
                                <tbody>{series.map((r: any) => <tr key={r.date}><td>{r.date}</td><td className="num">{fmtNum(r.views)}</td><td className="num">{fmtNum(r.clicks)}</td><td className="num">{fmtPct(r.views ? (r.clicks / r.views) * 100 : 0)}</td></tr>)}</tbody>
                            </table>
                        </div>
                    ) : (
                        <AreaChart
                            labels={labels}
                            ariaLabel={__("Views and clicks per day", "notificationx")}
                            {...chartSeries([
                                { key: "views", label: __("Views", "notificationx"), values: views, color: "var(--nxa-brand)" },
                                { key: "clicks", label: __("Clicks", "notificationx"), values: clicks, color: "var(--nxa-mint)" },
                                { key: "ctr", label: __("CTR", "notificationx"), values: ctrSeries, color: "var(--nxa-blue)", format: fmtPct, empty: __("No clicks yet, so CTR is 0% in this period", "notificationx") },
                            ], focus, ["ctr"])}
                        />
                    )}
                </div>
            </section>

            <div className="nxa-kpis">
                <KpiCard icon="eye" tone="brand" label={__("Views", "notificationx")} value={fmtCompact(totals.views)} delta={changes?.views} spark={views} sparkColor="var(--nxa-brand)"
                    hint={__("Counted each time a notification loads on a page.", "notificationx")} />
                <KpiCard icon="click" tone="mint" label={__("Clicks", "notificationx")} value={fmtCompact(totals.clicks)} delta={changes?.clicks} spark={clicks} sparkColor="var(--nxa-mint)" />
                <KpiCard icon="percent" tone="blue" label={__("Click-through rate", "notificationx")} value={fmtPct(totals.ctr)} delta={changes?.ctr} deltaUnit="pp" spark={ctrSeries} sparkColor="var(--nxa-blue)"
                    hint={__("Clicks divided by views.", "notificationx")} />
                <KpiCard icon="leads" tone="amber" label={__("Leads", "notificationx")} value={fmtCompact(totals.leads)} delta={changes?.leads}
                    hint={__("Form submissions from Popup and Exit Intent notifications.", "notificationx")} />
            </div>

            {data.locked ? (
                <ProCard title={__("Find your best notifications", "notificationx")}
                    text={__("See your top notifications, which types perform best, and open any notification for its own trend.", "notificationx")} />
            ) : (
            <div className="nxa-grid">
                <Card className="nxa-col-8" icon="trophy" tone="amber" title={__("Top notifications", "notificationx")} subtitle={__("Ranked by views", "notificationx")}>
                    {top.length ? (
                        <div className="nxa-table-wrap">
                            <table className="nxa-table nxa-table-click">
                                <thead><tr><th>#</th><th>{__("Notification", "notificationx")}</th><th className="num">{__("Views", "notificationx")}</th><th className="num">{__("Clicks", "notificationx")}</th><th className="num">{__("CTR", "notificationx")}</th></tr></thead>
                                <tbody>
                                    {top.map((row: any, i: number) => (
                                        <tr key={row.nx_id} tabIndex={0} onClick={() => onOpen(row.nx_id)} onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && (e.preventDefault(), onOpen(row.nx_id))}>
                                            <td className="nxa-rank"><span className={`nxa-rank-badge ${i < 3 ? "is-podium" : ""}`}>{i + 1}</span></td>
                                            <td><div className="nxa-name"><StatusDot enabled={row.enabled} /><span className="nxa-name-text" title={row.title}>{row.title}</span><TypeBadge label={row.type_label} /></div></td>
                                            <td className="num">{fmtNum(row.views)}</td>
                                            <td className="num">{fmtNum(row.clicks)}</td>
                                            <td className="num"><CtrPill value={row.ctr} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : <EmptyState title={__("No notification had views in this period", "notificationx")} />}
                </Card>
                <Card className="nxa-col-4" icon="layers" tone="brand" title={__("Views by type", "notificationx")} subtitle={__("Which kinds of notification are seen most", "notificationx")}>
                    {types.length ? (
                        <BarList rank valueLabel={__("Views by notification type", "notificationx")} items={types.map((t: any) => ({ key: t.type, label: t.label, value: t.views, pill: <CtrPill value={t.views ? (t.clicks / t.views) * 100 : 0} />, title: `${t.label} · ${fmtPct(t.views ? (t.clicks / t.views) * 100 : 0)} CTR` }))} />
                    ) : <EmptyState title={__("No data yet", "notificationx")} />}
                    <p className="nxa-note">{__("Views count page loads and leave out Popup, Exit Intent and Cookie Notice. Audience shows how often every notification was actually seen.", "notificationx")}</p>
                </Card>
            </div>
            )}
        </div>
    );
};

const OverviewSkeleton = () => (
    <div className="nxa-overview" aria-busy="true">
        <section className="nxa-hero nxa-card">
            <div className="nxa-hero-rail"><Skeleton width={120} /><Skeleton height={44} width={160} /><Skeleton width="80%" /><Skeleton width="70%" /></div>
            <div className="nxa-hero-chart"><Skeleton height={260} /></div>
        </section>
        <div className="nxa-kpis">{[1, 2, 3, 4].map((k) => <div key={k} className="nxa-kpi"><Skeleton width={90} /><Skeleton height={30} width={110} /></div>)}</div>
        <div className="nxa-grid"><div className="nxa-card nxa-col-8"><Skeleton height={220} /></div><div className="nxa-card nxa-col-4"><Skeleton height={220} /></div></div>
    </div>
);

export default Overview;
