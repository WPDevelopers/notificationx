import React, { useEffect, useState } from "react";
import { useNotificationContext, Notification, Shortcode, Pressbar } from ".";
import GDPR from "./GDPR";
import Popup from "./Popup";
import NotificationForMobile from "./NotificationForMobile";
const NotificationContainer = (props: any) => {
    const frontendContext = useNotificationContext();
    const [isMobile, setIsMobile] = useState(false);

    // Detect screen size
    useEffect(() => {
        const handleResize = () => {
            setIsMobile(window.innerWidth <= 574);
        };
        handleResize();
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);

    const renderNotice = (NoticeList, position) => {
        // Check using custom animation
        const hasCustomAnimation = NoticeList.some(item =>  item.config.animation_notification_hide !== 'default' ||  item.config.animation_notification_show !== 'default' );
        let isAnimateImport = false;
        if (hasCustomAnimation && !isAnimateImport) {
            // @ts-ignore 
            import("animate.css/animate.min.css")
            isAnimateImport = true;
        }
        
        const isMobileAndPro = isMobile && frontendContext?.is_pro;
        const noMobileDesign = ['announcements', 'custom_notification', 'inline','gdpr_notification'];
        
        return (
            <div className={`nx-container nxc-${position}`} key={`container-${position}`}>
                {NoticeList.map((notice) => {
                    if (isMobileAndPro && notice?.config?.is_mobile_responsive && !noMobileDesign?.includes(notice?.config?.source) ) {
                        return (
                            <NotificationForMobile
                                assets={frontendContext.assets}
                                dispatch={frontendContext.dispatch}
                                key={notice.id}
                                {...notice}
                            />
                        );
                    } else {
                        if (
                            notice?.config?.type == 'gdpr' &&
                            (position == 'cookie_notice_bottom_left' ||
                            position == 'cookie_notice_bottom_right' ||
                            position == 'cookie_notice_center' ||
                            position == 'cookie_banner_bottom' ||
                            position == 'cookie_banner_top' )
                        ) {
                            const gdprItem = notice;
                            return (
                                <GDPR
                                    key={`pressbar-${gdprItem?.config?.nx_id}`}
                                    position={position}
                                    gdpr={gdprItem}
                                    dispatch={frontendContext.dispatch} />
                            );

                        }

                        if (notice?.config?.type == 'popup') {
                            const popupItem = notice;
                            return (
                                <Popup
                                    key={`popup-${popupItem?.config?.nx_id}`}
                                    position={position}
                                    nxPopup={popupItem}
                                    dispatch={frontendContext.dispatch} />
                            );
                        }

                        return (
                            <Notification
                                assets={frontendContext.assets}
                                dispatch={frontendContext.dispatch}
                                key={notice.id}
                                {...notice}
                            />
                        );
                    }
                })}
            </div>
        );
    };    
    return (
        <>
            {frontendContext.getNxToRender((position, NoticeList) => {                
                if (NoticeList?.[0]?.config?.type == 'notification_bar' && (position == 'top' || position == 'bottom')) {
                    return NoticeList.map((nxBar) => {
                        const nxId = nxBar?.config?.nx_id;
                        const reappearance = nxBar?.config?.bar_reappearance;
                        const countRand = nxBar?.config?.countdown_rand ? `-${nxBar.config.countdown_rand}` : '';
                        const storageKey = `notificationx_${nxId}${countRand}`;
                        if (
                            (reappearance === 'dont_show_welcomebar' && localStorage.getItem(storageKey)) ||
                            (reappearance === 'show_welcomebar_next_visit' && sessionStorage.getItem(storageKey))
                        ) {
                            return null;
                        }

                        return (
                            <Pressbar
                                key={`pressbar-${nxId}`}
                                position={position}
                                nxBar={nxBar}
                                dispatch={frontendContext.dispatch}
                            />
                        );
                    });
                }                
                if (position.indexOf('notificationx-shortcode-') === 0) {
                    return (
                        <Shortcode key={`shortcode-${position}`} position={position}>
                            {renderNotice(NoticeList, position)}
                        </Shortcode>
                    );
                }
                return renderNotice(NoticeList, position);
            })}
        </>
    );
};

export default NotificationContainer;
