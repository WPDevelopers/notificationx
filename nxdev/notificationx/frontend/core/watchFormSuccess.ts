/**
 * Watch an Elementor design rendered inside a notification (Exit Intent popup,
 * Notification Bar) for a form submit that succeeded. Elementor and the form
 * scripts submit through their own AJAX and only show a success message; they
 * don't know they live inside a notification.
 *
 * Calls `onSuccess` once, on the first of:
 * 1. Elementor Pro's jQuery `submit_success` event on `root` (the Form widget);
 * 2. the `nx:exit-intent-close` document event that the NotificationX Form
 *    widget (assets/public/js/nx-elementor-form.js) sends after a successful
 *    submit inside an Exit Intent popup, for this notification or with no id;
 * 3. a success message appearing inside `root`, for integrations that send
 *    neither.
 *
 * Returns the cleanup.
 *
 * @since x.x.x
 */
export const FORM_SUCCESS_SELECTOR = '.elementor-message-success, .e-form__submit__success, .nx-form-message.is-success';

export const watchFormSuccess = (root: HTMLElement, nxId, onSuccess: () => void): (() => void) => {
    let done = false;
    const succeed = () => {
        if (done) return;
        done = true;
        onSuccess();
    };

    // @ts-ignore
    const jq = window.jQuery;
    if (jq) jq(root).on('submit_success', succeed);

    const onNxClose = (ev: any) => {
        const id = ev?.detail?.nxId;
        if (!id || String(id) === String(nxId)) succeed();
    };
    document.addEventListener('nx:exit-intent-close', onNxClose);

    const observer = new MutationObserver(() => {
        if (root.querySelector(FORM_SUCCESS_SELECTOR)) succeed();
    });
    observer.observe(root, { childList: true, subtree: true });

    return () => {
        if (jq) jq(root).off('submit_success', succeed);
        document.removeEventListener('nx:exit-intent-close', onNxClose);
        observer.disconnect();
    };
};
