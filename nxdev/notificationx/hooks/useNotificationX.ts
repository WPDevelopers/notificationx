import React, { useCallback, useEffect, useReducer, useRef } from 'react'
import notificationXReducer from './notificationXReducer';

// @ts-ignore
import { __experimentalGetSettings } from "@wordpress/date";

const useNotificationX = ( props ) => {
    const isMounted = useRef(null);
    const timeSettings = useRef({});
    useEffect(() => {
        isMounted.current = true;
        timeSettings.current = __experimentalGetSettings();
        if( timeSettings.current ) {
            dispatch({
                type: "SET_TIME_SETTINGS",
                payload: timeSettings.current
            })
        }
        return () => { isMounted.current = false };
    }, [])

    const [state, dispatch] = useReducer(notificationXReducer, {
        settings: {
            time: timeSettings.current
        }
    });

    const setOptions = (field, value) => {
        dispatch({
            type: "SET_COMMON_OPTIONS",
            payload: { field, value }
        })
    }

    const getOptions = useCallback(
        ( name ) => {
            return state.common?.[name];
        },
        [state.common],
    );

    const getSettings = useCallback(
        ( key ) => {
            return state.settings?.[key];
        },
        [state.settings],
    );

    return {
        ...props,
        state,
        dispatch,
        getSettings: getSettings,
        setOptions: setOptions,
        getOptions: getOptions,
    }
}

export default useNotificationX;