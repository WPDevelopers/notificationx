import { __ } from '@wordpress/i18n';
import { loadExternalStyles } from './core/external-styles';
(function (notificationX) {
    if(notificationX){
        // @ts-ignore
        window.notificationXArr = window.notificationXArr || [];
        // @ts-ignore
        window.notificationXArr.push(notificationX);
        // Legacy embeds load crossSite.css, which no longer @imports the fonts and icons.
        loadExternalStyles(notificationX.external_styles);
    }
    console.warn(__("You are using old version of cross-domain scripts for NotificationX Pro. Please update this from your NotificationX Settings page.", 'notificationx'));
    // @ts-ignore
})(window.nxCrossSite);


import './index';
// Cross-domain embeds can't rely on the PHP enqueue of gdpr-modal.css, so the
// GDPR modal styles stay bundled into crossSite.css.
import './scss/gdpr-modal.scss';
