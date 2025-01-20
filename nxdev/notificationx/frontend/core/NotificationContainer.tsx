import React, { useEffect, useState } from "react";
import { useNotificationContext, Notification, Shortcode, Pressbar } from ".";
import GDPR from "./GDPR";
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
        const isMobileAndPro = isMobile && frontendContext?.is_pro;

        return (
            <div className={`nx-container nxc-${position}`} key={`container-${position}`}>
                {NoticeList.map((notice) => {
                    if (isMobileAndPro && notice?.config?.is_mobile_responsive && notice?.config?.type !== 'gdpr') {
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
                        return (
                            <Pressbar
                                key={`pressbar-${nxBar?.config?.nx_id}`}
                                position={position}
                                nxBar={nxBar}
                                dispatch={frontendContext.dispatch} />
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
