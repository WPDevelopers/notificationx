import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';
import PreviewModal from './PreviewModal';
import FlashingMessageIcon from './FlashingMessageIcon';



const Field = (r, type, props) => {

    switch (type) {
        case "advanced-template":
            return <AdvancedTemplate {...props} />;
        case "preview-modal":
            return <PreviewModal {...props} />;
        case "flashing-message-icon":
            return <FlashingMessageIcon {...props} />;
        default:
            return <></>;
    }
};

export default Field;
