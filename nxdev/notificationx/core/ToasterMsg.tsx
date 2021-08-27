import React from 'react'
import ConnectedToastIcon from "../icons/ConnectedSuccessful";
import ErrorToastIcon from "../icons/Error";

export const toastDefaultArgs: any = {
    position: "bottom-right",
    autoClose: 5000,
    hideProgressBar: false,
    closeOnClick: true,
    pauseOnHover: true,
    draggable: true,
    progress: undefined,
};

export function SuccessMsg() {
    return (
        <div className="nx-toast-wrapper">
            <ConnectedToastIcon />
            <p>Connected Successfully.</p>
        </div>
    )
}

export function ErrorMsg() {
    return (
        <div className="nx-toast-wrapper">
            <ErrorToastIcon />
            <p>Oops, Something went wrong. Please try again.</p>
        </div>
    )
}
