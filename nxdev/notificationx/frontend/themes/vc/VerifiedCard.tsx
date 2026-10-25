import React, { useEffect, useRef, useState } from "react";
import classNames from "classnames";
// Concrete modules only: never ../../core (index), which would deepen the
// core/index -> Notification -> themes import cycle.
import Image from "../helpers/Image";
import Close from "../helpers/Close";
import VcCredit from "./VcCredit";
import useVcLineScroll from "./useVcLineScroll";

/**
 * Verified Cards (vc-*): the card Theme.tsx renders for a whitelisted vc key
 * (isVcTheme) when it is not Pro's mobile card (is_mobile) and not an
 * Advanced Template. It replaces the generic Image/Content/Close markup, so
 * the nx_theme_before_content, nx_theme_after_content and nx_content_append
 * filters do not run here; nx_frontend_image still runs through Image.
 *
 * `slots` is GetTemplate's slot array after token substitution (HTML strings,
 * every value already escaped). Each layout maps fixed indexes:
 *  - vc-receipt:  [0] name, [1] action, [2] product, [3] time,
 *                 [4] Freemius plan + price ('<span></span>' elsewhere)
 *  - vc-showcase: [0] action, [1] product, [2] name, [3] city, country,
 *                 [4] time
 * An empty slot (no text once tags are stripped) hides its part.
 *
 * Desktop and tablet get the 344px card; at 480px and below (matchMedia,
 * guarded) the slim two-line card with credit A and option C. Only one
 * layout is mounted at a time.
 */

const SLIM_QUERY = "(max-width: 480px)";

const getSlimQuery = () => {
    if (typeof window === "undefined" || typeof window.matchMedia !== "function") {
        return null;
    }
    try {
        return window.matchMedia(SLIM_QUERY);
    } catch (e) {
        return null;
    }
};

/** True at 480px and below; false when matchMedia is missing. */
export const useVcSlim = () => {
    const [slim, setSlim] = useState(() => {
        const mq = getSlimQuery();
        return !!(mq && mq.matches);
    });
    useEffect(() => {
        const mq: any = getSlimQuery();
        if (!mq) return;
        const handler = () => setSlim(!!mq.matches);
        handler();
        if (typeof mq.addEventListener === "function") mq.addEventListener("change", handler);
        else if (typeof mq.addListener === "function") mq.addListener(handler);
        return () => {
            if (typeof mq.removeEventListener === "function") mq.removeEventListener("change", handler);
            else if (typeof mq.removeListener === "function") mq.removeListener(handler);
        };
    }, []);
    return slim;
};

