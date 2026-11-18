/**
 * "Why isn't it showing?": pure helpers for the modal (no DOM, no requests).
 *
 * The reasons come from GET notificationx/v1/why-not-showing/<id>
 * (includes/Core/WhyNotShowing.php).
 */

export type Level = 'blocked' | 'limited' | 'note' | 'ok';

export interface Reason {
    key: string;
    level: Level;
    title: string;
    detail?: string;
    fix?: { label: string; url: string } | null;
}

export interface BrowserKeys {
    cookies?: string[];
    local?: string[];
    session?: string[];
}

export const LEVELS: Level[] = ['blocked', 'limited', 'note', 'ok'];

/**
 * Reasons by level, in the order the server sent them.
 */
export const groupReasons = (reasons: Reason[] = []): Record<Level, Reason[]> => {
    const groups = { blocked: [], limited: [], note: [], ok: [] } as Record<Level, Reason[]>;
    (Array.isArray(reasons) ? reasons : []).forEach((reason) => {
        if (reason && groups[reason.level]) {
            groups[reason.level].push(reason);
        }
    });
    return groups;
};

/**
 * Cookie names in a `document.cookie` string.
 */
export const cookieNames = (cookie: string = ''): string[] =>
    String(cookie)
        .split(';')
        .map((part) => part.split('=')[0].trim())
        .filter(Boolean);

const has = (storage: Pick<Storage, 'getItem'> | null | undefined, key: string): boolean => {
    try {
        return !!storage && storage.getItem(key) !== null;
    } catch (e) {
        return false;
    }
};

/**
 * The close markers for this notification found in this browser (the
 * admin's own), the same keys the front end writes when it's closed. Only
 * read, never changed or sent anywhere.
 */
export const closedHere = (
    keys: BrowserKeys = {},
    env: { cookie?: string; local?: Pick<Storage, 'getItem'> | null; session?: Pick<Storage, 'getItem'> | null } = {}
): string[] => {
    const found: string[] = [];
    const names = cookieNames(env.cookie);
    (keys.cookies || []).forEach((key) => {
        if (names.includes(key)) {
            found.push(`cookie:${key}`);
        }
    });
    (keys.local || []).forEach((key) => {
        if (has(env.local, key)) {
            found.push(`localStorage:${key}`);
        }
    });
    (keys.session || []).forEach((key) => {
        if (has(env.session, key)) {
            found.push(`sessionStorage:${key}`);
        }
    });
    return found;
};

/**
 * The one-line verdict at the top of the modal.
 */
export const verdict = (groups: Record<Level, Reason[]>, closedInThisBrowser = false): Level => {
    if (groups.blocked.length) {
        return 'blocked';
    }
    if (groups.limited.length || closedInThisBrowser) {
        return 'limited';
    }
    return 'ok';
};
