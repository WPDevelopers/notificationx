/**
 * Exit Intent "Stop Showing After End Date".
 *
 * PHP sends `exit_intent_end_ts` (UTC, ms) and `exit_intent_hide_after_end`
 * (see ExitIntentNotification::add_end_timestamp()). The admin preview gets
 * neither, so it is never hidden or closed here.
 *
 * Dependency-free so the frontend bundle and Jest can import it directly.
 */

/** Milliseconds until the popup must stop showing, or null when it never stops. */
export const msUntilExitIntentEnd = (config: any, now: number = Date.now()): number | null => {
    if (config?.exit_intent_hide_after_end !== true) return null;
    const end = Number(config?.exit_intent_end_ts);
    if (!Number.isFinite(end) || end <= 0) return null;
    return end - now;
};

/** True when the popup must not open any more. */
export const isExitIntentPastEnd = (config: any, now: number = Date.now()): boolean => {
    const left = msUntilExitIntentEnd(config, now);
    return left !== null && left <= 0;
};
