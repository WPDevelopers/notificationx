/**
 * Helpers shared by the admin app and the frontend runtime.
 *
 * Keep this module dependency-free: no imports at all. The frontend bundle
 * (assets/public/js/frontend.js) pulls it in, and anything imported here ends
 * up on every page that shows a notification. Admin code re-exports these
 * from core/functions.ts and core/constants.ts, so existing admin imports keep
 * working unchanged.
 *
 * See docs/features/frontend-performance/ for why the frontend must never
 * import from core/ or hooks/ directly.
 */

// Helper function to get the complete icon URL
export const getIconUrl = (iconValue, iconPrefix = '') => {
    if (!iconValue) return '';

    // Check if it's already a complete URL (starts with http/https or data:)
    if (/^(https?:\/\/|data:)/.test(iconValue)) {
        return iconValue;
    }

    // Convert admin URL to public URL if needed
    let prefix = iconPrefix;
    if (prefix && prefix.includes('/wp-admin/')) {
        // Convert admin URL to public URL
        prefix = prefix.replace('/wp-admin/', '/wp-content/plugins/notificationx/assets/admin/');
        prefix = prefix.replace('/images/icons/', 'images/icons/');
    }

    // Default to NotificationX public icons directory if no prefix
    if (!prefix) {
        const baseUrl = (typeof window !== 'undefined' && window.location)
            ? window.location.origin
            : '';
        prefix = baseUrl + '/wp-content/plugins/notificationx/assets/admin/images/icons/';
    }

    return prefix + iconValue;
};

export const themes_has_bg = ['press_bar_theme-four','press_bar_theme-five'];

export const modalStyle = {
    overlay: {
        position: "fixed",
        display: "flex",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: "rgba(3, 6, 60, 0.7)",
        zIndex: 9999999,
        padding: "60px 15px",
    },
    content: {
        position: "static",
        width: '900px',
        margin: "auto",
        border: "0px solid #5414D0",
        // background: "#5414D0",
        overflow: "auto",
        WebkitOverflowScrolling: "touch",
        borderRadius: "4px",
        outline: "none",
        padding: "15px",
    }
}
