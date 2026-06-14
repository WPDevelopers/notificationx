import { __ } from "@wordpress/i18n";
import React from "react";
// Growth-dashboard hero, cropped from the Figma illustration (node 4440:259)
// and exported as a lavender-matted WebP so its corners blend into the panel.
import dashboard from "./growth-dashboard.webp";

/**
 * Static illustration panel matching the Figma "Illustration Section"
 * (node 4440:259): two mock notification cards framing the Growth Dashboard
 * hero image. This replaces the animated LivePreview on the Welcome step so
 * the right column matches the actual design 1:1.
 */

const avatar = (letter: string, c1: string, c2: string) =>
    "data:image/svg+xml," +
    encodeURIComponent(
        `<svg xmlns='http://www.w3.org/2000/svg' width='96' height='96'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='${c1}'/><stop offset='1' stop-color='${c2}'/></linearGradient></defs><rect width='96' height='96' rx='48' fill='url(#g)'/><text x='50%' y='55%' font-family='Inter,Arial,sans-serif' font-size='42' font-weight='700' fill='#fff' text-anchor='middle' dominant-baseline='middle'>${letter}</text></svg>`
    );

const Illustration = () => (
    <div className="nx-sw__illus">
        {/* Top mock — "Alex just purchased!" */}
        <div className="nx-sw__mock nx-sw__mock--top">
            <span className="nx-sw__mock-avatar">
                <img src={avatar("A", "#897fff", "#4a3aff")} alt="" />
            </span>
            <span className="nx-sw__mock-body">
                <strong>{__("Alex just purchased!", "notificationx")}</strong>
                <small>{__("Pro Plan • 2 mins ago", "notificationx")}</small>
            </span>
            <span className="nx-sw__mock-verified" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" fill="currentColor" />
                    <path
                        d="M8 12.5l2.5 2.5L16 9.5"
                        stroke="#fff"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </span>
        </div>

        {/* Growth Dashboard hero */}
        <div className="nx-sw__hero">
            <img src={dashboard} alt={__("Growth dashboard", "notificationx")} />
        </div>

        {/* Bottom mock — "High Demand" */}
        <div className="nx-sw__mock nx-sw__mock--bottom">
            <span className="nx-sw__mock-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13.5 1.5c.4 3-1.3 4.6-2.8 6C9 9 7.5 10.6 7.5 13.3a4.5 4.5 0 109 0c0-1.2-.4-2.2-1-3 .1.9-.6 1.7-1.4 1.7-.9 0-1.4-.7-1.4-1.7 0-1.7 1-2.6 1-4.8 0-1.7-.7-3-1.7-4z" />
                </svg>
            </span>
            <span className="nx-sw__mock-body">
                <strong>{__("High Demand", "notificationx")}</strong>
                <small>{__("Only 3 spots left today", "notificationx")}</small>
            </span>
        </div>
    </div>
);

export default Illustration;
