/**
 * "After a form submit": what a visitor sees after a successful form submit in
 * a Popup, Exit Intent or Notification Bar.
 *
 * Settings (Helper::submit_reappearance_fields()):
 * - `submit_reappearance`: `same_as_close` (default), `days` or `never`;
 * - `submit_hide_days`: 1-365, used by `days`.
 *
 * `same_as_close` is today's behaviour: a submit is remembered like a close, and
 * nothing here reads or writes anything. For the other two, a successful submit
 * stores when it happened, in localStorage, under a key scoped to the site the
 * notification comes from. The notification's current rule decides at read
 * time, so a later change of the rule also applies to visitors who already
 * submitted, and "never" needs no special value.
 *
 * The builder preview neither reads nor writes the key.
 *
 * @since x.x.x
 */

export type SubmitRule = 'same_as_close' | 'days' | 'never';

/**
 * The notificationXArr entry a notification belongs to (what the frontend
 * context holds): its REST root, the site's storage scope, the preview flag.
 */
export type SubmitEntry = {
    storage_scope?: unknown;
    rest?: { root?: unknown };
    nxPreview?: unknown;
} | null | undefined;

export const SUBMITTED_KEY_PREFIX = 'notificationx_submitted_';
export const MAX_SUBMIT_DAYS = 365;
const DAY_MS = 24 * 60 * 60 * 1000;

/**
 * Days to hide for the `days` rule: an integer from 1 to 365, or 0 when the
 * setting is missing or not a usable number.
 */
export const getSubmitDays = (settings): number => {
    const raw = settings?.submit_hide_days;
    const days = typeof raw === 'number' ? Math.floor(raw) : parseInt(String(raw ?? ''), 10);
    if (!Number.isFinite(days) || days < 1) {
        return 0;
    }
    return Math.min(days, MAX_SUBMIT_DAYS);
};

/**
 * The notification's rule. Anything missing, unknown or unusable (`days`
 * without a usable number of days) is `same_as_close`: today's behaviour.
 */
export const getSubmitRule = (settings): SubmitRule => {
    const rule = settings?.submit_reappearance;
    if (rule === 'never') {
        return 'never';
    }
    if (rule === 'days' && getSubmitDays(settings) > 0) {
        return 'days';
    }
    return 'same_as_close';
};

/**
 * The site part of the key. The entry's `storage_scope` (Helper::storage_scope())
 * when it has one; otherwise, for Cross Domain Notice snippets copied before it
 * existed, the host and path of the REST root. '' when neither is known.
 */
export const getStorageScope = (entry: SubmitEntry): string => {
    const own = typeof entry?.storage_scope === 'string'
        ? entry.storage_scope.toLowerCase().replace(/[^a-z0-9]/g, '')
        : '';
    if (own) {
        return own;
    }
    const root = entry?.rest?.root;
    if (typeof root !== 'string' || !root) {
        return '';
    }
    let site: string;
    try {
        const url = new URL(root);
        // https://example.com/sub/wp-json/ and the plain-permalink form
        // https://example.com/sub/index.php?rest_route=/ both become example.com/sub.
        site = url.host + url.pathname.replace(/\/wp-json(\/.*)?$/i, '').replace(/\/index\.php$/i, '');
    } catch (e) {
        return '';
    }
    return site.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
};

/** The localStorage key for this notification, or '' without an ID. */
export const submittedKey = (nxId, entry: SubmitEntry): string => {
    const id = String(nxId ?? '').replace(/[^0-9a-zA-Z_-]/g, '');
    if (!id) {
        return '';
    }
    const scope = getStorageScope(entry);
    return SUBMITTED_KEY_PREFIX + (scope ? `${scope}_` : '') + id;
};

// localStorage, or null where reading it throws (blocked site data) or it is missing.
const getStorage = (): Storage | null => {
    try {
        return typeof window !== 'undefined' && window.localStorage ? window.localStorage : null;
    } catch (e) {
        return null;
    }
};

const removeKey = (storage: Storage, key: string) => {
    try {
        storage.removeItem(key);
    } catch (e) {}
};

const writeAt = (storage: Storage, key: string, at: number): boolean => {
    try {
        storage.setItem(key, JSON.stringify({ v: 1, at }));
        return true;
    } catch (e) {
        // Quota full, or storage refused.
        return false;
    }
};

// The submit time stored under the key, or null for anything unusable.
const readAt = (raw: string): number | null => {
    try {
        const record = JSON.parse(raw);
        const at = record && typeof record === 'object' ? Number(record.at) : NaN;
        return Number.isFinite(at) && at > 0 ? at : null;
    } catch (e) {
        return null;
    }
};

const isPreview = (entry: SubmitEntry) => !!entry?.nxPreview;

/**
 * Remember a successful submit.
 *
 * Returns true when the submit rule has taken over: the rule isn't
 * `same_as_close`, this isn't the builder preview, and the key was written.
 * When it returns false the caller runs today's close code unchanged, so the
 * visitor gets at least today's behaviour.
 */
export const rememberSubmit = (settings, entry: SubmitEntry, now: number = Date.now()): boolean => {
    if (getSubmitRule(settings) === 'same_as_close' || isPreview(entry)) {
        return false;
    }
    const key = submittedKey(settings?.nx_id, entry);
    const storage = getStorage();
    if (!key || !storage) {
        return false;
    }
    return writeAt(storage, key, now);
};

/**
 * Whether the notification is hidden because the visitor submitted its form.
 *
 * - `same_as_close` and the builder preview: never (the key isn't read).
 * - `never`: hidden once a submit is stored.
 * - `days`: hidden for that many days after the submit; an expired record is removed.
 *
 * A record that can't be read, or that claims a submit more than a day in the
 * future, is removed and doesn't hide anything. A submit time slightly in the
 * future (the clock was set back) is moved to now, so nobody is hidden longer
 * than the rule says. Storage that can't be read hides nothing.
 */
export const isHiddenBySubmit = (settings, entry: SubmitEntry, now: number = Date.now()): boolean => {
    const rule = getSubmitRule(settings);
    if (rule === 'same_as_close' || isPreview(entry)) {
        return false;
    }
    const key = submittedKey(settings?.nx_id, entry);
    const storage = getStorage();
    if (!key || !storage) {
        return false;
    }
    let raw: string | null;
    try {
        raw = storage.getItem(key);
    } catch (e) {
        return false;
    }
    if (raw === null) {
        return false;
    }
    let at = readAt(raw);
    if (at === null || at > now + DAY_MS) {
        removeKey(storage, key);
        return false;
    }
    if (at > now) {
        at = now;
        writeAt(storage, key, at);
    }
    if (rule === 'never') {
        return true;
    }
    const hidden = now < at + getSubmitDays(settings) * DAY_MS;
    if (!hidden) {
        removeKey(storage, key);
    }
    return hidden;
};
