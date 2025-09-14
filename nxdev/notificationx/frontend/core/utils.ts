import { GetTemplate } from "../themes";
import cookie from "react-cookies";
import nxHelper from "../core/functions";
// @ts-ignore

// apiFetch.use(apiFetch.createNonceMiddleware(notificationX.rest.nonce));

export const processNotice = ({ config }) => {
    let url = `notice/?frontend=true`;
    if (config.rest?.lang) {
        url += `&lang=${config.rest.lang}`;
    }
    return nxHelper
        .post(url, {
            all_active: config.all_active || false,
            global: config.global || [],
            active: config.active || [],
            pressbar: config.pressbar || [],
            shortcode: config.shortcode || [],
        })
        .then(normalizeResponse)
        .catch((err) => console.error("Fetch Error: ", err));
};

export const isNotClosed = (entry) => {
    const nx_id = entry?.nx_id || entry?.post?.nx_id;
    if (nx_id) {
        let countRand = entry?.post?.countdown_rand ? `-${entry.post.countdown_rand}` : '';
        if (cookie.load("notificationx_" + nx_id + countRand)) {
            return false;
        }

        // Check session storage for popup notifications
        if (entry?.post?.source === 'popup_notification' || entry?.source === 'popup_notification') {
            const sessionKey = "notificationx_popup_" + nx_id;
            if (sessionStorage.getItem(sessionKey) === 'closed') {
                return false;
            }
        }
    }
    return true;
};

export const normalizeResponse = (response: any) => {
    let mergedGlobalArray    = normalize(response?.global, response?.settings);
    let mergedActiveArray    = normalize(response?.active, response?.settings);
    let mergedShortcodeArray = normalize(response?.shortcode, response?.settings);
    let pressbar             = normalizePressBar(response?.pressbar, response?.settings);
    let gdpr                 = normalizePressBar(response?.gdpr, response?.settings);
    let popup                = normalizePressBar(response?.popup, response?.settings);

    return {
        settings       : response?.settings,
        activeNotice   : mergedActiveArray,
        globalNotice   : mergedGlobalArray,
        shortcodeNotice: mergedShortcodeArray,
        pressbar       : pressbar,
        gdpr           : gdpr,
        popup          : popup,
    };
};

export const normalize = (_entries, globalSettings) => {
    let mergedArray = [];
    _entries = _entries || {};
    for (const key in _entries) {
        if (Object.hasOwnProperty.call(_entries, key)) {
            let settings = _entries[key]?.post;
            let template;
            template = settings?.template_adv ? settings?.advanced_template?.split?.(/\r\n|\r|\n/) : GetTemplate(settings);
            if (settings?.global_queue) {
                settings = { ...settings, ...globalSettings, template };
            }
            else {
                settings = { ...globalSettings, ...settings, template };
            }
            const entries = Object.values(_entries[key]?.entries)
                ?.filter(isNotClosed)
                ?.map((entry) => {
                    return {
                        data: entry,
                        props: settings,
                    };
                });
            if (settings?.global_queue) {
                mergedArray = [...mergedArray, ...entries];
            }
            else {
                mergedArray = [...mergedArray, [...entries]];
            }
        }
    }
    return mergedArray;
}

export const normalizePressBar = (_entries, globalSettings) => {
    let mergedArray = [];
    _entries = _entries || {};
    for (const key in _entries) {
        let entry = _entries[key];
        if (isNotClosed(entry)) {
            mergedArray = [...mergedArray, { ...entry, post: { ...globalSettings, ...entry.post } }];
        }
    }
    return mergedArray;
}
export const isAdminBar = () => {
    const adminBar = document.getElementById("wpadminbar");
    if (adminBar) {
        return true;
    }
    return false;
};