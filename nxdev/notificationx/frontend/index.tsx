import React from "react";
import ReactDOM from "react-dom";
import domReady from '@wordpress/dom-ready';
import { setLocaleData } from "@wordpress/i18n";
import { NotificationXFrontEnd } from "./core";
import { exposeFrontendRuntime } from "./core/runtime";
import { loadExternalStyles, whenStyled } from "./core/external-styles";

declare let __webpack_public_path__: string;

// Must run before domReady renders anything: add-on scripts that load after
// this bundle read window.nxFrontendRuntime when their modules evaluate.
exposeFrontendRuntime();

// Lazy chunks (moment locales, announcement themes) load from the directory
// webpack derives from this script's URL, `<plugin>/assets/public/js/../../`.
// When an optimizer serves the bundle from somewhere else (Autoptimize's
// cache, a combined file) that directory has no chunks, the imports fail and
// nothing renders. Use the localized asset URL then. The config is pushed after
// this script tag, so this runs from notificationXWrapper(), before any import.
const setChunkPath = (assets?: unknown) => {
    if (typeof assets === 'string' && assets && !/\/public\/js\/\.\.\/\.\.\/$/.test(__webpack_public_path__)) {
        __webpack_public_path__ = assets.replace(/public\/?$/, '');
    }
};

function notificationXWrapper(notificationX, id) {
    if (!notificationX?.rest)
        return;

    setChunkPath(notificationX.assets);

    if (notificationX.cross) {
        loadExternalStyles(notificationX.external_styles);
    }

    if(notificationX.localeData){
        const localeData = JSON.parse(notificationX.localeData)?.locale_data;
        if(localeData?.messages){
            localeData.messages[""].domain = 'notificationx';
            setLocaleData(localeData.messages, 'notificationx');
        }
        else if(localeData?.['notificationx']){
            localeData['notificationx'][""].domain = 'notificationx';
            setLocaleData(localeData['notificationx'], 'notificationx');
        }
    }
    let lang = notificationX.lang?.replace('_', '-')?.toLowerCase();
    if(lang && lang !== "en" && lang !== "en-us"){
        import("moment/locale/" + lang).catch(err => {
            lang = lang.split('-')[0];
            import("moment/locale/" + lang).catch(err => {
                console.log("Couldn't locate moment/locale/" + lang);
            });
        });
    }

    let xDiv = document.createElement('div');
    xDiv.id = 'notificationx-frontend' + id;
    xDiv.classList.add('notificationx-frontend');

    document.body.appendChild(xDiv);

    whenStyled('notificationx-public-css').then(() => {
        ReactDOM.render(
            <NotificationXFrontEnd config={notificationX} />,
            xDiv
        );
    });
    // @ts-ignore
}

function inIframe () {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

domReady(function () {
    // @ts-ignore
    if(inIframe() && !window.notificationXArr?.[0]?.nxPreview){
        console.error("NotificationX: NotificationX doesn't work in iframe.");
        return;
    }

    (function (notificationX) {

        notificationX?.map((nx, index) => notificationXWrapper(nx, index))

    // @ts-ignore
    })(window.notificationXArr);

    if (!("Proxy" in window)) {
        return;
    }

    // @ts-ignore
    window.notificationXArr = new Proxy(window.notificationXArr || [], {
        set: function(target, property, value, receiver) {
            target[property] = value;

            if('length' !== property){
                notificationXWrapper(value, property);
            }
            return true;
        }
    });
});
