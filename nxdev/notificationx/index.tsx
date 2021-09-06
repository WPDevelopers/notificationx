import React, { useEffect, useState } from "react";
import {
    BrowserRouter as Router,
    Route as R,
    Switch,
    Redirect,
    useParams,
    matchPath,
    useHistory,
} from "react-router-dom";

import "./scss/index.scss";
import { NotificationXProvider, useNotificationX } from "./hooks";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import Route from "./Route";

// import defaultArgs from '../form-builder/config/default';

const NotificationX = (props) => {
    // const builder = useBuilder(notificationxTabs);
    // const builder = useBuilder(defaultArgs);
    const [title, setTitle] = useState("");
    if (!title) {
        let documentTitle = document.querySelector("title").text;
        documentTitle = documentTitle
                            .replace("All NotificationX", "")
                            .replace("Add New", "")
                            .replace("Settings", "")
                            .replace("Analytics", "")
                            .replace("Quick Builder", "");
        setTitle(documentTitle);
    }

    const builder  = useNotificationX({ ...notificationxTabs, title });
    const url      = new URL(builder.admin_url);
    const basename = url.pathname.replace(/\/$/, "");

    return (
        <Router basename={basename}>
            <div className="notificationx-main">
                <NotificationXProvider value={builder}>
                    {
                        builder.state?.redirect?.to &&
                        <Redirect to={builder.state.redirect.to} />
                    }
                    <R  component={Route} />
                    <ToastContainer />
                </NotificationXProvider>
            </div>
        </Router>
    );
};

export default NotificationX;
