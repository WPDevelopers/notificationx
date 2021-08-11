import apiFetch from "@wordpress/api-fetch";
import { GetTemplate } from "../themes";
import cookie from "react-cookies";
import nxHelper from "../../core/functions";
// @ts-ignore
import { __experimentalGetSettings } from "@wordpress/date";
import moment from "moment";

// apiFetch.use(apiFetch.createNonceMiddleware(notificationX.rest.nonce));

export const proccesNotice = ({ config }) => {
    return nxHelper
        .post("notice/?frontend=true", {
            global   : config.global,
            active   : config.active,
            pressbar : config.pressbar,
            shortcode: config.shortcode,
        })
        .then((response: any) => {
            let mergedGlobalArray = normalize(response?.global, response?.settings);
            let mergedActiveArray = normalize(response?.active, response?.settings);
            let mergedShortcodeArray = normalize(response?.shortcode, response?.settings);
            let pressbar          = Object.values(response?.pressbar || {})?.filter(isNotClosed);

            return {
                settings: response?.settings,
                activeNotice: mergedActiveArray,
                globalNotice: mergedGlobalArray,
                shortcodeNotice: mergedShortcodeArray,
                pressbar: pressbar,
            };
        })
        .catch((err) => console.error("Fetch Error: ", err));
};

export const isNotClosed = (entry) => {
    const nx_id = entry?.nx_id || entry?.post?.nx_id;
    if (nx_id) {
        let countRand = entry?.post?.countdown_rand ? `-${entry.post.countdown_rand}` : '';
        if (cookie.load("notificationx_" + nx_id + countRand)) {
            return false;
        }
    }
    return true;
};

const normalize = (_entries, globalSettings) => {
    let mergedArray = [];
    _entries = _entries || {};
    for (const key in _entries) {
        if (Object.hasOwnProperty.call(_entries, key)) {
            let settings = _entries[key]?.post;
            let template = settings?.template_adv ? settings?.advanced_template?.split?.(/\r\n|\r|\n/) : GetTemplate(settings);
            if(settings?.global_queue){
                settings = { ...settings, ...globalSettings, template };
            }
            else{
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
            if(settings?.global_queue){
                mergedArray = [...mergedArray, ...entries];
            }
            else{
                mergedArray = [...mergedArray, [...entries]];
            }
        }
    }
    return mergedArray;
}

export const getTime = ( value?, keepLocalTime: boolean = false ) => {
    const settings: any = __experimentalGetSettings();
    const _value = moment.utc(value ? value : undefined).utcOffset(+settings?.timezone?.offset, keepLocalTime);
    return _value;
}