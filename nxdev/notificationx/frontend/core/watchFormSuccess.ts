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
 * A NotificationX Form the visitor already sent this session is locked with an
 * "already submitted" message (`.nx-form-message.is-locked`). That is not a new
 * submit: `onSuccess` gets `alreadySubmitted = true`, so callers don't store a
 * submit the visitor never made in this notification.
 *
 * Returns the cleanup.
 *
 * @since x.x.x
 */
export const FORM_SUCCESS_SELECTOR = '.elementor-message-success, .e-form__submit__success, .nx-form-message.is-success';
export const FORM_LOCKED_SELECTOR = '.nx-form-message.is-locked';

export const watchFormSuccess = (root: HTMLElement, nxId, onSuccess: (alreadySubmitted: boolean) => void): (() => void) => {
    let done = false;
    let lockedSeen = false;
    const succeed = (alreadySubmitted = false) => {
        if (done) return;
        if (alreadySubmitted) {
            // Reported once; a real submit in another form still counts.
            if (lockedSeen) return;
            lockedSeen = true;
        } else {
            done = true;
        }
        onSuccess(alreadySubmitted);
    };

    // @ts-ignore
    const jq = window.jQuery;
    const onSubmitSuccess = () => succeed();
    if (jq) jq(root).on('submit_success', onSubmitSuccess);

    const onNxClose = (ev: any) => {
        const id = ev?.detail?.nxId;
        if (!id || String(id) === String(nxId)) succeed();
    };
    document.addEventListener('nx:exit-intent-close', onNxClose);

    const observer = new MutationObserver(() => {
        const success = root.querySelectorAll(FORM_SUCCESS_SELECTOR);
        if (!success.length) return;
        // Only locked forms: an earlier submit, not one made here.
        const locked = Array.prototype.every.call(success, (el: Element) => el.matches(FORM_LOCKED_SELECTOR));
        succeed(locked);
    });
    observer.observe(root, { childList: true, subtree: true });

    return () => {
        if (jq) jq(root).off('submit_success', onSubmitSuccess);
        document.removeEventListener('nx:exit-intent-close', onNxClose);
        observer.disconnect();
    };
};
