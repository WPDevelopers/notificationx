/**
 * Exit Intent "Do Not Show Again" (toggle) and "Do Not Show Again For" (days).
 *
 * Closing a popup always hides it for the rest of the browser session. With
 * the toggle on, it also writes a cookie for the number of days, and only then
 * does an existing cookie keep the popup hidden.
 *
 * The server sends the toggle for every Exit Intent campaign: campaigns saved
 * before it existed come as "on" when they have days
 * (ExitIntentNotification::dismiss_toggle_on_read()). A payload without the
 * key behaves as before the toggle: the days decide.
 *
 * @since x.x.x
 */

const isOn = ( value: any ): boolean => value === true || value === 1 || value === '1' || value === 'true';

/**
 * Whether the dismissal cookie applies to this campaign.
 */
export const isExitIntentCookieOn = ( settings: any ): boolean => {
    const value = settings?.exit_intent_cookie_enabled;
    if ( value === undefined || value === null ) {
        return true;
    }
    return isOn( value );
};

/**
 * Days to keep the dismissal cookie, or 0 when no cookie is written.
 */
export const exitIntentCookieDays = ( settings: any ): number => {
    if ( ! isExitIntentCookieOn( settings ) ) {
        return 0;
    }
    const days = parseInt( settings?.exit_intent_cookie_days, 10 );
    return Number.isFinite( days ) && days > 0 ? days : 0;
};
