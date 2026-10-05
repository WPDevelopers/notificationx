import React, { useEffect, useMemo, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { fmtDate, fmtNum } from "./format";

/** Track an element's width so SVG charts stay crisp at any size. */
const useWidth = (fallback = 320) => {
    const ref = useRef<HTMLDivElement>(null);
    const [width, setWidth] = useState(fallback);
    useEffect(() => {
        if (!ref.current) return;
        const el = ref.current;
        setWidth(el.clientWidth || fallback);
        if (typeof ResizeObserver === "undefined") return;
        const ro = new ResizeObserver((entries) => {
            const w = Math.round(entries[0].contentRect.width);
            if (w) setWidth(w);
        });
        ro.observe(el);
        return () => ro.disconnect();
    }, []);
    return [ref, width] as const;
};

/**
 * Axis top and step for counts: 4 equal steps of 1, 2 or 5 x 10^n, so tick
 * labels are whole numbers (no "187.5").
 */
const niceScale = (max: number) => {
    if (max <= 4) return { top: 4, step: 1 };
    const raw = max / 4;
    const exp = Math.pow(10, Math.floor(Math.log10(raw)));
    const f = raw / exp;
    // 2.5 only from tens up, so ticks stay whole numbers.
    const step = (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 && exp >= 10 ? 2.5 : f <= 5 ? 5 : 10) * exp;
    // Stop at the first tick above the data: 87 → 0..100, not 0..200.
    return { top: Math.ceil(max / step) * step, step };
};

let gradientId = 0;

/**
 * Smooth line through points without overshooting (monotone cubic,
 * Fritsch–Carlson), so a curve never dips below zero between two zero days.
 */
export const smoothPath = (pts: number[][]) => {
    const n = pts.length;
    if (n < 3) return pts.map((p, i) => `${i ? "L" : "M"}${p[0].toFixed(1)},${p[1].toFixed(1)}`).join(" ");
    const dx: number[] = [], m: number[] = [];
    for (let i = 0; i < n - 1; i++) {
        dx[i] = pts[i + 1][0] - pts[i][0];
        m[i] = (pts[i + 1][1] - pts[i][1]) / (dx[i] || 1);
    }
    const t: number[] = [m[0]];
    for (let i = 1; i < n - 1; i++) t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2;
    t[n - 1] = m[n - 2];
    for (let i = 0; i < n - 1; i++) {
        if (m[i] === 0) { t[i] = 0; t[i + 1] = 0; continue; }
        const a = t[i] / m[i], b = t[i + 1] / m[i], h = a * a + b * b;
        if (h > 9) { const k = 3 / Math.sqrt(h); t[i] = k * a * m[i]; t[i + 1] = k * b * m[i]; }
    }
    let d = `M${pts[0][0].toFixed(1)},${pts[0][1].toFixed(1)}`;
    for (let i = 0; i < n - 1; i++) {
        const c = dx[i] / 3;
        d += ` C${(pts[i][0] + c).toFixed(1)},${(pts[i][1] + c * t[i]).toFixed(1)} ${(pts[i + 1][0] - c).toFixed(1)},${(pts[i + 1][1] - c * t[i + 1]).toFixed(1)} ${pts[i + 1][0].toFixed(1)},${pts[i + 1][1].toFixed(1)}`;
    }
    return d;
};

export type Series = { key: string; label: string; values: number[]; color: string; dashed?: boolean; area?: boolean; format?: (v: number) => string; empty?: string };

/**
 * Area/line chart for daily series. Colors are CSS variables so dark mode
 * restyles the chart with the rest of the page.
 */
/**
 * Pass every series and the keys to hide: the y-axis is scaled on all of
 * them, so switching a line off never re-scales the chart (which made the
 * same data look different).
 */
export const AreaChart = ({ labels, series: allSeries, height = 260, ariaLabel, hidden = [] }: { labels: string[]; series: Series[]; height?: number; ariaLabel: string; hidden?: string[] }) => {
    const series = allSeries.filter((s) => !hidden.includes(s.key));
    const [ref, width] = useWidth();
    const [hover, setHover] = useState<number | null>(null);
    const ids = useMemo(() => allSeries.map(() => `nxa-grad-${++gradientId}`), [allSeries.length]);
    const pad = { top: 12, right: 12, bottom: 28, left: 44 };
    const w = Math.max(width, 200);
    const innerW = w - pad.left - pad.right;
    const innerH = height - pad.top - pad.bottom;
    const n = labels.length;
    const { top: max, step: tickStep } = niceScale(Math.max(1, ...allSeries.flatMap((s) => s.values)));
    // Every visible line is all zero: say so instead of drawing a flat line.
    const allZero = series.length > 0 && series.every((s) => s.values.every((v) => !v));
    // A percent line (CTR) labels its axis in percent.
    const axisFormat = series.length && series.every((s) => s.format === series[0].format) && series[0].format ? (t: number) => series[0].format!(t).replace(/\.0%$/, "%") : fmtNum;
    const x = (i: number) => pad.left + (n <= 1 ? innerW / 2 : (i / (n - 1)) * innerW);
    const y = (v: number) => pad.top + innerH - (v / max) * innerH;
    const ticks = allZero ? [0] : Array.from({ length: Math.round(max / tickStep) + 1 }, (_, i) => i * tickStep);
    // One date label per ~72px so they never overlap on narrow charts.
    const maxLabels = Math.max(2, Math.floor(innerW / 72));
    const step = Math.max(1, Math.ceil(n / maxLabels));

    const path = (values: number[]) => smoothPath(values.map((v, i) => [x(i), y(v)]));

    const onMove = (e: React.MouseEvent<SVGRectElement>) => {
        const rect = (e.target as SVGRectElement).getBoundingClientRect();
        const rel = e.clientX - rect.left;
        const i = n <= 1 ? 0 : Math.round((rel / rect.width) * (n - 1));
        setHover(Math.max(0, Math.min(n - 1, i)));
    };

    return (
        <div className="nxa-chart" ref={ref}>
            <svg width={w} height={height} role="img" aria-label={ariaLabel}>
                <defs>
                    {series.map((s, i) => (
                        <linearGradient key={s.key} id={ids[allSeries.indexOf(s)]} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor={s.color} stopOpacity="0.28" />
                            <stop offset="100%" stopColor={s.color} stopOpacity="0" />
                        </linearGradient>
                    ))}
                </defs>
                {ticks.map((t) => (
                    <g key={t}>
                        <line className="nxa-gridline" x1={pad.left} x2={w - pad.right} y1={y(t)} y2={y(t)} />
                        <text className="nxa-axis" x={pad.left - 8} y={y(t)} dy="0.32em" textAnchor="end">{axisFormat(t)}</text>
                    </g>
                ))}
                {labels.map((l, i) => (i % step === 0 || i === n - 1) && (n - 1 - i >= step || i === n - 1) ? (
                    <text key={l} className="nxa-axis" x={x(i)} y={height - 8} textAnchor={i === 0 ? "start" : i === n - 1 ? "end" : "middle"}>{fmtDate(l)}</text>
                ) : null)}
                {series.map((s, i) => (
                    <g key={s.key}>
                        {s.area !== false && n > 1 && (
                            <path d={`${path(s.values)} L${x(n - 1)},${y(0)} L${x(0)},${y(0)} Z`} fill={`url(#${ids[allSeries.indexOf(s)]})`} />
                        )}
                        <path d={path(s.values)} fill="none" stroke={s.color} strokeWidth={s.dashed ? 1.8 : 2.6} strokeDasharray={s.dashed ? "5 4" : undefined} strokeLinejoin="round" strokeLinecap="round" />
                    </g>
                ))}
                {hover !== null && (
                    <g>
                        <line className="nxa-crosshair" x1={x(hover)} x2={x(hover)} y1={pad.top} y2={pad.top + innerH} />
                        {series.map((s) => <circle key={s.key} cx={x(hover)} cy={y(s.values[hover] || 0)} r="4" fill={s.color} stroke="var(--nxa-surface)" strokeWidth="2" />)}
                    </g>
                )}
                <rect x={pad.left} y={pad.top} width={innerW} height={innerH} fill="transparent" onMouseMove={onMove} onMouseLeave={() => setHover(null)} />
            </svg>
            {allZero && (
                <div className="nxa-chart-empty" role="note">
                    {series.length === 1
                        ? series[0].empty || sprintf(__("No %s in this period", "notificationx"), series[0].label.toLowerCase())
                        : __("Nothing recorded in this period", "notificationx")}
                </div>
            )}
            {hover !== null && (
                <div className="nxa-tooltip" style={{ left: Math.min(Math.max(x(hover), 90), w - 90), top: 4 }}>
                    <div className="nxa-tooltip-date">{fmtDate(labels[hover], { weekday: "short", month: "short", day: "numeric" })}</div>
                    {series.map((s) => (
                        <div key={s.key} className="nxa-tooltip-row">
                            <span className="nxa-dot" style={{ background: s.color }} />
                            {s.label}<strong>{(s.format || fmtNum)(s.values[hover] || 0)}</strong>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

/** Small trend line for KPI cards and table rows. */
export const Sparkline = ({ values, color = "var(--nxa-brand)", width = 96, height = 30 }: { values: number[]; color?: string; width?: number; height?: number }) => {
    const id = useMemo(() => `nxa-spark-${++gradientId}`, []);
    if (!values || values.length < 2) return <svg width={width} height={height} aria-hidden="true" />;
    const max = Math.max(1, ...values);
    const step = width / (values.length - 1);
    const pts = values.map((v, i) => [i * step, height - 3 - (v / max) * (height - 6)]);
    const d = smoothPath(pts);
    return (
        <svg width={width} height={height} aria-hidden="true" className="nxa-spark">
            <defs>
                <linearGradient id={id} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={color} stopOpacity="0.25" />
                    <stop offset="100%" stopColor={color} stopOpacity="0" />
                </linearGradient>
            </defs>
            <path d={`${d} L${width},${height} L0,${height} Z`} fill={`url(#${id})`} />
            <path d={d} fill="none" stroke={color} strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round" />
        </svg>
    );
};

export type RankItem = { key: string; label: React.ReactNode; value: number; meta?: React.ReactNode; pill?: React.ReactNode; avatar?: React.ReactNode; color?: string; title?: string };

/**
 * Ranked rows: avatar, label, value, share of the total and a slim bar.
 * The leader is emphasised; the rest stay quiet so the order reads at a glance.
 */
export const BarList = ({ items, valueLabel, total, rank = false }: { items: RankItem[]; valueLabel?: string; total?: number; rank?: boolean }) => {
    const max = Math.max(1, ...items.map((i) => i.value));
    const sum = total || items.reduce((a, i) => a + i.value, 0);
    return (
        <ol className={`nxa-ranklist ${rank ? "has-rank" : ""}`} aria-label={valueLabel}>
            {items.map((item, idx) => (
                <li key={item.key} className={idx === 0 ? "is-top" : ""} title={item.title}>
                    {rank && <span className="nxa-rank-n nxa-num">{idx + 1}</span>}
                    {item.avatar && <span className="nxa-rank-avatar">{item.avatar}</span>}
                    <div className="nxa-rank-main">
                        <div className="nxa-rank-row">
                            <span className="nxa-rank-label">{item.label}</span>
                            <span className="nxa-rank-value nxa-num">{fmtNum(item.value)}</span>
                        </div>
                        <div className="nxa-rank-row nxa-rank-sub">
                            <span className="nxa-rank-track"><span style={{ width: `${(item.value / max) * 100}%`, background: item.color }} /></span>
                            <span className="nxa-rank-share nxa-num">{sum ? `${((item.value / sum) * 100).toFixed(1)}%` : "—"}</span>
                            {item.pill}
                        </div>
                        {item.meta && <div className="nxa-rank-meta">{item.meta}</div>}
                    </div>
                </li>
            ))}
        </ol>
    );
};

export type DonutItem = { key: string; label: string; value: number; color: string; meta?: React.ReactNode; icon?: React.ReactNode };

/**
 * Donut chart with the total (or the hovered slice) in the middle and a
 * legend that doubles as the accessible data table. Slices are stroked
 * circles, so they need no path maths and scale cleanly.
 */
export const DonutChart = ({ items, centerLabel, ariaLabel, size = 196 }: { items: DonutItem[]; centerLabel: string; ariaLabel: string; size?: number }) => {
    const [active, setActive] = useState<string | null>(null);
    const total = items.reduce((sum, i) => sum + i.value, 0);
    const stroke = 26;
    // Leave room for the hovered slice, which is drawn 6px thicker.
    const r = (size - stroke - 8) / 2;
    const c = 2 * Math.PI * r;
    const shown = items.filter((i) => i.value > 0);
    const gap = shown.length > 1 ? 3 : 0;
    let offset = 0;
    const current = active ? items.find((i) => i.key === active) : null;
    const pct = (v: number) => (total ? (v / total) * 100 : 0);
    const mid = size / 2;

    return (
        <div className="nxa-donut">
            <div className="nxa-donut-figure" style={{ width: size, height: size }}>
                <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} role="img" aria-label={ariaLabel}>
                    <circle cx={mid} cy={mid} r={r} fill="none" stroke="var(--nxa-grid)" strokeWidth={stroke} />
                    {shown.map((item) => {
                        const len = (item.value / total) * c;
                        const dash = Math.max(0.5, len - gap);
                        const el = (
                            <circle key={item.key} cx={mid} cy={mid} r={r} fill="none" stroke={item.color}
                                strokeWidth={active === item.key ? stroke + 6 : stroke}
                                strokeDasharray={`${dash} ${c - dash}`} strokeDashoffset={-offset}
                                transform={`rotate(-90 ${mid} ${mid})`}
                                className={`nxa-donut-slice ${active && active !== item.key ? "is-dim" : ""}`}
                                onMouseEnter={() => setActive(item.key)} onMouseLeave={() => setActive(null)} />
                        );
                        offset += len;
                        return el;
                    })}
                    <circle cx={mid} cy={mid} r={r - stroke / 2 - 6} fill="var(--nxa-surface-2)" />
                </svg>
                <div className="nxa-donut-center" aria-hidden="true">
                    {current?.icon && <span className="nxa-donut-center-icon" style={{ color: current.color }}>{current.icon}</span>}
                    <strong className="nxa-num">{current ? `${pct(current.value).toFixed(1)}%` : fmtNum(total)}</strong>
                    <span>{current ? current.label : centerLabel}</span>
                </div>
            </div>
            <ul className="nxa-donut-legend">
                {items.map((item) => (
                    <li key={item.key} className={active === item.key ? "is-active" : ""} tabIndex={0}
                        style={{ ["--nxa-c" as any]: item.color }}
                        onMouseEnter={() => setActive(item.key)} onMouseLeave={() => setActive(null)}
                        onFocus={() => setActive(item.key)} onBlur={() => setActive(null)}>
                        <span className="nxa-donut-chip" aria-hidden="true">{item.icon}</span>
                        <div className="nxa-donut-text">
                            <div className="nxa-donut-row">
                                <span className="nxa-donut-label">{item.label}</span>
                                <strong className="nxa-num">{pct(item.value).toFixed(1)}%</strong>
                            </div>
                            <span className="nxa-donut-bar"><span style={{ width: `${pct(item.value)}%` }} /></span>
                            {item.meta && <em className="nxa-num">{item.meta}</em>}
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
};

/** Vertical bars with a label under each, the highest one emphasised. */
export const ColumnChart = ({ items, ariaLabel, height = 150 }: { items: { key: string; label: string; value: number }[]; ariaLabel: string; height?: number }) => {
    const max = Math.max(1, ...items.map((i) => i.value));
    const top = items.reduce((best, i) => (i.value > best.value ? i : best), items[0]);
    return (
        <div className="nxa-columns" role="img" aria-label={ariaLabel} style={{ height }}>
            {items.map((item) => (
                <div key={item.key} className={`nxa-column ${item.value && item === top ? "is-top" : ""}`} title={`${item.label}: ${fmtNum(item.value)}`}>
                    <span className="nxa-column-value nxa-num">{item.value ? fmtNum(item.value) : ""}</span>
                    <span className="nxa-column-bar" style={{ height: `${Math.max(item.value ? 6 : 2, (item.value / max) * 100)}%` }} />
                    <span className="nxa-column-label">{item.label}</span>
                </div>
            ))}
        </div>
    );
};
