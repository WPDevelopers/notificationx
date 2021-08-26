import React from "react";
import ReactDOM from "react-dom";
import apiFetch from "@wordpress/api-fetch";
import domReady from '@wordpress/dom-ready';
import { NotificationXFrontEnd } from "./core";

(function (notificationX) {
    if (!notificationX?.rest)
        return;
    // apiFetch.use(apiFetch.createNonceMiddleware(notificationX.rest.nonce));
    apiFetch.use(apiFetch.createRootURLMiddleware(notificationX.rest.root));

    domReady(function () {
        ReactDOM.render(
            <NotificationXFrontEnd config={notificationX} />,
            document.getElementById("notificationx-frontend")
        );
    });
    // @ts-ignore
})(window.notificationX);
