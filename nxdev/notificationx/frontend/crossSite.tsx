(function (notificationX) {
    if(notificationX){
        // @ts-ignore
        window.notificationXArr = window.notificationXArr || [];
        // @ts-ignore
        window.notificationXArr.push(notificationX);
    }
    // @ts-ignore
})(window.nxCrossSite);


import './index';
