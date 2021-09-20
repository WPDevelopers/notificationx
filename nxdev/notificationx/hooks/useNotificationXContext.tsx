import { __ } from '@wordpress/i18n';
import * as React from 'react';
import invariant from 'tiny-warning';

export const NotificationXContext = React.createContext(undefined as any);
NotificationXContext.displayName = process.env.NODE_ENV === 'production' ? 'Anonymous' : 'NotificationXContext';

export const NotificationXProvider = NotificationXContext.Provider;
export const NotificationXConsumer = NotificationXContext.Consumer;

export default function useNotificationXContext() {
    const notificationXContext = React.useContext(NotificationXContext);
    invariant(
        !!notificationXContext,
        __(`NotificationXContext context is undefined, please verify you are calling useNotificationXContext() as child of a <NotificationX> component.`, 'notificationx')
    );
    return notificationXContext;
}
