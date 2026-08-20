import React, { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { withLabel } from 'quickbuilder';
import nxHelper from '../core/functions';
import nxToast from '../core/ToasterMsg';

type ConnectionStatus = {
    connected: boolean;
    site_id_masked: string;
    tier: string;
    license_status?: string;
    is_pro_plugin?: boolean;
    connected_at: string;
    home_url: string;
    admin_email: string;
};

/**
 * Facebook Reviews → NotificationX API connection.
 *
 * Wrapped in quickbuilder's withLabel so it renders as a regular settings row
 * (label column + field column) like every other control in the section. The
 * initial `status` comes from PHP; Connect/Disconnect return a fresh one, so
 * the row updates in place without reloading the page.
 */
const FacebookReviewsConnection = (props) => {
    const [status, setStatus] = useState<ConnectionStatus>(props?.status || {});
    const [busy, setBusy] = useState<'' | 'connect' | 'disconnect'>('');

    const source = props?.source || 'facebook_reviews';
    const isConnected = !!status?.connected;
    const tier = (status?.tier || 'free').toUpperCase();
    // Pro plugin connected as FREE: the proxy could not verify the licence.
    const licenseWarning =
        isConnected && status?.is_pro_plugin && status?.tier !== 'pro'
            ? status?.license_status === 'missing'
                ? __('No licence key found. Activate your NotificationX Pro licence, then reconnect to unlock Pro limits.', 'notificationx')
                : status?.license_status === 'unverified'
                    ? __('Your licence could not be verified right now. Pro limits apply once it is — reconnect later.', 'notificationx')
                    : sprintf(/* translators: %s: licence status reported by the licence server */ __('Your NotificationX Pro licence is not valid (%s). Renew it, then reconnect to unlock Pro limits.', 'notificationx'), status?.license_status || 'invalid')
            : '';

    const run = async (action: 'connect' | 'disconnect') => {
        if (busy) return;
        setBusy(action);
        try {
            const res: any = await nxHelper.post('api-connect', { source, action }, { get_error: true });
            if (res?.status === 'success' && res?.connection) {
                setStatus(res.connection);
                nxToast.connected(res?.message || __('Done.', 'notificationx'));
            } else {
                nxToast.error(res?.message || __('Something went wrong. Please try again.', 'notificationx'));
            }
        } catch (err: any) {
            nxToast.error(err?.message || __('Something went wrong. Please try again.', 'notificationx'));
        } finally {
            setBusy('');
        }
    };

    return (
        <div className={`nx-fbr-connection ${isConnected ? 'is-connected' : 'is-disconnected'}`}>
            <div className="nx-fbr-connection__status">
                <span className={`nx-fbr-connection__badge ${isConnected ? 'is-on' : 'is-off'}`}>
                    <i className="nx-fbr-connection__dot" />
                    {isConnected
                        ? sprintf(/* translators: %s: plan name (FREE/PRO) */ __('Connected · %s', 'notificationx'), tier)
                        : __('Not connected', 'notificationx')}
                </span>
                <span className="nx-fbr-connection__text">
                    {isConnected
                        ? __('Your site is connected and ready to collect Facebook recommendations. This connection is locked to this site only.', 'notificationx')
                        : __('Connect this site to collect Facebook Page recommendations — no API token or third-party account needed.', 'notificationx')}
                </span>
            </div>

            <div className="nx-fbr-connection__meta">
                {isConnected ? (
                    <>
                        <span className="nx-fbr-connection__label">{__('Site ID', 'notificationx')}</span>
                        <code className="nx-fbr-connection__code">{status?.site_id_masked}</code>
                        {status?.connected_at && (
                            <span className="nx-fbr-connection__text">
                                {sprintf(/* translators: %s: date */ __('Connected on %s', 'notificationx'), status.connected_at)}
                            </span>
                        )}
                        {licenseWarning && (
                            <span className="nx-fbr-connection__text nx-fbr-connection__warning">{licenseWarning}</span>
                        )}
                    </>
                ) : (
                    <>
                        <span className="nx-fbr-connection__label">{__('Will be sent', 'notificationx')}</span>
                        <code className="nx-fbr-connection__code">{status?.home_url}</code>
                        <code className="nx-fbr-connection__code">{status?.admin_email}</code>
                        <span className="nx-fbr-connection__text">{status?.is_pro_plugin ? __('+ an anonymous install fingerprint and your Pro licence key (to verify your plan)', 'notificationx') : __('+ an anonymous install fingerprint', 'notificationx')}</span>
                    </>
                )}
            </div>

            <div className="nx-fbr-connection__actions">
                {isConnected ? (
                    <button
                        type="button"
                        className="wprf-control wprf-button wprf-btn nx-fbr-connection__btn is-secondary"
                        disabled={!!busy}
                        onClick={() => run('disconnect')}
                    >
                        {busy === 'disconnect' ? __('Disconnecting...', 'notificationx') : __('Disconnect', 'notificationx')}
                    </button>
                ) : (
                    <button
                        type="button"
                        className="wprf-control wprf-button wprf-btn nx-fbr-connection__btn"
                        disabled={!!busy}
                        onClick={() => run('connect')}
                    >
                        {busy === 'connect' ? __('Connecting...', 'notificationx') : __('Connect', 'notificationx')}
                    </button>
                )}
            </div>
        </div>
    );
};

export default withLabel(FacebookReviewsConnection);
