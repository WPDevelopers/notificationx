import { useReducer, useEffect, useRef, useState, useCallback } from "react";
import { frontendReducer } from ".";
import { isNotClosed, normalize, normalizePressBar, normalizeResponse } from "./utils";
import { v4 } from "uuid";
import cookie from "react-cookies";
import sortArray from "sort-array";
import nxHelper from "./functions";
import moment from "moment";
import usePreviewType from "./usePreviewType";

const useNotificationX = (props: any) => {

    const [state, dispatch] = useReducer(frontendReducer, {
        is_pro: props?.config?.is_pro,
        notices: {},
        templates: {},
    });

    const isMounted = useRef(null);
    const previewType = usePreviewType();
    const [activeNotices, setActiveNotices] = useState(null);
    const [globalNotices, setGlobalNotices] = useState(null);
    const [pressbarNotices, setPressbarNotices] = useState(null);
    const [gdprNotices, setGdprNotices] = useState(null);
    const [shortcodeNotices, setShortcodeNotices] = useState(null);

    const getTime = (value?, keepLocalTime: boolean = false) => {
        const _value = moment.utc(value ? value : undefined).utcOffset(+props.config.gmt_offset, keepLocalTime);
        return _value;
    }
    // The Fisher-Yates algorith
    const shuffleArray = array => {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            const temp = array[i];
            array[i] = array[j];
            array[j] = temp;
        }
    }

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
        if(props.config.nxPreview){
            const config = {...props.config};
            // const params = new URLSearchParams(window.location.search);
            // let settings = JSON.parse(params.get('nx-preview'));
            // settings = {...settings, previewType};


            if(Object.keys(config.active).length){
                const filteredConfig = {};
                Object.keys(config.active).forEach((key) => {
                    const active   = config.active[key];
                    let settings = {...active['post'], previewType};
                    if(settings._global_queue){
                        settings = {...settings, ...config.settings};
                    }
                    if(!(previewType === 'phone' && settings.hide_on_mobile)){
                        active['post']      = settings;
                        filteredConfig[key] = active;
                    }
                });
                setActiveNotices(normalize(filteredConfig, config.settings));
            }
            if(Object.keys(config.pressbar).length){
                const filteredConfig = {};
                Object.keys(config.pressbar).forEach((key) => {
                    const pressbar = config.pressbar[key];
                    let settings = {...pressbar['post'], previewType};
                    if(settings._global_queue){
                        settings = {...settings, ...config.settings};
                    }
                    if(!(previewType === 'phone' && settings.hide_on_mobile)){
                        pressbar['post']    = settings;
                        filteredConfig[key] = pressbar;
                    }
                });
                setPressbarNotices(normalizePressBar(filteredConfig, config.settings));
            }
            if(config && Object.keys(config?.gdpr).length){
                const filteredConfig = {};
                Object.keys(config.gdpr).forEach((key) => {
                    const gdpr = config.gdpr[key];
                    let settings = {...gdpr['post'], previewType};
                    if(settings._global_queue){
                        settings = {...settings, ...config.settings};
                    }
                    if(!(previewType === 'phone' && settings.hide_on_mobile)){
                        gdpr['post']    = settings;
                        filteredConfig[key] = gdpr;
                    }
                });
                setGdprNotices(normalizePressBar(filteredConfig, config.settings));
            }
        }
    }, [previewType])

    const getDeviceType = () => {
        const width = window?.innerWidth;
    
        if (width >= 1024) {
            return "desktop";
        } else if (width >= 768 && width < 1024) {
            return "tablet";
        } else {
            return "mobile";
        }
    }

    useEffect(() => {
        isMounted.current = true;
        // console.log("props frontend", props);
        // Fetch Notices

        if(props.config.nxPreview){
            return;
        }


        let query:{[key: string]:string} = {};
        if(props.config.rest?.lang){
            query.lang = props.config.rest.lang;
        }
        let url = nxHelper.getPath(props.config.rest, `notice/`, query);
        const extras = props.config?.extra || [];
        const deviceType = getDeviceType();
        const data = {
            all_active: props.config?.all_active || false,
            global    : props.config?.global || [],
            active    : props.config?.active || [],
            pressbar  : props.config?.pressbar || [],
            shortcode : props.config?.shortcode || [],
            gdpr      : props.config?.gdpr || [],
            deviceType: deviceType,
            extra     : { ...extras,'url': location.pathname, 'page_title': document.title },
        };

        const args: {[key: string]: any} = {};
        if(!props.config.rest.omit_credentials){
            args.credentials = 'same-origin';
        }

        nxHelper
        .post(url, data, args)
        .then(response => normalizeResponse(response))
        .then((response: any) => {
            // Add Active Notices into State
            if (isMounted.current) {
                setActiveNotices(response?.activeNotice);

                let gNotices = response?.globalNotice || [];
                if(response.settings?.random){
                    shuffleArray(gNotices);
                }
                else{
                    sortArray(gNotices, {
                        by: 'timestamp',
                        order: 'desc',
                        computed: {
                            timestamp: row => row.data?.timestamp ? row.data.timestamp : getTime(row.data?.updated_at)
                        }
                    });
                }                                
                setGlobalNotices(gNotices);
                setShortcodeNotices(response?.shortcodeNotice);
                setPressbarNotices(response?.pressbar);
                setGdprNotices(response?.gdpr);
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
        let timeoutIDs = {};
        let intervalIDs = {};
        if (activeNotices != null && activeNotices.length > 0) {
            activeNotices.forEach((entries) => {
                if (entries?.length > 0) {
                    let id = 0;
                    const config = entries[id].props;
                    if(!config?.random_order && config.source !== 'custom_notification' && config.source !== 'custom_notification_conversions'){
                        sortArray(entries, {
                            by: 'timestamp',
                            order: 'desc',
                            computed: {
                                timestamp: row => row.data?.timestamp ? row.data.timestamp : getTime(row.data?.updated_at)
                            }
                        });
                    }
                    const delayBefore = (config?.delay_before || 5) * 1000;
                    const delayBetween = (config?.delay_between || 5) * 1000;
                    const displayFor = (config?.display_for || 5) * 1000;

                    let args = {
                        id,
                        count: entries?.length || 0,
                        loop: config?.loop || false,
                        intervalID: null,
                        timeoutID: null,
                        data: null,
                        config
                    }

                    timeoutIDs[id] = setTimeout(() => {
                        args.timeoutID = timeoutIDs[id];
                        args.data = entries[id].data;
                        dispatchNotification( args );
                        // notice
                        intervalIDs[id] = setInterval(() => {
                            if ( id === entries?.length - 1 ) {
                                if (config.loop) {
                                    id = 0;
                                } else {
                                    clearInterval(intervalIDs[id]);
                                    clearTimeout(timeoutIDs[id]);
                                    return;
                                }
                            } else {
                                id++;
                            }
                            args.intervalID = intervalIDs[id];
                            args.data = entries[id].data;
                            dispatchNotification( args );
                        }, displayFor + delayBetween );
                    }, delayBefore );
                }
            });
        }
        return () => {
            // console.log(activeNotices, timeoutIDs, intervalIDs);

            if(Object.keys(timeoutIDs)?.length){
                Object.keys(timeoutIDs).forEach(id => {
                    clearTimeout(timeoutIDs?.[id]);
                });
            }
            if(Object.keys(intervalIDs)?.length){
                Object.keys(intervalIDs).forEach(id => {
                    clearInterval(intervalIDs?.[id]);
                });
            }

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
                            return;
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
        if (pressbarNotices != null && pressbarNotices.length > 0) {
            const triggeredNotices = new Set();

            const checkAndShowNotice = (nxBar) => {
                const config = nxBar.post;
                const uniqueKey = config?.nx_id;

                if (triggeredNotices.has(uniqueKey)) return;

                const mode = config?.scroll_offset?.scroll_trigger_mode || null;
                const value = parseFloat(config?.scroll_offset?.scroll_trigger_value || "300");
                const appearCondition = config?.appear_condition || "after_few_seconds"; // default: show immediately

                const scrollTop = window.scrollY || window.pageYOffset;
                const windowHeight = window.innerHeight;
                const fullHeight = document.documentElement.scrollHeight;

                let show = false;

                if (appearCondition === 'on_scroll') {
                    // Only show if scroll condition is satisfied
                    if (mode === "px" && scrollTop >= value) {
                        show = true;
                    } else if (mode === "percent") {
                        const percentScrolled = (scrollTop / (fullHeight - windowHeight)) * 100;
                        if (percentScrolled >= value) show = true;
                    } else if (mode === "vh") {
                        const vhValue = (windowHeight * value) / 100;
                        if (scrollTop >= vhValue) show = true;
                    }
                } else {
                    // Show immediately regardless of scroll
                    show = true;
                }

                if (show) {
                    triggeredNotices.add(uniqueKey);

                    const initialDelay = (+config?.initial_delay || 0) * 1000;
                    const hideAfter = (+config?.hide_after || 5) * 1000;

                    const args = {
                        intervalID: null,
                        timeoutID: null,
                        data: nxBar.content,
                        config,
                    };

                    const timeoutID = setTimeout(() => {
                        args.timeoutID = timeoutID;
                        const ID = dispatchNotification(args);

                        if (config?.auto_hide && +config?.hide_after) {
                            if (config?.close_forever) {
                                const expires = new Date();
                                expires.setDate(expires.getDate() + (config?.time_reset ? 1 : 365));
                                const countRand = config?.countdown_rand ? `-${config.countdown_rand}` : '';
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
                                document.body.style.paddingTop = `0px`;
                            }, hideAfter);
                        }
                    }, initialDelay);
                }
            };

            // On load: only show if appear_condition !== 'on_scroll'
            pressbarNotices.forEach((nxBar) => {
                if (nxBar.post?.appear_condition !== 'on_scroll') {
                    checkAndShowNotice(nxBar);
                }
            });

            // Always setup scroll handler (for scroll-triggered ones)
            const handleScroll = () => {
                pressbarNotices.forEach((nxBar) => {
                    if (nxBar.post?.appear_condition === 'on_scroll') {
                        checkAndShowNotice(nxBar);
                    }
                });
            };

            window.addEventListener("scroll", handleScroll);

            return () => {
                window.removeEventListener("scroll", handleScroll);
            };
        }
    }, [pressbarNotices]);


    /**
     * GDPR
     */
    useEffect(() => {
        // Process to render;
        if (gdprNotices != null && gdprNotices.length > 0) {
            gdprNotices.forEach((gdprItem) => {
                const config = gdprItem.post;
                const initialDelay = (+config?.cookie_visibility_delay_before || 5) * 1000;
                const hideAfter = (+config?.hide_after || 5) * 1000;

                let args = {
                    intervalID: null,
                    timeoutID: null,
                    data: null,
                    config
                }

                const timeoutID = setTimeout(() => {
                    args.timeoutID = timeoutID;
                    args.data = gdprItem.content;
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
                            document.body.style.paddingTop = `0px`;
                        }, hideAfter);
                    }
                }, initialDelay);
            });
            
        }
    }, [gdprNotices]);

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
                    const delayBefore  = (config?.delay_before || 5) * 1000;
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
        // Define a fixed order of positions
        let fixedOrder = ['top','bottom', 'bottom_left', 'bottom_right', 'top_left', 'top_right','center','cookie_notice_bottom_left','cookie_notice_bottom_right', 'cookie_notice_center','cookie_banner_top', 'cookie_banner_bottom'];
        for (let i = 0; i < state.notices.length; i++) {
            const notice = state.notices[i];
            let get_position;
            if( notice.config.type == 'gdpr' ) {
                let { gdpr_position } = notice.config;
                get_position = gdpr_position;
            }else{
                let { position } = notice.config;
                get_position = position;
            }
            if (get_position.startsWith('notificationx-shortcode-')) {
                fixedOrder.push(get_position);
            }
            noticeToRender[get_position] || (noticeToRender[get_position] = []);
            noticeToRender[get_position]!.push(notice);
        }
        
        return fixedOrder.map((p) => 
            noticeToRender[p] ? callback(p, noticeToRender[p]!) : null
        ).filter(Boolean);
    };

    return {
        ...props.config,
        isMounted,
        state,
        dispatch,
        getNxToRender,
        getTime,
        assets: { free: props.config.assets, pro: props.config?.pro_assets, common : props.config?.common_assets },
        rest: props.config.rest,
    };
};

export default useNotificationX;
