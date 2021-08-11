import React from "react";
import ReactDOM from "react-dom";
import apiFetch from "@wordpress/api-fetch";
import { NotificationXFrontEnd } from "./core";

(function (notificationX) {
    if(!notificationX?.rest)
        return;
    apiFetch.use(apiFetch.createNonceMiddleware(notificationX.rest.nonce));

    window.addEventListener("DOMContentLoaded", function () {
        // let notificationxFrontendRoot = document.createElement("div");
        // notificationxFrontendRoot.id = "notificationx-frontend-root";
        // notificationxFrontendRoot.innerHTML =
        //     "<!-- This DIV for NotificationX, It is used to show the notifications by appending them here. For more details please visit: https://notificationx.com/docs -->";
        // let notificationxFrontend = document.createElement("div");
        // notificationxFrontend.id = "notificationx-frontend";
        // notificationxFrontendRoot.append(notificationxFrontend);
        // document.body.append(notificationxFrontendRoot);

        ReactDOM.render(
            <NotificationXFrontEnd config={notificationX} />,
            document.getElementById("notificationx-frontend")
        );

    });
    // @ts-ignore
})(window.notificationX);
