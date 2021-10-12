import React from "react";
import ReactDOM from "react-dom";
import { addFilter } from '@wordpress/hooks'
import NotificationX from "./notificationx/index";
import { Sidebar } from "./notificationx/admin/Settings";
import Loader from "./notificationx/components/Loader";
import apiFetch from "@wordpress/api-fetch";
import 'quickbuilder/dist/index.css';

(function () {
    if(notificationxTabs?.rest?.root){
        apiFetch.use(apiFetch.createNonceMiddleware(notificationxTabs.rest.nonce));
        apiFetch.use(apiFetch.createRootURLMiddleware(notificationxTabs.rest.root));
        // apiFetch.nonceEndpoint = admin_url( 'admin-ajax.php?action=rest-nonce' )
        // wp_default_packages_inline_scripts
    }

    addFilter('wprf_tab_content', 'NotificationX', (x, props) => {
        return !props.is_pro_active && props.current_page === 'settings' && <Sidebar assetsUrl={props.assets} is_pro_active={props.is_pro_active} />
    })

    addFilter('nxpro_preloader', 'NotificationX', (ProContent, isLoading) => {
        return isLoading ? <Loader /> : ProContent;
    })

    ReactDOM.render(
        <NotificationX />,
        document.getElementById("notificationx")
    );
})();
