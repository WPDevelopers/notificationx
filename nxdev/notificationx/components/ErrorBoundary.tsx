import { __ } from '@wordpress/i18n';
import React from 'react';

type ErrorBoundaryProps = {
    children?: React.ReactNode;
};

type ErrorBoundaryState = {
    hasError: boolean;
};

// Keeps a render error inside the wrapped panel. Without a boundary React
// unmounts the whole admin app, so one broken add-on UI (for example Pro's
// filter output on an older WordPress core React) blanks the entire builder.
class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
    state: ErrorBoundaryState = { hasError: false };

    static getDerivedStateFromError(): ErrorBoundaryState {
        return { hasError: true };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo) {
        console.error('NotificationX:', error, info?.componentStack);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="nx-error-boundary notice notice-error inline">
                    <p>{__('This section could not be loaded. Please reload the page, and update NotificationX and NotificationX Pro if the problem continues.', 'notificationx')}</p>
                </div>
            );
        }
        // null, not undefined: React 17 (core React before WP 6.2) throws on undefined.
        return this.props.children ?? null;
    }
}

export default ErrorBoundary;
