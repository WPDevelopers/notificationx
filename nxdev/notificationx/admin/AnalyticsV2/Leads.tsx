import React, { useEffect, useState } from "react";
import { __, _n, sprintf } from "@wordpress/i18n";
import { getLeadsReport } from "./api";
import { AreaChart, BarList, ColumnChart } from "./charts";
import { Card, Chip, EmptyState, ErrorState, Icon, KpiCard, Skeleton, StatusDot } from "./ui";
import { fmtCompact, fmtNum, fmtPct } from "./format";

const WEEKDAYS = [__("Mon", "notificationx"), __("Tue", "notificationx"), __("Wed", "notificationx"), __("Thu", "notificationx"), __("Fri", "notificationx"), __("Sat", "notificationx"), __("Sun", "notificationx")];
const WEEKDAYS_LONG = [__("Monday", "notificationx"), __("Tuesday", "notificationx"), __("Wednesday", "notificationx"), __("Thursday", "notificationx"), __("Friday", "notificationx"), __("Saturday", "notificationx"), __("Sunday", "notificationx")];

/**
 * Leads report: how many form submissions Popup and Exit Intent
 * notifications collect, how well they convert, and where they come from.
 * The entries themselves are listed on the Entries page.
 */
const Leads = ({ range, nxId, type, compare, isPro, reloadKey }: any) => {
    const [data, setData] = useState<any>(null);
    const [error, setError] = useState(false);
    const [loading, setLoading] = useState(true);

    const load = () => {
        setLoading(true);
        setError(false);
        getLeadsReport(range, nxId, type, compare && isPro).then((res) => {
            if (!res || !res.totals) { setError(true); } else { setData(res); }
            setLoading(false);
        });
    };
    useEffect(load, [range, nxId, type, compare, isPro, reloadKey]);

    if (error) return <ErrorState onRetry={load} />;
    if (loading && !data) return <LeadsSkeleton />;

    const { totals, series, weekday, sources, top, change, entries_url } = data;
    const bestDay = weekday.indexOf(Math.max(...weekday));
    const sourceTotal = sources.popup_notification + sources.exit_intent_custom;
    const manage = entries_url ? <a className="nxa-btn nxa-btn-ghost nxa-btn-sm" href={entries_url}><Icon name="arrow" size={14} />{nxId ? __("View entries", "notificationx") : __("View all entries", "notificationx")}</a> : null;

    return (
        <div className={`nxa-leads ${loading ? "is-refreshing" : ""}`}>
            <div className="nxa-kpis">
                <KpiCard icon="leads" tone="amber" label={__("Leads", "notificationx")} value={fmtCompact(totals.leads)} delta={change}
                    spark={series.map((r: any) => r.leads)} sparkColor="var(--nxa-amber)"
                    foot={sprintf(__("About %s a day", "notificationx"), fmtNum(totals.per_day))} />
                <KpiCard icon="percent" tone="mint" label={__("Conversion rate", "notificationx")} value={totals.seen ? fmtPct(totals.conversion) : "—"} meter={totals.conversion}
                    hint={__("Leads divided by the times a Popup or Exit Intent notification was seen.", "notificationx")}
                    foot={totals.seen ? sprintf(__("%1$s leads from %2$s seen", "notificationx"), fmtNum(totals.leads), fmtNum(totals.seen)) : __("Needs Audience data", "notificationx")} />
                <KpiCard icon="trophy" tone="brand" label={__("Top collector", "notificationx")} value={<span className="nxa-kpi-text">{top[0] ? top[0].title : "—"}</span>}
                    foot={top[0] ? sprintf(_n("%s lead", "%s leads", top[0].leads, "notificationx"), fmtNum(top[0].leads)) : __("No leads in this period", "notificationx")} />
                <KpiCard icon="trend" tone="blue" label={__("Busiest day", "notificationx")} value={<span className="nxa-kpi-text">{totals.leads ? WEEKDAYS_LONG[bestDay] : "—"}</span>}
                    foot={totals.leads ? sprintf(__("%s of leads arrive then", "notificationx"), fmtPct((weekday[bestDay] / totals.leads) * 100)) : __("Shows once leads come in", "notificationx")} />
            </div>

            <Card icon="trend" tone="amber" title={__("Leads over time", "notificationx")} subtitle={__("Form submissions per day, in UTC", "notificationx")} action={manage}>
                {totals.leads ? (
                    <AreaChart labels={series.map((r: any) => r.date)} ariaLabel={__("Leads per day", "notificationx")} height={240}
                        series={[{ key: "leads", label: __("Leads", "notificationx"), values: series.map((r: any) => r.leads), color: "var(--nxa-amber)" }]} />
                ) : (
                    <EmptyState title={__("No leads in this period", "notificationx")} text={__("Popup and Exit Intent notifications with a form collect leads. Try a longer date range.", "notificationx")} />
                )}
            </Card>

            <div className="nxa-grid nxa-grid-even">
                <Card className="nxa-col-6" icon="trophy" tone="brand" title={__("Top lead collectors", "notificationx")} subtitle={__("Leads and conversion rate per notification", "notificationx")}>
                    {top.length ? (
                        <BarList rank valueLabel={__("Leads by notification", "notificationx")} items={top.map((r: any) => ({
                            key: String(r.nx_id), value: r.leads, color: "var(--nxa-amber)",
                            title: sprintf(__("%1$s · %2$s seen", "notificationx"), r.title, fmtNum(r.seen)),
                            avatar: <StatusDot enabled={r.enabled} />,
                            label: <span className="nxa-host">{r.title}<span className="nxa-badge">{r.type_label}</span></span>,
                            pill: <span className={`nxa-pill ${r.conversion >= 5 ? "is-high" : r.conversion > 0 ? "is-mid" : "is-zero"}`} title={__("Conversion rate: leads divided by times seen", "notificationx")}>{r.seen ? sprintf(__("%s conv.", "notificationx"), fmtPct(r.conversion)) : "—"}</span>,
                        }))} />
                    ) : <EmptyState title={__("No leads yet", "notificationx")} />}
                </Card>
                <Card className="nxa-col-6" icon="layers" tone="mint" title={__("When and where leads come in", "notificationx")} subtitle={__("By weekday, and Popup vs Exit Intent", "notificationx")}>
                    <ColumnChart ariaLabel={__("Leads by weekday", "notificationx")} items={weekday.map((n: number, i: number) => ({ key: String(i), label: WEEKDAYS[i], value: n }))} />
                    <div className="nxa-split">
                        <div className="nxa-split-bar" role="img" aria-label={sprintf(__("Popup %1$s, Exit Intent %2$s", "notificationx"), fmtNum(sources.popup_notification), fmtNum(sources.exit_intent_custom))}>
                            {sourceTotal ? <>
                                {sources.popup_notification > 0 && <span style={{ flexGrow: sources.popup_notification, background: "var(--nxa-brand)" }} />}
                                {sources.exit_intent_custom > 0 && <span style={{ flexGrow: sources.exit_intent_custom, background: "var(--nxa-pink)" }} />}
                            </> : <span style={{ flexGrow: 1, background: "var(--nxa-grid)" }} />}
                        </div>
                        <div className="nxa-split-legend">
                            <span><Chip icon="layers" tone="brand" size={14} /><span><strong className="nxa-num">{fmtNum(sources.popup_notification)}</strong>{__("Popup", "notificationx")}</span><em className="nxa-num">{sourceTotal ? fmtPct((sources.popup_notification / sourceTotal) * 100) : "—"}</em></span>
                            <span><Chip icon="arrow" tone="pink" size={14} /><span><strong className="nxa-num">{fmtNum(sources.exit_intent_custom)}</strong>{__("Exit Intent", "notificationx")}</span><em className="nxa-num">{sourceTotal ? fmtPct((sources.exit_intent_custom / sourceTotal) * 100) : "—"}</em></span>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    );
};

const LeadsSkeleton = () => (
    <div className="nxa-leads" aria-busy="true">
        <div className="nxa-kpis">{[1, 2, 3, 4].map((k) => <div key={k} className="nxa-kpi"><Skeleton width={90} /><Skeleton height={30} width={110} /></div>)}</div>
        <div className="nxa-card"><Skeleton height={240} /></div>
    </div>
);

export default Leads;
