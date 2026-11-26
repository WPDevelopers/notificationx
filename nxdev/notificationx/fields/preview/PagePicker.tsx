import React, { useEffect, useRef, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";

/**
 * One page the Preview can be shown on (Rest\PreviewPages::item()).
 */
export type PreviewPage = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    status: string;
    password: boolean;
    public: boolean;
    front: boolean;
    url: string;
};

type PagesResponse = {
    items?: PreviewPage[];
    current?: { id: number; title: string } | null;
};

export const SEARCH_DELAY = 250;

export const pagesPath = (search: string, url: string) =>
    `/notificationx/v1/preview/pages?search=${encodeURIComponent(search)}&url=${encodeURIComponent(url)}`;

export const statusBadges = (page: PreviewPage): string[] => {
    const labels: { [status: string]: string } = {
        draft: __("Draft", "notificationx"),
        pending: __("Pending", "notificationx"),
        future: __("Scheduled", "notificationx"),
        private: __("Private", "notificationx"),
    };
    const badges = labels[page.status] ? [labels[page.status]] : [];
    if (page.password) {
        badges.push(__("Password", "notificationx"));
    }
    return badges;
};

/**
 * Groups the pages by post type, in the order the server sent them.
 */
export const groupPages = (items: PreviewPage[]) => {
    const groups: { label: string; items: PreviewPage[] }[] = [];
    items.forEach((item) => {
        let group = groups.find((g) => g.label === item.type_label);
        if (!group) {
            group = { label: item.type_label, items: [] };
            groups.push(group);
        }
        group.items.push(item);
    });
    return groups;
};

type PagePickerProps = {
    defaultUrl: string;
    chosen: PreviewPage | null;
    defaultId: number | null;
    onChoose: (page: PreviewPage | null) => void;
    onDefaultName: (current: { id: number; title: string }) => void;
    onClose: () => void;
};

const PagePicker = ({ defaultUrl, chosen, defaultId, onChoose, onDefaultName, onClose }: PagePickerProps) => {
    const [search, setSearch] = useState("");
    const [items, setItems] = useState<PreviewPage[]>([]);
    const [loading, setLoading] = useState(true);
    const [failed, setFailed] = useState(false);
    const request = useRef(0);
    const input = useRef<HTMLInputElement>(null);

    useEffect(() => {
        input.current?.focus();
    }, []);

    useEffect(() => {
        const id = ++request.current;
        setLoading(true);
        const timer = setTimeout(
            () => {
                apiFetch({ path: pagesPath(search.trim(), defaultUrl) })
                    .then((res: PagesResponse) => {
                        // A later search answered first: keep the newest results.
                        if (id !== request.current) {
                            return;
                        }
                        setItems(Array.isArray(res?.items) ? res.items : []);
                        setFailed(false);
                        if (res?.current?.title) {
                            onDefaultName(res.current);
                        }
                    })
                    .catch(() => {
                        if (id === request.current) {
                            setItems([]);
                            setFailed(true);
                        }
                    })
                    .finally(() => {
                        if (id === request.current) {
                            setLoading(false);
                        }
                    });
            },
            search ? SEARCH_DELAY : 0
        );
        return () => clearTimeout(timer);
    }, [search]);

    const isCurrent = (page: PreviewPage) => (chosen ? chosen.id === page.id : defaultId !== null && defaultId === page.id);

    return (
        <div
            className="nx-preview-panel nx-preview-page-picker"
            role="dialog"
            aria-label={__("Choose the page to preview on", "notificationx")}
        >
            <label className="nx-preview-page-search">
                <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true">
                    <path fill="currentColor" d="M10 3a7 7 0 0 1 5.6 11.2l5.1 5.1-1.4 1.4-5.1-5.1A7 7 0 1 1 10 3zm0 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10z" />
                </svg>
                <input
                    ref={input}
                    type="search"
                    value={search}
                    placeholder={__("Search pages, posts, products", "notificationx")}
                    aria-label={__("Search pages, posts, products", "notificationx")}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </label>
            <div className="nx-preview-page-list" aria-busy={loading}>
                {loading && !items.length && <p className="nx-preview-page-note">{__("Loading…", "notificationx")}</p>}
                {!loading && failed && <p className="nx-preview-page-note">{__("The pages could not be loaded. Please try again.", "notificationx")}</p>}
                {!loading && !failed && !items.length && <p className="nx-preview-page-note">{__("No pages found.", "notificationx")}</p>}
                {groupPages(items).map((group) => (
                    <div className="nx-preview-page-group" key={group.label}>
                        <div className="nx-preview-page-group-label">{group.label}</div>
                        {group.items.map((page) => (
                            <button
                                key={page.id}
                                type="button"
                                className={`nx-preview-page-item${isCurrent(page) ? " current" : ""}`}
                                aria-pressed={isCurrent(page)}
                                onClick={() => onChoose(page)}
                            >
                                <span className="nx-preview-page-check" aria-hidden="true">
                                    {isCurrent(page) && (
                                        <svg viewBox="0 0 24 24" width="14" height="14">
                                            <path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z" />
                                        </svg>
                                    )}
                                </span>
                                <span className="nx-preview-page-title">
                                    {page.title}
                                    {page.front && <span className="nx-preview-page-muted"> {__("(front page)", "notificationx")}</span>}
                                </span>
                                {statusBadges(page).map((badge) => (
                                    <span className="nx-preview-page-badge" key={badge}>{badge}</span>
                                ))}
                            </button>
                        ))}
                    </div>
                ))}
            </div>
            {chosen && (
                <div className="nx-preview-panel-foot">
                    <button type="button" className="nx-preview-link" onClick={() => onChoose(null)}>
                        {__("Back to default", "notificationx")}
                    </button>
                </div>
            )}
        </div>
    );
};

export default PagePicker;
