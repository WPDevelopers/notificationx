import React from "react";

// Green circular check that leads the Verified Card credit
// ("✓ Verified by NotificationX"). It is the credit's first <svg>, so CSS that
// restyles a credit through `.nx-branding > svg:first-child` still targets it.
const VerifiedCheck = () => (
    <svg
        className="nx-verified-check"
        width="12"
        height="12"
        viewBox="0 0 14 14"
        aria-hidden="true"
        focusable="false"
        xmlns="http://www.w3.org/2000/svg"
    >
        <circle cx="7" cy="7" r="7" fill="#006E2F" />
        <path
            d="M3.6 7.2l2 2 4.2-4.4"
            fill="none"
            stroke="#fff"
            strokeWidth="1.4"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    </svg>
);

export default VerifiedCheck;
