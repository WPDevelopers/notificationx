import React from "react";
import useNotificationContext from "./NotificationProvider";
import { analyticsOnClick, recordAnalyticsClick } from "./Analytics";
import nxHelper from "./functions";

/**
 * Shared runtime for add-ons that render inside the NotificationX React tree.
 *
 * The frontend bundle ships its own copy of React (the build runs with
 * --webpack-no-externals). A component from another bundle that calls React
 * hooks (useState, useContext, lazy/Suspense) must use this same React copy,
 * or React throws "Invalid hook call". notificationx-pro maps `react` to
 * `window.nxFrontendRuntime.React` in its webpack externals for that reason.
 *
 * Bump `version` only for a breaking change to this object; add new members
 * without a bump. See docs/api/frontend-js-hooks.md.
 */
export const FRONTEND_RUNTIME_VERSION = 1;

export const createFrontendRuntime = () =>
    Object.freeze({
        version: FRONTEND_RUNTIME_VERSION,
        React,
        useNotificationContext,
        analyticsOnClick,
        recordAnalyticsClick,
        getPath: (rest, path, query = {}) => nxHelper.getPath(rest, path, query),
    });

export const exposeFrontendRuntime = () => {
    const w = window as any;
    // The bundle can load twice (crossSite.js next to frontend.js). Keep the
    // first runtime so every add-on sees the React copy that rendered first.
    if (!w.nxFrontendRuntime) {
        w.nxFrontendRuntime = createFrontendRuntime();
    }
    return w.nxFrontendRuntime;
};
