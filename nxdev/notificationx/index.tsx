import React, { useState } from "react";
import {
    BrowserRouter as Router,
    Route as R,
    Redirect,
} from "react-router-dom";

import "./scss/index.scss";
import { NotificationXProvider, useNotificationX } from "./hooks";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import Route from "./Route";
import { __ } from "@wordpress/i18n";

const NotificationX = (props) => {
    // const builder = useBuilder(notificationxTabs);
    // const builder = useBuilder(defaultArgs);
    const [title, setTitle] = useState("");
    if (!title) {
        let documentTitle = document.querySelector("title").text;
        documentTitle = documentTitle
            .replace(__("All NotificationX", 'notificationx'), "")
            .replace(__("Add New", 'notificationx'), "")
            .replace(__("Settings", 'notificationx'), "")
            .replace(__("Analytics", 'notificationx'), "")
            .replace(__("Quick Builder", 'notificationx'), "");
        setTitle(documentTitle);
    }

    const builder = useNotificationX({ ...notificationxTabs, title });
    let basename;
    try {
        const url = new URL(builder.admin_url);
        basename = url.pathname.replace(/\/$/, "");
    }
    catch (e){
        basename = builder.admin_url.replace(/\/$/, "");
    }

    return (
        <Router basename={basename}>
            <div className="notificationx-main">
                <NotificationXProvider value={builder}>
                    {
                        builder.state?.redirect?.pathname &&
                        <Redirect to={builder.state.redirect} />
                    }
                    <R component={Route} />
                    <ToastContainer />
                </NotificationXProvider>
            </div>
        </Router>
    );
};

export default NotificationX;
