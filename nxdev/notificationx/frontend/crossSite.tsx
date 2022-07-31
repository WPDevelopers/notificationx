import { __ } from '@wordpress/i18n';
(function (notificationX) {
    if(notificationX){
        // @ts-ignore
        window.notificationXArr = window.notificationXArr || [];
        // @ts-ignore
        window.notificationXArr.push(notificationX);
    }
    console.warn(__("You are using old version of cross-domain scripts for NotificationX Pro. Please update this from your NotificationX Settings page.", 'notificationx'));
    // @ts-ignore
})(window.nxCrossSite);


import './index';
