import React, { useState } from 'react'

const getToasterIcon = (url) => {
    return (url + '?version=' + Math.random());
};

export const ToasterIcons = {
    deleted: () => getToasterIcon('https://sapanmozammel.shopking.shop/Deleted.gif'),
    regenerated: () => getToasterIcon('https://sapanmozammel.shopking.shop/Regenerated.gif'),
    enabled: () => getToasterIcon('https://sapanmozammel.shopking.shop/Enabled.gif'),
    disabled: () => getToasterIcon('https://sapanmozammel.shopking.shop/Disabled.gif'),
    error: () => getToasterIcon('https://sapanmozammel.shopking.shop/Error.gif'),
    connected: () => getToasterIcon('https://sapanmozammel.shopking.shop/Connected.gif'),
}

export const toastDefaultArgs: any = {
    position: "bottom-right",
    autoClose: 5000,
    hideProgressBar: false,
    closeOnClick: true,
    pauseOnHover: true,
    draggable: true,
    progress: undefined,
};

export const SuccessMsg = () => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.connected()} alt="" />
            <p>Connected Successfully.</p>
        </div>
    )
}

export const ErrorMsg =() => {
    return (
            <div className="nx-toast-wrapper">
            <img src={ToasterIcons.error()} alt="" />
            <p>Oops, Something went wrong. Please try again.</p>
        </div>
    )
}
