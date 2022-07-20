import React from "react";
import ReactDOM from "react-dom";
import domReady from '@wordpress/dom-ready';
import { NotificationXFrontEnd } from "./core";

// export const NxFrontEndWrapper = ({ config: notifications }) => {
//     return (
// notifications.map((nx, index) => notificationXWrapper(nx, index))
//     )
// }
// @ts-ignore

function notificationXWrapper(notificationX, id) {
    if (!notificationX?.rest)
        return;

    let xDiv = document.createElement('div');
    xDiv.id = 'notificationx-frontend' + id;
    xDiv.classList.add('notificationx-frontend');

    document.body.appendChild(xDiv);

    console.log("notificationX", notificationX);


    ReactDOM.render(
        <NotificationXFrontEnd config={notificationX} />,
        document.getElementById("notificationx-frontend" + id)
        // notificationX?.cross ? document.getElementById("notificationx-frontend-crosssite") : document.getElementById("notificationx-frontend")
    );
    // @ts-ignore
}



domReady(function () {
    (function (notificationX) {

        notificationX.map((nx, index) => notificationXWrapper(nx, index))

    // @ts-ignore
    })(window.notificationXArr);
});
