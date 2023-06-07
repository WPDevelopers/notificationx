import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';
import PreviewModal from './PreviewModal';
import FlashingMessageIcon from './FlashingMessageIcon';
import ThemeOne from './ThemeOne';
import ThemeFour from './ThemeFour';



const Field = (r, type, props) => {

    switch (type) {
        case "advanced-template":
            return <AdvancedTemplate {...props} />;
        case "preview-modal":
            return <PreviewModal {...props} />;
        case "flashing-message-icon":
            return <FlashingMessageIcon {...props} />;
        case "flashing-theme-one":
            return <ThemeOne {...props} />;
        case "flashing-theme-four":
            return <ThemeFour {...props} />;
        default:
            return <></>;
    }
};

export default Field;