/** True when an HTML slot has visible text. */
export const hasText = (html?: string) =>
    typeof html === "string" &&
    html.replace(/<[^>]*>/g, "").replace(/&nbsp;|&#160;/g, " ").trim() !== "";

const Html = ({ as: Tag = "span", className, html }: { as?: any; className?: string; html: string }) => (
    <Tag className={className} dangerouslySetInnerHTML={{ __html: html }} />
);

// Joins the visible parts of line 1 with single spaces.
const joinParts = (parts: React.ReactNode[]) =>
    parts.reduce<React.ReactNode[]>((out, part, i) => {
        if (out.length) out.push(" ");
        out.push(<React.Fragment key={i}>{part}</React.Fragment>);
        return out;
    }, []);

const PinIcon = () => (
    <svg className="nx-vc-pin" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path
            fill="currentColor"
            d="M12 2a7 7 0 00-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 00-7-7zm0 9.6A2.6 2.6 0 1112 6.4a2.6 2.6 0 010 5.2z"
        />
    </svg>
);

const VcImage = (props) => (
    <Image {...props} theme={props.themeName} style={{}} isSplitCss={false} isSplit={false} />
);

/* ---------- desktop / tablet ---------- */

const ReceiptCard = ({ props, s }) => {
    const hasTime = hasText(s[3]);
    return (
        <div className={classNames("nx-vc-card", "nx-vc-receipt", { "has-time": hasTime })}>
            {hasTime && <Html className="nx-vc-time" html={s[3]} />}
            {hasText(s[0]) && <Html as="div" className="nx-vc-name nx-vc-ell nx-vc-rsv" html={s[0]} />}
            <div className="nx-vc-panel nx-vc-row">
                <VcImage {...props} />
                <div className="nx-vc-col">
                    {hasText(s[1]) && <Html as="div" className="nx-vc-label" html={s[1]} />}
                    {hasText(s[2]) && (
                        <div className="nx-vc-body nx-vc-cl2">
                            <Html className="nx-vc-em-a" html={s[2]} />
                        </div>
                    )}
                    {hasText(s[4]) && <Html as="div" className="nx-vc-meta nx-vc-plan nx-vc-ell" html={s[4]} />}
                </div>
            </div>
            <VcCredit config={props.config} />
            <Close {...props} />
        </div>
    );
};

const ShowcaseCard = ({ props, s }) => {
    const hasTime = hasText(s[4]);
    const hasBuyer = hasText(s[2]);
    const hasLoc = hasText(s[3]);
    return (
        <div className={classNames("nx-vc-card", "nx-vc-showcase", { "has-time": hasTime })}>
            {hasTime && <Html className="nx-vc-time" html={s[4]} />}
            {hasText(s[0]) && (
                <div className="nx-vc-rsv">
                    <Html className="nx-vc-chip" html={s[0]} />
                </div>
            )}
            <div className="nx-vc-row nx-vc-sc-main">
                <div className="nx-vc-col">
                    {hasText(s[1]) && <Html as="div" className="nx-vc-sc-title nx-vc-cl2" html={s[1]} />}
                    {(hasBuyer || hasLoc) && (
                        <div className="nx-vc-sc-buyer nx-vc-ell">
                            {hasBuyer && <Html as="b" html={s[2]} />}
                            {hasBuyer && hasLoc && <span className="nx-vc-sep"> · </span>}
                            {hasLoc && (
                                <span className="nx-vc-loc">
                                    <PinIcon /> <Html html={s[3]} />
                                </span>
                            )}
                        </div>
                    )}
                </div>
                <VcImage {...props} />
            </div>
            <VcCredit config={props.config} />
            <Close {...props} />
        </div>
    );
};

/* ---------- slim (≤480px) ---------- */

const slimLine = (word: string, s: string[]) => {
    const parts: React.ReactNode[] = [];
    if (word === "receipt") {
        // <b>name</b> action <em-a>product</em-a>
        if (hasText(s[0])) parts.push(<Html as="b" html={s[0]} />);
        if (hasText(s[1])) parts.push(<Html html={s[1]} />);
        if (hasText(s[2])) parts.push(<Html className="nx-vc-em-a" html={s[2]} />);
        return { line: joinParts(parts), time: s[3] };
    }
    // showcase: <b>product</b> · <em-a>action</em-a>
    const hasTitle = hasText(s[1]);
    const hasAction = hasText(s[0]);
    if (hasTitle) parts.push(<Html as="b" html={s[1]} />);
    if (hasTitle && hasAction) parts.push(<span className="nx-vc-dim">·</span>);
    if (hasAction) parts.push(<Html className="nx-vc-em-a" html={s[0]} />);
    return { line: joinParts(parts), time: s[4] };
};

const SlimCard = ({ props, s, word }) => {
    const msgRef = useRef(null);
    const inRef = useRef(null);
    useVcLineScroll(msgRef, inRef);
    const { line, time } = slimLine(word, s);
    return (
        <div
            className={classNames("nx-vc-card", `nx-vc-${word}`, "nx-vc-slim", {
                "is-no-img": !props?.data?.image_data,
                "is-no-credit": !!props?.config?.disable_powered_by,
            })}
        >
            <VcImage {...props} />
            <div className="nx-vc-l1">
                {/* className never changes, so React keeps the hook's `is-run`. */}
                <span className="nx-vc-msg" ref={msgRef}>
                    <span className="nx-vc-msg-in" ref={inRef}>
                        {line}
                    </span>
                </span>
                {hasText(time) && <Html className="nx-vc-slim-time" html={time} />}
            </div>
            <VcCredit config={props.config} slim />
            <Close {...props} />
        </div>
    );
};

const VerifiedCard = (props) => {
    const { slots, themeName } = props;
    const slim = useVcSlim();
    const s: string[] = Array.isArray(slots) ? slots : [];
    const word = String(themeName || "").replace(/^vc-/, "");

    if (slim) {
        return <SlimCard props={props} s={s} word={word} />;
    }
    switch (word) {
        case "showcase":
            return <ShowcaseCard props={props} s={s} />;
        case "receipt":
        default:
            return <ReceiptCard props={props} s={s} />;
    }
};

export default VerifiedCard;
