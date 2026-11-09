import React from "react";
import { __ } from "@wordpress/i18n";
import { Link } from "react-router-dom";
import AnalyticsForDashboard from "../Analytics/AnalyticsForDashboard";
import NxIcon from "../../components/NxIcon";

// Bundled logos (assets/admin/images/extensions/sources/). `padded` logos
// carry wide whitespace in the file, so they render larger.
const INTEGRATIONS = [
    { file: 'woocommerce.png', name: 'WooCommerce' },
    { file: 'edd.png', name: 'Easy Digital Downloads' },
    { file: 'surecart.png', name: 'SureCart' },
    { file: 'fluentcart.png', name: 'FluentCart' },
    { file: 'freemius.png', name: 'Freemius' },
    { file: 'envato.png', name: 'Envato' },
    { file: 'mailchimp.png', padded: true, name: 'Mailchimp' },
    { file: 'convertkit.png', padded: true, name: 'ConvertKit' },
    { file: 'ActiveCampaign.png', padded: true, name: 'ActiveCampaign' },
    { file: 'zapier.png', padded: true, name: 'Zapier' },
    { file: 'google-rating.png', padded: true, name: 'Google Reviews' },
    { file: 'youtube.png', name: 'YouTube' },
    { file: 'learndash.png', padded: true, name: 'LearnDash' },
    { file: 'tutor.png', name: 'Tutor LMS' },
    { file: 'give.png', name: 'GiveWP' },
];

const AnalyticsDashboard = ({ props, context }) => {
    const adminAssets = context?.assets?.admin || '';
    // Users who cannot see analytics (or with analytics turned off) get the
    // integrations card alone instead of being redirected away.
    const showAnalytics = !context?.analyticsRedirect;

    return (
        <div className={`nx-dash-grid ${showAnalytics ? 'nx-dash-grid--main' : ''}`}>
            { showAnalytics &&
                <section className='nx-card nx-dash-performance'>
                    <header className='nx-card__header'>
                        <div>
                            <h2 className="nx-card__title">{ __('Performance', 'notificationx') }</h2>
                            <p className="nx-card__subtitle">{ __('Views, clicks and click-through rate over time.', 'notificationx') }</p>
                        </div>
                        <Link className="nx-button nx-button--ghost" to={ { pathname: "/admin.php", search: `?page=nx-analytics`} }>
                            { __('Analytics', 'notificationx') }
                            <NxIcon name="arrowRight" size={15} />
                        </Link>
                    </header>
                    <div className='nx-dash-performance__body'>
                        <AnalyticsForDashboard isDashboard={true} />
                    </div>
                </section>
            }
            <section className='nx-card nx-dash-integrations'>
                <header className='nx-card__header'>
                    <div>
                        <h2 className="nx-card__title">{ __('Integrations', 'notificationx') }</h2>
                        <p className="nx-card__subtitle">{ __('Connect the tools you already use.', 'notificationx') }</p>
                    </div>
                </header>
                <ul className='nx-dash-integrations__grid'>
                    { INTEGRATIONS.map((item) => (
                        <li key={item.file} title={item.name} className={item.padded ? 'is-padded' : undefined}>
                            <img src={ `${adminAssets}/images/extensions/sources/${item.file}` } alt={item.name} loading="lazy" />
                        </li>
                    )) }
                </ul>
                <Link className="nx-button nx-button--secondary nx-button--block" to={ { pathname: "/admin.php", search: `?page=nx-settings`} }>
                    <NxIcon name="plug" size={16} />
                    { __('View All Integrations', 'notificationx') }
                </Link>
            </section>
        </div>
    );
};
export default AnalyticsDashboard;
