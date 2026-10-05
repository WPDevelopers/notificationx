import React, { useEffect, useMemo, useState } from "react";
import { __, _n, sprintf } from "@wordpress/i18n";
import { getNotifications } from "./api";
import { Card, EmptyState, ErrorState, Skeleton, StatusDot, TypeBadge } from "./ui";
import { fmtNum, fmtPct } from "./format";

const PER_PAGE = 20;
type SortKey = "title" | "views" | "seen" | "clicks" | "ctr" | "leads";

const Notifications = ({ range, onOpen, reloadKey }: any) => {
    const [rows, setRows] = useState<any[] | null>(null);
    const [error, setError] = useState(false);
    const [search, setSearch] = useState("");
    const [type, setType] = useState("");
    const [status, setStatus] = useState("");
    const [sort, setSort] = useState<{ key: SortKey; dir: 1 | -1 }>({ key: "views", dir: -1 });
    const [page, setPage] = useState(1);

    const load = () => {
        setError(false);
        getNotifications(range).then((res) => {
            if (!res || !Array.isArray(res.rows)) { setError(true); return; }
            setRows(res.rows);
        });
    };
    useEffect(load, [range, reloadKey]);
    useEffect(() => setPage(1), [search, type, status, range]);

    const types = useMemo(() => {
        const map: Record<string, string> = {};
        (rows || []).forEach((r) => { map[r.type] = r.type_label; });
        return Object.keys(map).map((k) => ({ value: k, label: map[k] })).sort((a, b) => a.label.localeCompare(b.label));
    }, [rows]);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        return (rows || [])
            .filter((r) => !q || r.title.toLowerCase().includes(q) || String(r.nx_id) === q)
            .filter((r) => !type || r.type === type)
            .filter((r) => !status || (status === "on" ? r.enabled : !r.enabled))
            .sort((a, b) => {
                const av = a[sort.key], bv = b[sort.key];
                const cmp = typeof av === "string" ? av.localeCompare(bv) : av - bv;
                return cmp * sort.dir || b.views - a.views;
            });
    }, [rows, search, type, status, sort]);

    if (error) return <ErrorState onRetry={load} />;
    if (!rows) return <Card><Skeleton height={36} /><div style={{ height: 12 }} />{[1, 2, 3, 4, 5, 6].map((k) => <div key={k} style={{ marginBottom: 10 }}><Skeleton height={28} /></div>)}</Card>;

    const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    const shown = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);
    const th = (key: SortKey, label: string, num = true) => (
        <th className={num ? "num" : ""} aria-sort={sort.key === key ? (sort.dir === 1 ? "ascending" : "descending") : "none"}>
            <button type="button" className="nxa-sort" onClick={() => setSort({ key, dir: sort.key === key ? (sort.dir === 1 ? -1 : 1) : (key === "title" ? 1 : -1) })}>
                {label}<span aria-hidden="true">{sort.key === key ? (sort.dir === 1 ? " ↑" : " ↓") : ""}</span>
            </button>
        </th>
    );

    return (
        <Card>
            <div className="nxa-toolbar">
                <input type="search" className="nxa-input" placeholder={__("Search notifications…", "notificationx")} value={search} onChange={(e) => setSearch(e.target.value)} aria-label={__("Search notifications", "notificationx")} />
                <select className="nxa-input" value={type} onChange={(e) => setType(e.target.value)} aria-label={__("Filter by type", "notificationx")}>
                    <option value="">{__("All types", "notificationx")}</option>
                    {types.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                </select>
                <select className="nxa-input" value={status} onChange={(e) => setStatus(e.target.value)} aria-label={__("Filter by status", "notificationx")}>
                    <option value="">{__("Any status", "notificationx")}</option>
                    <option value="on">{__("Enabled", "notificationx")}</option>
                    <option value="off">{__("Disabled", "notificationx")}</option>
                </select>
                <span className="nxa-count-pill" aria-live="polite">
                    {filtered.length === (rows || []).length
                        ? <><strong className="nxa-num">{fmtNum((rows || []).length)}</strong>{_n("notification", "notifications", (rows || []).length, "notificationx")}</>
                        : <><strong className="nxa-num">{fmtNum(filtered.length)}</strong>{sprintf(__("of %s shown", "notificationx"), fmtNum((rows || []).length))}</>}
                </span>
            </div>
            {shown.length ? (
                <div className="nxa-table-wrap">
                    <table className="nxa-table nxa-table-click">
                        <thead><tr>{th("title", __("Notification", "notificationx"), false)}{th("views", __("Views", "notificationx"))}{th("seen", __("Seen", "notificationx"))}{th("clicks", __("Clicks", "notificationx"))}{th("ctr", __("CTR", "notificationx"))}{th("leads", __("Leads", "notificationx"))}</tr></thead>
                        <tbody>
                            {shown.map((row) => (
                                <tr key={row.nx_id} tabIndex={0} onClick={() => onOpen(row.nx_id)} onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && (e.preventDefault(), onOpen(row.nx_id))}>
                                    <td><div className="nxa-name"><StatusDot enabled={row.enabled} /><span className="nxa-name-text" title={row.title}>{row.title}</span><span className="nxa-id">#{row.nx_id}</span><TypeBadge label={row.type_label} /></div></td>
                                    <td className="num">{row.is_lead && !row.views ? <span className="nxa-muted" title={__("Popup and Exit Intent loads aren't counted as views; see Seen.", "notificationx")}>—</span> : fmtNum(row.views)}</td>
                                    <td className="num">{fmtNum(row.seen || 0)}</td>
                                    <td className="num">{fmtNum(row.clicks)}</td>
                                    <td className="num">{row.views ? fmtPct(row.ctr) : "—"}</td>
                                    <td className="num">{row.is_lead ? fmtNum(row.leads) : "—"}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : <EmptyState title={__("No notifications match these filters", "notificationx")} />}
            {pages > 1 && (
                <nav className="nxa-pager" aria-label={__("Pages", "notificationx")}>
                    <button type="button" className="nxa-btn nxa-btn-ghost" disabled={page <= 1} onClick={() => setPage(page - 1)}>{__("Previous", "notificationx")}</button>
                    <span className="nxa-muted">{sprintf(__("Page %1$s of %2$s", "notificationx"), page, pages)}</span>
                    <button type="button" className="nxa-btn nxa-btn-ghost" disabled={page >= pages} onClick={() => setPage(page + 1)}>{__("Next", "notificationx")}</button>
                </nav>
            )}
        </Card>
    );
};

export default Notifications;
