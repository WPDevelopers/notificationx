import React, { useCallback, useEffect, useReducer, useRef } from 'react'
import notificationXReducer from './notificationXReducer';

// @ts-ignore
import { __experimentalGetSettings } from "@wordpress/date";
import nxHelper from '../core/functions';

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
        },
        redirect: {
        }
    });

    const setOptions = (field, value) => {
        dispatch({
            type: "SET_COMMON_OPTIONS",
            payload: { field, value }
        })
    }

    const setRedirect = (redirectData) => {
        const {state, keepHash, ...rest} = redirectData;
        const redirect = nxHelper.getRedirect(rest, keepHash);
        dispatch({
            type: 'SET_REDIRECT',
            payload: {
                ...redirect,
                state,
            }
        });
    };

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
        setRedirect,
        getSettings: getSettings,
        setOptions: setOptions,
        getOptions: getOptions,
    }
}

export default useNotificationX;