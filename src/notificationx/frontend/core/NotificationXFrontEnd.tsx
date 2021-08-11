import React from "react";
import { NotificationProvider, NotificationContainer, useNotificationX } from "./index";

import "../scss/theme.scss";

const NotificationXFrontEnd = (props) => {
    const notificationx = useNotificationX(props);
    return <NotificationProvider value={notificationx}>
        <NotificationContainer />
    </NotificationProvider>;
};

export default NotificationXFrontEnd;