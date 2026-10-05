import React, { useEffect, useState } from "react";
import { __, _n, sprintf } from "@wordpress/i18n";
import { getAudience } from "./api";
import { AreaChart, BarList, DonutChart } from "./charts";
import { Avatar, Card, Chip, CtrPill, EmptyState, ErrorState, Icon, KpiCard, LegendToggle, Skeleton, Tone, chartSeries, useChartFocus } from "./ui";
import { fmtCompact, fmtDate, fmtNum, fmtPct } from "./format";

const CHANNELS: Record<string, { label: string; color: string; tone: Tone; icon: string; hint: string }> = {
    ai: { label: __("AI assistants", "notificationx"), color: "var(--nxa-brand)", tone: "brand", icon: "sparkle", hint: __("ChatGPT, Perplexity, Gemini…", "notificationx") },
    search: { label: __("Search", "notificationx"), color: "var(--nxa-blue)", tone: "blue", icon: "search", hint: __("Google, Bing, DuckDuckGo…", "notificationx") },
    social: { label: __("Social", "notificationx"), color: "var(--nxa-pink)", tone: "pink", icon: "share", hint: __("Facebook, X, LinkedIn…", "notificationx") },
    referral: { label: __("Other websites", "notificationx"), color: "var(--nxa-mint)", tone: "mint", icon: "link", hint: __("Links on other sites", "notificationx") },
    email: { label: __("Email", "notificationx"), color: "var(--nxa-amber)", tone: "amber", icon: "mail", hint: __("Newsletters and webmail", "notificationx") },
    direct: { label: __("Direct", "notificationx"), color: "var(--nxa-ink-3)", tone: "slate", icon: "arrow", hint: __("Typed or bookmarked", "notificationx") },
    internal: { label: __("Your own pages", "notificationx"), color: "var(--nxa-line-strong)", tone: "slate", icon: "home", hint: __("Moved between your pages", "notificationx") },
};

/** Brand colors of AI assistants, for their initials. */
const AI_COLORS: Record<string, string> = {
    ChatGPT: "#10a37f", Claude: "#d97757", Gemini: "#4285f4", Perplexity: "#20808d", Copilot: "#0078d4",
    DeepSeek: "#4d6bfe", Grok: "#1f2937", "Meta AI": "#0866ff", Mistral: "#fa520f", "You.com": "#7c3aed", Phind: "#0f766e", Poe: "#5d5cde",
};

const DEVICES: Record<string, string> = {
    desktop: __("Desktop", "notificationx"),
    tablet: __("Tablet", "notificationx"),
    mobile: __("Mobile", "notificationx"),
};

const DEVICE_COLORS: Record<string, string> = {
    desktop: "var(--nxa-brand)",
    mobile: "var(--nxa-mint)",
    tablet: "var(--nxa-amber)",
};

const DEVICE_ICONS: Record<string, string> = {
    desktop: "M3 5h18v11H3zM8 20h8M12 16v4",
    tablet: "M6 3h12v18H6zM11 18h2",
    mobile: "M8 3h8v18H8zM11 18h2",
};

/** "BD" → 🇧🇩. Regional indicator symbols render as a flag on most systems. */
const flag = (code: string) =>
    /^[A-Z]{2}$/.test(code) ? String.fromCodePoint(...code.split("").map((c) => 0x1f1a5 + c.charCodeAt(0))) : "🌐";

const share = (part: number, total: number) => (total ? (part / total) * 100 : 0);

