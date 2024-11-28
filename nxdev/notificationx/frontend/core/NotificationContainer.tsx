import React from "react";
import { useNotificationContext, Notification, Shortcode, Pressbar } from ".";
import GDPR from "./GDPR";

const NotificationContainer = (props: any) => {
    const frontendContext = useNotificationContext();
    
    const renderNotice = (NoticeList, position) => {
        return (
            <div className={`nx-container nxc-${position}`} key={`container-${position}`}>
                {NoticeList.map((notice) => {
                    return (
                        <Notification
                            assets={frontendContext.assets}
                            dispatch={frontendContext.dispatch}
                            key={notice.id}
                            {...notice}
                        />
                    );
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
                if (NoticeList?.[0]?.config?.type == 'gdpr' && (position == 'bottom_right' || position == 'bottom_left' || position == 'center')) {
                    return NoticeList.map((gdprItem) => {                        
                        return (
                            <GDPR
                                key={`pressbar-${gdprItem?.config?.nx_id}`}
                                position={position}
                                gdpr={gdprItem}
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
