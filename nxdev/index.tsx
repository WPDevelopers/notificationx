import React from "react";
import ReactDOM from "react-dom";
import { addFilter } from '@wordpress/hooks'
import NotificationX from "./notificationx/index";
import { Sidebar } from "./notificationx/admin/Settings";
import Loader from "./notificationx/components/Loader";
import Field from "./notificationx/fields/Field";
import 'quickbuilder/dist/index.css';
import Modal from "./notificationx/fields/Modal";

(function () {

    addFilter('wprf_tab_content', 'NotificationX', (x, props) => {
        return !props.is_pro_active && props.current_page === 'settings' && <Sidebar assetsUrl={props.assets} is_pro_active={props.is_pro_active} />
    })

    addFilter('nxpro_preloader', 'NotificationX', (ProContent, isLoading) => {
        return isLoading ? <Loader /> : ProContent;
    })

    addFilter('custom_field', 'NotificationX', Field);
    // addFilter('nx-stepped-button-after', 'NotificationX', () => {

    //     return <Modal ></Modal>;
    // });

    ReactDOM.render(
        <NotificationX />,
        document.getElementById("notificationx")
    );
})();
