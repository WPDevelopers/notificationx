import React from "react";
import ReactDOM from "react-dom";
import domReady from '@wordpress/dom-ready';
import { setLocaleData } from "@wordpress/i18n";
import { NotificationXFrontEnd } from "./core";

function notificationXWrapper(notificationX, id) {
    if (!notificationX?.rest)
        return;

    if(notificationX.localeData){
        const localeData = JSON.parse(notificationX.localeData).locale_data.messages;
        localeData[""].domain = 'notificationx';
        setLocaleData(localeData, 'notificationx');
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

    ReactDOM.render(
        <NotificationXFrontEnd config={notificationX} />,
        document.getElementById("notificationx-frontend" + id)
    );
    // @ts-ignore
}



domReady(function () {
    (function (notificationX) {

        notificationX?.map((nx, index) => notificationXWrapper(nx, index))

    // @ts-ignore
    })(window.notificationXArr);
});
