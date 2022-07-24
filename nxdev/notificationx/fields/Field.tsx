import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';



const Field = (r, type, props) => {

    switch (type) {
        case "advanced-template":
            return <AdvancedTemplate {...props} />;
        default:
            return <></>;
    }
};

export default Field;