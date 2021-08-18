import React, { useEffect, useState } from "react";
import { HashRouter as Router, Switch, Route, Redirect } from "react-router-dom";
import "./scss/index.scss";
import { Admin, AddNewNotification, EditNotification, Settings, Analytics, Entries, QuickBuild } from "./admin/index";
import { NotificationXProvider, useNotificationX } from "./hooks";

const NotificationX = (props) => {
    // const builder = useBuilder(notificationxTabs);
    // const builder = useBuilder(defaultArgs);
    const [title, setTitle] = useState("")
    if (!title) {
        let documentTitle = document.querySelector('title').text;
        documentTitle = documentTitle.replace("All NotificationX", '');
        setTitle(documentTitle);
    }

    const builder = useNotificationX({ ...notificationxTabs, title });

    return (
        <Router>
            <div className="notificationx-main">
                <NotificationXProvider value={builder}>
                    {
                        // builder?.redirect?.to &&
                        // <Redirect to={builder?.redirect?.to} />
                    }
                    <Switch>
                        <Route path="/" exact component={Admin} />
                        <Route
                            path="/add-new"
                            exact
                            component={AddNewNotification}
                        />
                        <Route
                            path="/add-new/:clone"
                            exact
                            component={AddNewNotification}
                        />
                        <Route
                            path="/edit/:edit"
                            exact
                            component={EditNotification}
                        />
                        <Route path="/settings" exact component={Settings} />
                        <Route path="/analytics" exact component={Analytics} />
                        <Route path="/entries/:id" exact component={Entries} />
                        <Route path="/nx-builder" exact component={QuickBuild} />
                    </Switch>
                </NotificationXProvider>
            </div>
        </Router >
    );
};

export default NotificationX;
