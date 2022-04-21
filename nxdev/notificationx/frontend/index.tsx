import React from "react";
import ReactDOM from "react-dom";
import apiFetch from "@wordpress/api-fetch";
import domReady from '@wordpress/dom-ready';
import { NotificationXFrontEnd } from "./core";
apiFetch.use(apiFetch.createNonceMiddleware(''));

// export const NxFrontEndWrapper = ({ config: notifications }) => {
//     return (
// notifications.map((nx, index) => notificationXWrapper(nx, index))
//     )
// }
// @ts-ignore
const notificationX = [window?.notificationX, window?.nxCrossSite];

function notificationXWrapper(notificationX, id) {
    if (!notificationX?.rest)
        return;
    // apiFetch.use(apiFetch.createNonceMiddleware(''));
    // apiFetch.use(apiFetch.createRootURLMiddleware(notificationX.rest.root));

    let xDiv = document.createElement('div');
    xDiv.id = 'notificationx-frontend' + id;
    xDiv.classList.add('notificationx-frontend');

    document.body.appendChild(xDiv);

    console.log("notificationX", notificationX);


    domReady(function () {
        ReactDOM.render(
            <NotificationXFrontEnd config={notificationX} />,
            document.getElementById("notificationx-frontend" + id)
            // notificationX?.cross ? document.getElementById("notificationx-frontend-crosssite") : document.getElementById("notificationx-frontend")
        );
    });
    // @ts-ignore
}


(function (notificationX) {

    // console.log("notificationX", notificationX);


    notificationX.map((nx, index) => notificationXWrapper(nx, index))

    // if (!notificationX?.rest)
    //     return;
    // // apiFetch.use(apiFetch.createNonceMiddleware(notificationX.rest.nonce));
    // apiFetch.use(apiFetch.createRootURLMiddleware(notificationX.rest.root));

    // domReady(function () {
    //     ReactDOM.render(
    //         <NxFrontEndWrapper config={notificationX} />,
    //         notificationX?.cross ? document.getElementById("notificationx-frontend-crosssite") : document.getElementById("notificationx-frontend")
    //     );
    // });
    // @ts-ignore
})(notificationX);