const Audience = ({ range, nxId, type, reloadKey }: any) => {
    const [data, setData] = useState<any>(null);
    const [error, setError] = useState(false);
    const [loading, setLoading] = useState(true);
    const [focus, setFocus] = useChartFocus();
    const extraLines = ["closes", "hovers", "ctr"]; // Off until picked.

    const load = () => {
        setLoading(true);
        setError(false);
        getAudience(range, nxId, type).then((res) => {
            if (!res || !res.totals) { setError(true); } else { setData(res); }
            setLoading(false);
        });
    };
    useEffect(load, [range, nxId, type, reloadKey]);

    if (error) return <ErrorState onRetry={load} />;
    if (loading && !data) return <AudienceSkeleton />;

    const { totals, series, devices, countries, pages, sources, channels, ai, since, retention } = data;

    if (!since) {
        return (
            <div className="nxa-card nxa-audience-empty">
                <EmptyState
                    title={__("Audience data starts now", "notificationx")}
                    text={__("NotificationX now records when a visitor actually sees, clicks or closes a notification, on every notification type. Open a page with an active notification and this report fills in within a few minutes.", "notificationx")}
                />
                <PrivacyNote />
            </div>
        );
    }

    const noData = !totals.seen && !totals.clicks;
    const sinceInWindow = since > data.window.start;
    const clicksTitle = (r: any, name: string) => `${name} · ${sprintf(_n("%s click", "%s clicks", r.clicks, "notificationx"), fmtNum(r.clicks))} · ${fmtPct(r.ctr)} CTR`;

    return (
        <div className={`nxa-audience ${loading ? "is-refreshing" : ""}`}>
            {sinceInWindow && (
                <p className="nxa-banner">
                    <Icon name="sparkle" size={16} />
                    {sprintf(__("Audience tracking started on %s, so earlier days in this range are empty.", "notificationx"), fmtDate(since, { month: "long", day: "numeric", year: "numeric" }))}
                </p>
            )}

            <div className="nxa-kpis nxa-kpis-6">
                <KpiCard icon="leads" tone="blue" label={__("Visitors reached", "notificationx")} value={fmtCompact(totals.visitors)}
                    hint={__("Unique visitors each day who saw a notification, added up over the range. NotificationX can't recognise a visitor from one day to the next, so a returning visitor counts once per day.", "notificationx")}
                    foot={totals.visitors ? sprintf(__("Each saw about %s notifications", "notificationx"), fmtNum(totals.per_visitor)) : __("No visitors in this period", "notificationx")} />
                <KpiCard icon="eye" tone="brand" label={__("Seen", "notificationx")} value={fmtCompact(totals.seen)} spark={series.map((r: any) => r.seen)} sparkColor="var(--nxa-brand)"
                    foot={sprintf(__("About %s a day", "notificationx"), fmtNum(Math.round(totals.seen / Math.max(1, data.window.days))))}
                    hint={__("At least half of the notification was on screen for a second.", "notificationx")} />
                <KpiCard icon="hover" tone="amber" label={__("Hovered", "notificationx")} value={fmtPct(totals.engagement)} meter={totals.engagement}
                    hint={__("Engagement rate: how often visitors rested the mouse on a notification for half a second, out of the times it was seen. Not counted on touch screens.", "notificationx")}
                    foot={sprintf(_n("%1$s hover from %2$s seen", "%1$s hovers from %2$s seen", totals.hovers, "notificationx"), fmtNum(totals.hovers), fmtNum(totals.seen))} />
                <KpiCard icon="click" tone="mint" label={__("Clicks", "notificationx")} value={fmtCompact(totals.clicks)} spark={series.map((r: any) => r.clicks)} sparkColor="var(--nxa-mint)"
                    foot={sprintf(__("%s of them from AI assistants", "notificationx"), fmtNum(ai.clicks))} />
                <KpiCard icon="percent" tone="blue" label={__("CTR", "notificationx")} value={fmtPct(totals.ctr)} meter={totals.ctr}
                    hint={__("Click-through rate: clicks divided by times seen.", "notificationx")} foot={sprintf(__("%1$s clicks from %2$s seen", "notificationx"), fmtNum(totals.clicks), fmtNum(totals.seen))} />
                <KpiCard icon="close" tone="pink" label={__("Closed", "notificationx")} value={fmtCompact(totals.closes)} meter={totals.close_rate}
                    foot={sprintf(__("%s of the times seen", "notificationx"), fmtPct(totals.close_rate))} />
            </div>

            {noData ? (
                <div className="nxa-card"><EmptyState title={__("No activity in this period", "notificationx")} text={__("Try a longer date range, or check that your notifications are enabled.", "notificationx")} /></div>
            ) : (
                <>
                    <Card icon="trend" tone="brand" title={__("Seen and clicked", "notificationx")} subtitle={__("Per day, in UTC", "notificationx")}
                        action={<LegendToggle focus={focus} onFocus={setFocus} defaultHidden={extraLines} items={[
                            { key: "seen", label: __("Seen", "notificationx"), color: "var(--nxa-brand)", value: fmtNum(totals.seen) },
                            { key: "clicks", label: __("Clicks", "notificationx"), color: "var(--nxa-mint)", value: fmtNum(totals.clicks) },
                            { key: "hovers", label: __("Hovered", "notificationx"), color: "var(--nxa-amber)", value: fmtNum(totals.hovers) },
                            { key: "closes", label: __("Closed", "notificationx"), color: "var(--nxa-pink)", value: fmtNum(totals.closes) },
                            { key: "ctr", label: __("CTR", "notificationx"), color: "var(--nxa-blue)", value: fmtPct(totals.ctr) },
                        ]} />}>
                        <AreaChart
                            labels={series.map((r: any) => r.date)}
                            ariaLabel={__("Times seen and clicks per day", "notificationx")}
                            height={240}
                            {...chartSeries([
                                { key: "seen", label: __("Seen", "notificationx"), values: series.map((r: any) => r.seen), color: "var(--nxa-brand)" },
                                { key: "clicks", label: __("Clicks", "notificationx"), values: series.map((r: any) => r.clicks), color: "var(--nxa-mint)" },
                                { key: "hovers", label: __("Hovered", "notificationx"), values: series.map((r: any) => r.hovers || 0), color: "var(--nxa-amber)" },
                                { key: "closes", label: __("Closed", "notificationx"), values: series.map((r: any) => r.closes || 0), color: "var(--nxa-pink)" },
                                { key: "ctr", label: __("CTR", "notificationx"), values: series.map((r: any) => (r.seen ? (r.clicks / r.seen) * 100 : 0)), color: "var(--nxa-blue)", format: fmtPct, empty: __("No clicks yet, so CTR is 0% in this period", "notificationx") },
                            ], focus, extraLines)}
                        />
                    </Card>

                    <div className="nxa-grid nxa-grid-even">
                        <AiCard ai={ai} className="nxa-col-6" />
                        <Card className="nxa-col-6" icon="compass" tone="blue" title={__("Where visitors came from", "notificationx")} subtitle={__("Share of times seen, by channel", "notificationx")}>
                            <ChannelBar channels={channels} total={totals.seen} />
                            <ul className="nxa-channels">
                                {channels.map((c: any) => {
                                    const ch = CHANNELS[c.key] || { ...CHANNELS.referral, label: c.key };
                                    const pct = share(c.seen, totals.seen);
                                    return (
                                        <li key={c.key} style={{ ["--nxa-c" as any]: ch.color }}>
                                            <Chip icon={ch.icon} tone={ch.tone} size={16} />
                                            <div className="nxa-channel-main">
                                                <div className="nxa-channel-row"><strong>{ch.label}</strong><span className="nxa-num">{fmtNum(c.seen)}</span></div>
                                                <div className="nxa-channel-row nxa-channel-sub">
                                                    <span className="nxa-muted">{ch.hint}</span>
                                                    <span className="nxa-num nxa-muted">{fmtPct(pct)}</span>
                                                </div>
                                            </div>
                                            <CtrPill value={c.ctr} />
                                        </li>
                                    );
                                })}
                            </ul>
                        </Card>
                    </div>

                    <div className="nxa-grid nxa-grid-even">
                        <Card className="nxa-col-6" icon="devices" tone="mint" title={__("Devices", "notificationx")} subtitle={__("Where your notifications were seen", "notificationx")}>
                            {devices.some((d: any) => d.seen) ? (
                                <DonutChart
                                    size={168}
                                    ariaLabel={devices.map((d: any) => `${DEVICES[d.key] || d.key} ${fmtPct(share(d.seen, totals.seen))}`).join(", ")}
                                    centerLabel={__("times seen", "notificationx")}
                                    items={["desktop", "mobile", "tablet"].map((key) => {
                                        const row = devices.find((d: any) => d.key === key) || { seen: 0, ctr: 0 };
                                        return {
                                            key,
                                            label: DEVICES[key],
                                            value: row.seen,
                                            color: DEVICE_COLORS[key],
                                            icon: <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d={DEVICE_ICONS[key]} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" /></svg>,
                                            meta: sprintf(__("%1$s seen · %2$s CTR", "notificationx"), fmtNum(row.seen), fmtPct(row.ctr)),
                                        };
                                    })}
                                />
                            ) : <EmptyState title={__("No data yet", "notificationx")} />}
                            <DeviceInsight devices={devices} />
                        </Card>
                        <Card className="nxa-col-6" icon="globe" tone="blue" title={__("Countries", "notificationx")} subtitle={__("Top countries by times seen", "notificationx")}>
                            {countries.length && !(countries.length === 1 && countries[0].key === "") ? (
                                <BarList rank total={totals.seen} valueLabel={__("Times seen by country", "notificationx")} items={countries.slice(0, 7).map((c: any) => ({
                                    key: c.key || "unknown", label: c.label, value: c.seen, color: "var(--nxa-blue)",
                                    avatar: <span className="nxa-flag" aria-hidden="true">{flag(c.key)}</span>,
                                    pill: <CtrPill value={c.ctr} />, title: clicksTitle(c, c.label),
                                }))} />
                            ) : (
                                <EmptyState title={__("Country not available", "notificationx")} text={__("Countries appear when your host or CDN (for example Cloudflare) tells WordPress where a visitor is. NotificationX never sends visitor IPs to a lookup service for this.", "notificationx")} />
                            )}
                        </Card>
                    </div>

                    <div className="nxa-grid nxa-grid-even">
                        <Card className="nxa-col-6" icon="page" tone="amber" title={__("Top pages", "notificationx")} subtitle={__("Where notifications were seen", "notificationx")}>
                            {pages.length ? (
                                <BarList rank total={totals.seen} valueLabel={__("Times seen by page", "notificationx")} items={pages.slice(0, 7).map((r: any) => ({
                                    key: r.key, title: clicksTitle(r, r.key), value: r.seen, color: "var(--nxa-amber)", pill: <CtrPill value={r.ctr} />,
                                    label: <span className="nxa-path">{r.key === "/" ? __("Home page", "notificationx") : r.key}</span>,
                                }))} />
                            ) : <EmptyState title={__("No data yet", "notificationx")} />}
                        </Card>
                        <Card className="nxa-col-6" icon="link" tone="pink" title={__("Top referrers", "notificationx")} subtitle={__("Websites that sent visitors", "notificationx")}>
                            {sources.length ? (
                                <BarList rank total={totals.seen} valueLabel={__("Times seen by referrer", "notificationx")} items={sources.slice(0, 7).map((r: any) => ({
                                    key: r.key, title: clicksTitle(r, r.key), value: r.seen, color: "var(--nxa-pink)", pill: <CtrPill value={r.ctr} />,
                                    avatar: <Avatar text={r.key} color={r.ai ? AI_COLORS[r.ai] : undefined} />,
                                    label: <span className="nxa-host">{r.key}{r.ai && <span className="nxa-badge nxa-badge-ai"><Icon name="sparkle" size={11} />{r.ai}</span>}</span>,
                                }))} />
                            ) : <EmptyState title={__("No referrers yet", "notificationx")} text={__("Visitors in this period arrived directly or from your own pages.", "notificationx")} />}
                        </Card>
                    </div>
                </>
            )}
            <PrivacyNote retention={retention} />
        </div>
    );
};

