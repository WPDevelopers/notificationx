import React from "react";
import { __ } from "@wordpress/i18n";
const Button = ({ config }) => {
    const { themes } = config;
    if( [ "announcements_theme-15", "announcements_theme-14" ].includes(themes) ) {
        return (
            <a href="#">{ __( 'Buy Now', 'notificationx' ) }</a>
        );
    }else{
        return (<></>);
    }
};

export default Button;
