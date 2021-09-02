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
        documentTitle = documentTitle.replace("All NotificationX", "");
        setTitle(documentTitle);
    }

    const builder = useNotificationX({ ...notificationxTabs, title });



    return (
        <Router basename="/wp-admin/">
            <div className="notificationx-main">
                <NotificationXProvider value={builder}>
                    {
                        // builder?.redirect?.to &&
                        // <Redirect to={builder?.redirect?.to} />
                    }
                    <R  component={Route} />
                    <ToastContainer />
                </NotificationXProvider>
            </div>
        </Router>
    );
};

export default NotificationX;
