import { createContext, useContext } from "react";
export const NotificationContext = createContext(undefined as any);
export const NotificationProvider = NotificationContext.Provider;
export const NotificationConsumer = NotificationContext.Consumer;
const useNotificationContext = () => {
    return useContext(NotificationContext);
};
export default useNotificationContext;