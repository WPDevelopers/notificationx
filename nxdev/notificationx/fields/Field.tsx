import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';
import PreviewModal from './PreviewModal';
import FlashingThemeOne from './FlashingThemeOne';
import FlashingThemeThree from './FlashingThemeThree';
import FlashingThemeFour from './FlashingThemeFour';



const Field = (ret, type, props) => {

    switch (type) {
        case "advanced-template":
            return <AdvancedTemplate {...props} />;
        case "preview-modal":
            return <PreviewModal {...props} />;
        case "flashing-theme-one":
        case "flashing-theme-two":
            return <FlashingThemeOne {...props} />;
        case "flashing-theme-three":
            return <FlashingThemeThree {...props} />;
        case "flashing-theme-four":
            return <FlashingThemeFour {...props} />;
        default:
            return ret;
    }
};

export default Field;
