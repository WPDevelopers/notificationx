import { __ } from "@wordpress/i18n";
import React, { useState } from "react";
// Reuse the REAL frontend branding marks so the Sales popup matches 1:1.
import BrandLogo from "../../frontend/themes/helpers/BrandLogo";
import Logo from "../../frontend/themes/helpers/Logo";

/**
 * Welcome-screen "Live Preview" — shows the ACTUAL Sales notification
 * (conversions_conv-theme-twelve), rendered with the real frontend markup +
 * theme CSS (enqueued in SetupWizard.php). It behaves like the real plugin:
 * a notification slides in from the corner, holds, slides out, and the next
 * buyer slides in — a continuous queue. Driven by `onAnimationEnd` so each
 * card runs one clean in→hold→out cycle before advancing.
 */

type Sale = {
    name: string;
    action: string;
    product: string;
    time: string;
    avatar: [string, string, string]; // letter, gradient from, gradient to
};

const SALES: Sale[] = [
    { name: __("Alex M.", "notificationx"), action: __("purchased", "notificationx"), product: __("Premium Membership", "notificationx"), time: __("2 minutes ago", "notificationx"), avatar: ["A", "#897fff", "#4a3aff"] },
    { name: __("Sarah K.", "notificationx"), action: __("purchased", "notificationx"), product: __("Yoga Starter Kit", "notificationx"), time: __("just now", "notificationx"), avatar: ["S", "#f093fb", "#f5576c"] },
    { name: __("James R.", "notificationx"), action: __("purchased", "notificationx"), product: __("Annual Pro Plan", "notificationx"), time: __("5 minutes ago", "notificationx"), avatar: ["J", "#4facfe", "#00c6fb"] },
    { name: __("Emma W.", "notificationx"), action: __("purchased", "notificationx"), product: __("Wireless Headphones", "notificationx"), time: __("8 minutes ago", "notificationx"), avatar: ["E", "#43e97b", "#12b886"] },
    { name: __("Liam T.", "notificationx"), action: __("purchased", "notificationx"), product: __("Online Course Bundle", "notificationx"), time: __("11 minutes ago", "notificationx"), avatar: ["L", "#fa709a", "#fa5252"] },
];

const avatar = (letter: string, c1: string, c2: string) =>
    "data:image/svg+xml," +
    encodeURIComponent(
        `<svg xmlns='http://www.w3.org/2000/svg' width='88' height='88'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='${c1}'/><stop offset='1' stop-color='${c2}'/></linearGradient></defs><rect width='88' height='88' rx='44' fill='url(#g)'/><text x='50%' y='55%' font-family='Inter,Arial,sans-serif' font-size='38' font-weight='700' fill='#fff' text-anchor='middle' dominant-baseline='middle'>${letter}</text></svg>`
    );

const Branding = () => (
    <small className="nx-branding">
        <Logo />
        <span className="nx-byline">{__("by", "notificationx")}</span>
        <a className="nx-powered-by" href="#" onClick={(e) => e.preventDefault()}>
            <BrandLogo />
        </a>
    </small>
);

/** The real Sales popup (conversions_conv-theme-twelve). */
const SalesPopup = ({ item }: { item: Sale }) => (
    <div className="notification-item nx-notification themes-conversions_conv-theme-twelve">
        <div className="notificationx-inner no-advance-edit">
            <div className="notificationx-image image-circle">
                <img src={avatar(...item.avatar)} alt="buyer" />
            </div>
            <div className="notificationx-content">
                <p className="nx-first-row">
                    <span>
                        <span>{item.name}</span>
                        <span>{item.time}</span>
                    </span>
                </p>
                <p className="nx-second-row">
                    <span>
                        <span>{item.action} </span>
                        <span>{item.product}</span>
                        <span></span>
                    </span>
                </p>
                <p className="nx-third-row">
                    <Branding />
                </p>
            </div>
        </div>
    </div>
);

const LivePreview = () => {
    const [i, setI] = useState(0);
    const item = SALES[i % SALES.length];

    return (
        <div className="nx-sw__showcase">
            <div className="nx-sw__browser">
                {/* browser chrome */}
                <div className="nx-sw__browser-bar">
                    <span className="nx-sw__browser-dots">
                        <i />
                        <i />
                        <i />
                    </span>
                    <span className="nx-sw__tab">
                        <span className="nx-sw__tab-fav" />
                        <span className="nx-sw__tab-title">
                            {__("My WooCommerce Store", "notificationx")}
                        </span>
                    </span>
                    <span className="nx-sw__live-badge">
                        <i className="nx-sw__live-dot" />
                        {__("Live", "notificationx")}
                    </span>
                </div>

                {/* site viewport */}
                <div className="nx-sw__site">
                    <div className="nx-sw__site-skeleton">
                        <span className="nx-sw__sk-hero" />
                        <span />
                        <span />
                        <span className="nx-sw__sk-short" />
                    </div>

                    {/* keyed → each card runs one in→hold→out cycle, then advances */}
                    <div
                        className="nx-sw__slot-popup"
                        key={i}
                        onAnimationEnd={(e) => {
                            // Only advance on the slot's own cycle ending — never
                            // on a bubbled animation from a descendant.
                            if (e.animationName === "nx-sw-cycle") {
                                setI((p) => p + 1);
                            }
                        }}
                    >
                        <SalesPopup item={item} />
                    </div>
                </div>
            </div>

            <p className="nx-sw__preview-caption">
                {__(
                    "This is how your sales notifications will appear to visitors.",
                    "notificationx"
                )}
            </p>
        </div>
    );
};

export default LivePreview;
