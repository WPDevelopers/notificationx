import React from 'react'
import { __ } from '@wordpress/i18n';
import AdvancedTemplate from './AdvancedTemplate';
import PreviewModal from './PreviewModal';
import FlashingThemeOne from './FlashingThemeOne';
import FlashingThemeThree from './FlashingThemeThree';
import FlashingThemeFour from './FlashingThemeFour';
import CSVUpload from './CSVUpload';
import AdvancedRepeater from './AdvancedRepeater';
import AdvancedCodeViewer from './AdvancedCodeViewer';
import GradientPicker from './GradientPicker';
import BetterRepeater from './BetterRepeater';
import BetterToggle from './BetterToggle';
import BetterText from './BetterText';
import CookieScanner from './CookieScanner';
import Preview from './Preview';
import TimePicker from './TimePicker';
import DateRange from './DateRange';
import SimpleRepeater from './SimpleRepeater';
import BetterSelect from './BetterSelect';
import IconPicker from "./IconPicker";
import NxEditor from './NxEditor';

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
        case "advanced-repeater":
            return <AdvancedRepeater {...props} />;
        case "csv-upload":
            return <CSVUpload {...props} />;
        case "advanced-codeviewer":
            return <AdvancedCodeViewer {...props} />;
        case "better-repeater":
            return <BetterRepeater {...props} />;
        case "better-toggle":
            return <BetterToggle {...props} />;
        case "better-text":
            return <BetterText {...props} />;
        case "cookie-scanner":
            return <CookieScanner {...props} />;
        case "preview":
            return <Preview {...props} />;
        case "timepicker":
            return <TimePicker {...props} />;
        case "daterange":
            return <DateRange {...props} />;
        case "simple-repeater":
            return <SimpleRepeater {...props} />;
        case "better-select":
            return <BetterSelect {...props} />;
        case "gradientpicker":
            return <GradientPicker {...props} />;
        case "icon-picker":
            return <IconPicker {...props} />;
        case "nx-editor":
            return <NxEditor {...props} />;
        default:
            return ret;
    }
};

export default Field;