const AiCard = ({ ai, className }: any) => (
    <section className={`nxa-card nxa-ai ${className}`}>
        <div className="nxa-ai-glow" aria-hidden="true" />
        <header className="nxa-card-head">
            <div className="nxa-card-title">
                <span className="nxa-chip nxa-chip-ai" aria-hidden="true"><Icon name="sparkle" size={16} /></span>
                <div>
                    <h3>{__("Visitors from AI assistants", "notificationx")}</h3>
                    <p className="nxa-card-sub">{__("People who arrived from an AI answer", "notificationx")}</p>
                </div>
            </div>
        </header>
        <div className="nxa-card-body">
            <div className="nxa-ai-hero">
                <div>
                    <div className="nxa-ai-value nxa-num">{fmtCompact(ai.seen)}</div>
                    <div className="nxa-muted nxa-small">{__("times seen", "notificationx")}</div>
                </div>
                <div className="nxa-ai-facts">
                    <div><span>{__("Share of all", "notificationx")}</span><strong className="nxa-num">{fmtPct(ai.share)}</strong></div>
                    <div><span>{__("CTR", "notificationx")}</span><strong className="nxa-num">{fmtPct(ai.ctr)}</strong></div>
                </div>
            </div>
            {ai.assistants.length ? (
                <BarList total={ai.seen} valueLabel={__("Times seen by AI assistant", "notificationx")} items={ai.assistants.map((a: any) => ({
                    key: a.key, label: a.key, value: a.seen, color: AI_COLORS[a.key] || "var(--nxa-brand)",
                    avatar: <Avatar text={a.key} color={AI_COLORS[a.key]} />,
                    title: `${a.key} · ${sprintf(_n("%s click", "%s clicks", a.clicks, "notificationx"), fmtNum(a.clicks))}`,
                    pill: <CtrPill value={a.seen ? (a.clicks / a.seen) * 100 : 0} />,
                }))} />
            ) : (
                <p className="nxa-note">{__("No visits from ChatGPT, Perplexity, Gemini, Copilot or Claude in this period. They show up here as soon as one of them sends you a visitor.", "notificationx")}</p>
            )}
        </div>
    </section>
);

