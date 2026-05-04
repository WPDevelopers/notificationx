import React, { useState } from 'react';

const ExitIntentPopup = (props: any) => {
    const { nxExitIntent, dispatch } = props;
    const { config: settings } = nxExitIntent;
    const [isVisible, setIsVisible] = useState(true);

    const handleClose = () => {
        const sessionKey = `notificationx_exit_intent_${settings?.nx_id}`;
        sessionStorage.setItem(sessionKey, 'closed');
        setIsVisible(false);
        if (dispatch) {
            dispatch({ type: "REMOVE_NOTIFICATION", payload: nxExitIntent.id });
        }
    };

    if (!isVisible) return null;

    return (
        <div className="nx-exit-intent-overlay" onClick={handleClose}>
            <div className="nx-exit-intent-popup" onClick={(e) => e.stopPropagation()}>
                <button className="nx-exit-intent-close" onClick={handleClose} aria-label="Close">
                    &times;
                </button>
                <div className="nx-exit-intent-content">
                    <h2>Hello World</h2>
                </div>
            </div>
        </div>
    );
};

export default ExitIntentPopup;
