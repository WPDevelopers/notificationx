import React from "react";
import ReactDOM from "react-dom";
import domReady from '@wordpress/dom-ready';
import { NotificationXFrontEnd } from "./core";

(function (notificationX) {
    if (!notificationX?.rest)
        return;

    domReady(function () {
        ReactDOM.render(
            <NotificationXFrontEnd config={notificationX} />,
            notificationX?.cross ? document.getElementById("notificationx-frontend-crosssite") : document.getElementById("notificationx-frontend")
        );
    });
    // @ts-ignore
})(window.nxCrossSite || window.notificationX);
