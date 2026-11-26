import React, { useEffect, useRef, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { applyFilters } from "@wordpress/hooks";
import ErrorBoundary from "../../components/ErrorBoundary";
import PagePicker, { PreviewPage } from "./PagePicker";
import { ToolbarSize } from "./size";

export const PageIcon = () => (
    <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true">
        <path fill="currentColor" d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm7 1.5V9h5.5L13 3.5zM7 13h10v1.6H7V13zm0 3.6h7v1.6H7v-1.6z" />
    </svg>
);

const CaretIcon = () => (
    <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true">
        <path fill="currentColor" d="M7 9.5l5 5 5-5z" />
    </svg>
);

const NewTabIcon = () => (
    <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true">
        <path fill="currentColor" d="M14 3h7v7h-2V6.4l-9.3 9.3-1.4-1.4L17.6 5H14V3zM5 5h6v2H5v12h12v-6h2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
    </svg>
);

/**
 * What add-ons get in the `nx_preview_toolbar_items` filter.
 */
export type PreviewToolbarContext = {
    // The current, unsaved builder values (the saved notification's id is `values.nx_id`).
    values: any;
    previewType: string;
    // The page picked in the page picker, or null for the default page.
    page: PreviewPage | null;
    // The URL the preview shows now, and the default one.
    url: string;
    defaultUrl: string;
    size: ToolbarSize;
    // One panel is open at a time: give yours a name and use these.
    panel: string | null;
    setPanel: (panel: string | null) => void;
    openInNewTab: () => void;
};

type PreviewToolbarProps = {
    values: any;
    previewType: string;
    url: string;
    size: ToolbarSize;
    defaultUrl: string;
    homeUrl: string;
    defaultName: { id: number; title: string } | null;
    chosen: PreviewPage | null;
    onChoose: (page: PreviewPage | null) => void;
    onDefaultName: (current: { id: number; title: string }) => void;
    openInNewTab: () => void;
};

/**
 * The name the page button shows.
 */
export const pageName = (chosen: PreviewPage | null, defaultName: { title: string } | null, defaultUrl: string, homeUrl: string) => {
    if (chosen) {
        return chosen.title;
    }
    if (defaultName?.title) {
        return defaultName.title;
    }
    return homeUrl && defaultUrl.replace(/\/+$/, "") === homeUrl.replace(/\/+$/, "")
        ? __("Home", "notificationx")
        : __("Default page", "notificationx");
};

/**
 * Controls on the right of the Preview's top bar (layout A): the page the
 * preview shows and "Open in new tab". Absolutely positioned, so the device
 * buttons and the close button keep their places.
 */
const PreviewToolbar = (props: PreviewToolbarProps) => {
    const { values, previewType, url, size, defaultUrl, homeUrl, defaultName, chosen, onChoose, onDefaultName, openInNewTab } = props;
    const [panel, setPanel] = useState<string | null>(null);
    const root = useRef<HTMLDivElement>(null);
    const pageButton = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!panel) {
            return;
        }
        const outside = (event: MouseEvent) => {
            if (root.current && !root.current.contains(event.target as Node)) {
                setPanel(null);
            }
        };
        // A click in the preview frame moves the focus out of this window.
        const blur = () => setPanel(null);
        document.addEventListener("mousedown", outside);
        window.addEventListener("blur", blur);
        return () => {
            document.removeEventListener("mousedown", outside);
            window.removeEventListener("blur", blur);
        };
    }, [panel]);

    const onKeyDown = (event: React.KeyboardEvent) => {
        if ("Escape" === event.key && panel) {
            // Close the panel, not the whole Preview, and go back to its button.
            event.stopPropagation();
            const opener = root.current?.querySelector<HTMLElement>('[aria-expanded="true"]');
            setPanel(null);
            (opener || pageButton.current)?.focus();
        }
    };

    const name = pageName(chosen, defaultName, defaultUrl, homeUrl);
    const choose = (page: PreviewPage | null) => {
        setPanel(null);
        onChoose(page);
    };

    /**
     * Add-on controls after "Open in new tab" (for example Pro's Share).
     * Each item is a React element. A panel is an element with the class
     * `nx-preview-panel`, shown while `panel` is its name.
     */
    const context: PreviewToolbarContext = {
        values,
        previewType,
        page: chosen,
        url,
        defaultUrl,
        size,
        panel,
        setPanel,
        openInNewTab: () => {
            setPanel(null);
            openInNewTab();
        },
    };
    const extra = applyFilters("nx_preview_toolbar_items", [], context);
    const items = Array.isArray(extra) ? extra.filter((item) => React.isValidElement(item)) : [];

    return (
        <div ref={root} className={`nx-preview-tools nx-preview-tools-${size}`} onKeyDown={onKeyDown}>
            <button
                ref={pageButton}
                type="button"
                className={`nx-preview-tool nx-preview-tool-page${"page" === panel ? " active" : ""}${"compact" === size ? " icon" : ""}`}
                aria-expanded={"page" === panel}
                aria-haspopup="dialog"
                aria-label={sprintf(__("Page: %s", "notificationx"), name)}
                title={sprintf(__("Page: %s", "notificationx"), name)}
                onClick={() => setPanel("page" === panel ? null : "page")}
            >
                <PageIcon />
                {"compact" !== size && (
                    <span className="nx-preview-tool-label">
                        {"wide" === size && <span className="nx-preview-tool-muted">{__("Page:", "notificationx")} </span>}
                        {name}
                    </span>
                )}
                {"compact" !== size && <CaretIcon />}
            </button>
            <button
                type="button"
                className="nx-preview-tool icon"
                aria-label={__("Open in new tab", "notificationx")}
                title={__("Open in new tab", "notificationx")}
                onClick={context.openInNewTab}
            >
                <NewTabIcon />
            </button>
            {items.length > 0 && (
                <ErrorBoundary>
                    {items.map((item, index) => (
                        <React.Fragment key={index}>{item}</React.Fragment>
                    ))}
                </ErrorBoundary>
            )}
            {"page" === panel && (
                <PagePicker
                    defaultUrl={defaultUrl}
                    chosen={chosen}
                    defaultId={defaultName?.id ?? null}
                    onChoose={choose}
                    onDefaultName={onDefaultName}
                    onClose={() => setPanel(null)}
                />
            )}
        </div>
    );
};

export default PreviewToolbar;
