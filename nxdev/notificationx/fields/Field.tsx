import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';
import Modal from './Modal';



const Field = (r, type, props) => {

    switch (type) {
        case "advanced-template":
            return <AdvancedTemplate {...props} />;
        case "preview-modal":
            return <Modal {...props} />;
        default:
            return <></>;
    }
};

export default Field;