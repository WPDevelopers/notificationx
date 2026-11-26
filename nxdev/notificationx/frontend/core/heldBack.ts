import { nxApplyFilters } from "./hooks";

/**
 * Whether an add-on holds a notice back for now (Pro: the live visitor count,
 * until it is known). It is held back while an `nx_frontend_entry` filter
 * returns `null`, or while the entry still carries the server's
 * `nx_awaiting_data` mark: the same two cases in which NotificationContainer
 * draws nothing.
 *
 * useNotificationX skips such a cycle instead of adding the notice. A notice
 * only leaves the list through its own card's timer, so one that is added but
 * never drawn would stay, and every hidden cycle would add another copy, all
 * shown at once when the data arrives. The next cycle shows it once the data
 * is known.
 *
 * @since x.x.x
 */
export const isHeldBack = (data: any, config: any): boolean => {
    // Only entries (objects) can be held back. Bars, popups and GDPR pass
    // their content here and are drawn by their own components, whatever it is.
    if (!data || typeof data !== 'object') {
        return false;
    }
    const filtered = nxApplyFilters('nx_frontend_entry', data, { settings: config });
    if (filtered === null) {
        return true;
    }
    const entry = filtered && typeof filtered === 'object' ? filtered : data;
    return !!entry?.nx_awaiting_data;
};
