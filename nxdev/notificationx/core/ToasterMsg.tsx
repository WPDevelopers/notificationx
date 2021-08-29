import React, { useState } from 'react'
import { useNotificationXContext } from '../hooks';
import { toast } from 'react-toastify';
import { isObject } from '../../form-builder/src/core/utils';

const getToasterIcon = (url) => {
    return (notificationxTabs?.assets?.admin + url + '?version=' + Math.random());
};

export const ToasterIcons = {
    deleted: () => getToasterIcon('images/toaster-icon/Deleted.gif'),
    regenerated: () => getToasterIcon('images/toaster-icon/Regenerated.gif'),
    enabled: () => getToasterIcon('images/toaster-icon/Enable.gif'),
    disabled: () => getToasterIcon('images/toaster-icon/Disable.gif'),
    error: () => getToasterIcon('images/toaster-icon/Error.gif'),
    connected: () => getToasterIcon('images/toaster-icon/Connected.gif'),
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

// export const SuccessMsg = (msg) => {
export const ConnectedMsg = (msg) => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.connected()} alt="" />
            <p>{msg}</p>
        </div>
    )
}

export const ErrorMsg = (msg) => {
    return (
            <div className="nx-toast-wrapper">
            <img src={ToasterIcons.error()} alt="" />
            <p>{msg}</p>
        </div>
    )
}

export const RegeneratedMsg = (msg) => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.regenerated()} alt="" />
            <p>{msg}</p>
        </div>
    )
}

export const EnabledMsg = (msg) => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.enabled()} alt="" />
            <p>{msg}</p>
        </div>
    )
}

export const DisabledMsg = (msg) => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.disabled()} alt="" />
            <p>{msg}</p>
        </div>
    )
}
export const DeletedMsg = (msg) => {
    return (
        <div className="nx-toast-wrapper">
            <img src={ToasterIcons.deleted()} alt="" />
            <p>{msg}</p>
        </div>
    )
}


export const ToastAlert = (type, message, args?) => {
    type    = type || null;
    message = message || null;
    const promise = new Promise((resolve, reject) => {
        args = args || {};
        const defaultArgs = {...toastDefaultArgs, ...args, onClose: resolve}
        if (type == 'success' || type == 'connected') {
            toast.info(ConnectedMsg(message), defaultArgs);
        }
        if (type == 'error') {
            toast.error(ErrorMsg(message), defaultArgs);
        }
        if (type == 'regenerated') {
            toast.info(RegeneratedMsg(message), defaultArgs);
        }
        if (type == 'enabled') {
            toast.info(EnabledMsg(message), defaultArgs);
        }
        if (type == 'disabled') {
            toast.warning(DisabledMsg(message), defaultArgs);
        }
        if (type == 'deleted') {
            toast.error(DeletedMsg(message), defaultArgs);
        }
        if(!type){
            reject();
        }
    });
    return promise;
}


const nxToast = {
    connected  : (message, args?) => ToastAlert('connected', message, args),
    info       : (message, args?) => ToastAlert('connected', message, args),
    error      : (message, args?) => ToastAlert('error', message, args),
    regenerated: (message, args?) => ToastAlert('regenerated', message, args),
    enabled    : (message, args?) => ToastAlert('enabled', message, args),
    disabled   : (message, args?) => ToastAlert('disabled', message, args),
    warning    : (message, args?) => ToastAlert('disabled', message, args),
    deleted    : (message, args?) => ToastAlert('deleted', message, args),
}
export default nxToast;