/** One line naming the device whose visitors click most, once it has enough views to mean something. */
const DeviceInsight = ({ devices }: any) => {
    const ranked = devices.filter((d: any) => d.seen >= 3 && DEVICES[d.key]).sort((a: any, b: any) => b.ctr - a.ctr);
    if (ranked.length < 2 || !ranked[0].ctr) return null;
    return (
        <p className="nxa-insight">
            <Icon name="sparkle" size={14} />
            {sprintf(__("%1$s visitors click most: %2$s of the times they see a notification.", "notificationx"), DEVICES[ranked[0].key], fmtPct(ranked[0].ctr))}
        </p>
    );
};

const ChannelBar = ({ channels, total }: any) => (
    <div className="nxa-stack" role="img" aria-label={channels.map((c: any) => `${(CHANNELS[c.key] || { label: c.key }).label} ${fmtPct(share(c.seen, total))}`).join(", ")}>
        {channels.filter((c: any) => c.seen).map((c: any) => (
            <span key={c.key} style={{ flexGrow: c.seen, background: (CHANNELS[c.key] || CHANNELS.referral).color }} title={`${(CHANNELS[c.key] || { label: c.key }).label}: ${fmtPct(share(c.seen, total))}`} />
        ))}
    </div>
);

const PrivacyNote = ({ retention }: { retention?: number }) => (
    <p className="nxa-privacy">
        <span className="nxa-privacy-icon"><svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6zM9 12l2 2 4-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" /></svg></span>
        {__("Counted without cookies. No IP address is stored, and visitors can't be recognised from one day to the next.", "notificationx")}
        {retention !== undefined && " " + (retention ? sprintf(__("Kept for %d days.", "notificationx"), retention) : __("Kept until you reset it.", "notificationx"))}
    </p>
);

const AudienceSkeleton = () => (
    <div className="nxa-audience" aria-busy="true">
        <div className="nxa-kpis nxa-kpis-6">{[1, 2, 3, 4, 5, 6].map((k) => <div key={k} className="nxa-kpi"><Skeleton width={90} /><Skeleton height={30} width={110} /></div>)}</div>
        <div className="nxa-card"><Skeleton height={220} /></div>
        <div className="nxa-grid nxa-grid-even"><div className="nxa-card nxa-col-6"><Skeleton height={200} /></div><div className="nxa-card nxa-col-6"><Skeleton height={200} /></div></div>
    </div>
);

export default Audience;
