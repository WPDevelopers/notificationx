import { useReducer, useEffect, useRef, useState, useCallback } from "react";
import { frontendReducer } from ".";
import { proccesNotice, isNotClosed, getTime } from "./utils";
import { v4 } from "uuid";
import cookie from "react-cookies";
import sortArray from "sort-array";

const useNotificationX = (props: any) => {
    const [state, dispatch] = useReducer(frontendReducer, {
        notices: {},
        templates: {},
    });

    const isMounted = useRef(null);

    const [activeNotices, setActiveNotices] = useState(null);
    const [globalNotices, setGlobalNotices] = useState(null);
    const [pressbarNotices, setPressbarNotices] = useState(null);
    const [shortcodeNotices, setShortcodeNotices] = useState(null);

    const dispatchNotification = useCallback(( { data, config, ...args } ) => {
            if (!isNotClosed(data)) {
                args?.intervalID && clearInterval(args?.intervalID);
                args?.timeoutID && clearTimeout(args?.timeoutID);
                return;
            }
            const ID = v4();
            dispatch({
                type: "ADD_NOTIFICATION",
                payload: { id: ID, data, config },
            });
            return ID;
        },
        [ state ],
    );

    useEffect(() => {
        isMounted.current = true;
        // console.log("props frontend", props);
        // Fetch Notices
        proccesNotice(props).then((response: any) => {
            // Add Active Notices into State
            if (isMounted.current) {
                setActiveNotices(response?.activeNotice);

                let gNotices = response?.globalNotice;
                sortArray(gNotices, {
                    by: 'timestamp',
                    order: 'desc',
                    computed: {
                        timestamp: row => row.data?.timestamp ? row.data.timestamp : getTime(row.data?.updated_at)
                    }
                });

                setGlobalNotices(gNotices);
                setShortcodeNotices(response?.shortcodeNotice);
                setPressbarNotices(response?.pressbar);
            }
        });
        return () => {
            isMounted.current = false;
        };
    }, []);

    /**
     * Active Notices Dispatch Mechanism
     */
    useEffect(() => {
        // Procces to render;
        if (activeNotices != null && activeNotices.length > 0) {
            activeNotices.forEach((entries) => {
                if (entries?.length > 0) {
                    const reverseEntries = entries; //.reverse();
                    let id = 0;
                    const config = reverseEntries[id].props;
                    const delayBefore = (config?.delay_before || 5) * 1000;
                    const delayBetween = (config?.delay_between || 5) * 1000;
                    const displayFor = (config?.display_for || 5) * 1000;

                    let args = {
                        id,
                        count: reverseEntries?.length || 0,
                        loop: config?.loop || false,
                        intervalID: null,
                        timeoutID: null,
                        data: null,
                        config
                    }

                    const timeoutID = setTimeout(() => {
                        args.timeoutID = timeoutID;
                        args.data = reverseEntries[id].data;
                        dispatchNotification( args );
                        // notice
                        const intervalID = setInterval(() => {
                            if ( id === reverseEntries?.length - 1 ) {
                                if (config.loop) {
                                    id = 0;
                                } else {
                                    clearInterval(intervalID);
                                    clearInterval(timeoutID);
                                }
                            } else {
                                id++;
                            }
                            args.intervalID = intervalID;
                            args.data = reverseEntries[id].data;
                            dispatchNotification( args );
                        }, displayFor + delayBetween );
                    }, delayBefore )
                }
            });
        }
    }, [activeNotices]);
    /**
     * Global Notices Dispatch Mechanism
     */
    useEffect(() => {
        // Procces to render;
        if (globalNotices != null && globalNotices.length > 0) {
            let   id           = 0;
            const config      = globalNotices[id].props;
            const delayBefore  = (config?.delay_before || 5) * 1000;
            const delayBetween = (config?.delay_between || 5) * 1000;
            const displayFor = (config?.display_for || 5) * 1000;

            let args = {
                id,
                count: globalNotices?.length || 0,
                loop: config?.loop || false,
                intervalID: null,
                timeoutID: null,
                data: null,
                config
            }

            const timeoutID = setTimeout(() => {
                args.timeoutID = timeoutID;
                args.data      = globalNotices[id].data;
                dispatchNotification( args );
                const intervalID = setInterval(() => {
                    if ( id === globalNotices?.length - 1 ) {
                        if (args.loop) {
                            id = 0;
                        } else {
                            clearInterval(intervalID);
                            clearInterval(timeoutID);
                        }
                    } else {
                        id++;
                    }
                    const config = globalNotices[id].props;
                    args.intervalID = intervalID;
                    args.loop = config?.loop || false;
                    args.config = config;
                    args.data = globalNotices[id].data;
                    dispatchNotification( args );
                }, delayBetween + displayFor);
            }, delayBefore)
        }
    }, [globalNotices]);
    /**
     * Pressbar
     */
    useEffect(() => {
        // Process to render;
        if (pressbarNotices != null && pressbarNotices.length > 0) {
            pressbarNotices.forEach((nxBar) => {
                const config = nxBar.post;
                const initialDelay = (+config?.initial_delay || 5) * 1000;
                const hideAfter = (+config?.hide_after || 5) * 1000;

                let args = {
                    intervalID: null,
                    timeoutID: null,
                    data: null,
                    config
                }

                const timeoutID = setTimeout(() => {
                    args.timeoutID = timeoutID;
                    args.data = nxBar.content;
                    const ID = dispatchNotification(args);

                    if (config?.auto_hide && +config?.hide_after) {
                        if (config?.close_forever) {
                            const expires = new Date();
                            expires.setDate(
                                expires.getDate() +
                                    (config?.time_reset ? 1 : 365)
                            );
                            let countRand = config?.countdown_rand ? `-${config.countdown_rand}` : '';
                            cookie.save(
                                "notificationx_" + config?.nx_id + countRand,
                                true,
                                { path: "/", expires }
                            );
                        }
                        setTimeout(() => {
                            dispatch({
                                type: "REMOVE_NOTIFICATION",
                                payload: ID,
                            });
                        }, hideAfter);
                    }
                }, initialDelay);
            });
        }
    }, [pressbarNotices]);

    /**
     * ShortCode Dispatch
     */
    useEffect(() => {
        // Procces to render;
        if (shortcodeNotices != null && shortcodeNotices.length > 0) {
            shortcodeNotices.forEach((entries, index) => {
                if (entries?.length > 0) {
                    const reverseEntries = entries; //.reverse();

                    let id = 0;
                    const config = reverseEntries[id].props;
                    const delayBefore = (config?.delay_before || 5) * 1000;
                    const delayBetween = (config?.delay_between || 5) * 1000;
                    const displayFor = (config?.dislay_for || 5) * 1000;

                    let args = {
                        id,
                        count: reverseEntries?.length || 0,
                        loop: config?.loop || false,
                        intervalID: null,
                        timeoutID: null,
                        data: null,
                        config
                    }

                    const timeoutID = setTimeout(() => {
                        args.timeoutID = timeoutID;
                        args.data = reverseEntries[id].data;
                        dispatchNotification( args );
                        const intervalID = setInterval(() => {
                            if ( id === reverseEntries?.length - 1 ) {
                                if (config.loop) {
                                    id = 0;
                                } else {
                                    clearInterval(intervalID);
                                    clearInterval(timeoutID);
                                }
                            } else {
                                id++;
                            }
                            args.intervalID = intervalID;
                            args.data = reverseEntries[id].data;
                            dispatchNotification( args );
                        }, delayBetween + displayFor );
                    }, delayBefore)
                }
            });
        }
    }, [shortcodeNotices]);

    const getNxToRender = (callback: (position, NoticeList: []) => void) => {
        const noticeToRender = {};
        for (let i = 0; i < state.notices.length; i++) {
            const notice = state.notices[i];
            const { position } = notice.config;
            noticeToRender[position] || (noticeToRender[position] = []);
            noticeToRender[position]!.push(notice);
        }
        return (Object.keys(noticeToRender) as Array<any>).map((p) =>
            callback(p, noticeToRender[p]!)
        );
    };

    return {
        isMounted,
        state,
        dispatch,
        getNxToRender,
        assets: { free: props.config.assets, pro: props.config?.pro_assets },
    };
};

export default useNotificationX;