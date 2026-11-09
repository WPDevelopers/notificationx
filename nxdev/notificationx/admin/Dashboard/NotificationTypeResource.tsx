import { __, sprintf } from '@wordpress/i18n';
import React from 'react'
import { getAlert, proAlert } from '../../core/functions';
import { Link } from 'react-router-dom';
import { DOCS, NotificationType, proFeaturePopupConfigCrossDomain } from '../../core/constants';
import NxIcon from '../../components/NxIcon';

const PRO_TYPES = ['inline', 'flashing_tab', 'cross-domain'];

// Icon and accent per notification type, in place of remote preview GIFs.
const TYPE_STYLE: Record<string, { icon: string; tone: string }> = {
    'conversions'     : { icon: 'bag', tone: 'violet' },
    'gdpr'            : { icon: 'cookie', tone: 'amber' },
    'notification_bar': { icon: 'megaphone', tone: 'blue' },
    'inline'          : { icon: 'trending', tone: 'green' },
    'flashing_tab'    : { icon: 'browserTab', tone: 'pink' },
    'cross-domain'    : { icon: 'globe', tone: 'teal' },
};

const NotificationTypeResource = ({ props, context }) => {
    const isPro = !!context?.is_pro_active;
    const adminAssets = context?.assets?.admin || '';

    const handleSalesRedirection = ( type, source ) => {
        if( PRO_TYPES.includes( type ) && !isPro ) {
            const popup = getAlert( type , context );
            proAlert(popup).fire();
        }else{
            context.setRedirect({
                page: `nx-edit`,
                state: { type: type, source: source, timestamp: new Date().getTime() }
            });
        }
    }

    const handleCrossDomain = () => {
        if( !isPro ) {
            proAlert(proFeaturePopupConfigCrossDomain).fire();
        }else{
            context.setRedirect({
                page: `nx-settings`,
                tab: 'tab-miscellaneous-settings',
                keepHash: true,
            });
        }
    }

    const stories = [
        {
            title   : __('How Emilio Johann Got 1.4M+ Views with NotificationX Sales Alert', 'notificationx'),
            role    : __('eCom Founder Web & Ops AI Consultant', 'notificationx'),
            name    : __('Emilio Johann', 'notificationx'),
            meta    : __('San Diego, CA', 'notificationx'),
            avatar  : `${adminAssets}/images/new-img/author-1.png`,
        },
        {
            title   : __('Converting Prospects to Customers: Barn2\'s Success Story with NotificationX', 'notificationx'),
            role    : __('WordPress Plugin Developer Company', 'notificationx'),
            name    : __('Katie Keith', 'notificationx'),
            meta    : __('Co-Founder & CEO at Barn2', 'notificationx'),
            avatar  : `${adminAssets}/images/new-img/author-2.png`,
        },
    ];

    return (
        <>
            <section className='nx-card nx-dash-types'>
                <header className='nx-card__header'>
                    <div>
                        <h2 className="nx-card__title">{ __('Exclusive Features & Notification Types', 'notificationx') }</h2>
                        <p className="nx-card__subtitle">{ __('Get various types of notification support including', 'notificationx') }</p>
                    </div>
                    { !context?.createRedirect &&
                        <Link className="nx-button nx-button--ghost" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>
                            <NxIcon name="plus" size={15} strokeWidth={2.25} />
                            { __('Add New', 'notificationx') }
                        </Link>
                    }
                </header>
                <div className='nx-dash-types__grid'>
                    { NotificationType.map( (item) => {
                        const locked = !isPro && PRO_TYPES.includes( item?.type );
                        const style = TYPE_STYLE[ item?.type ] || { icon: 'bell', tone: 'violet' };
                        const onClick = item?.type == 'cross-domain'
                            ? () => handleCrossDomain()
                            : () => handleSalesRedirection( item?.type, item?.source );
                        const label = item?.type == 'cross-domain'
                            ? ( !isPro ? __('Upgrade To Pro', 'notificationx') : item?.button_text )
                            : ( locked ? __('Upgrade To Pro', 'notificationx') : item?.button_text );
                        return (
                            <article className={`nx-dash-type tone-${style.tone}`} key={ item?.type }>
                                <div className="nx-dash-type__media">
                                    <img src={ `${adminAssets}/images/new-img/types/${item?.img}` } alt="" loading="lazy" />
                                    { locked && <span className="nx-badge nx-badge--pro">{ __('PRO', 'notificationx') }</span> }
                                </div>
                                <div className="nx-dash-type__body">
                                    <div className="nx-dash-type__heading">
                                        <span className={`nx-dash-type__icon tone-${style.tone}`}>
                                            <NxIcon name={ style.icon } size={16} />
                                        </span>
                                        <h3 className="nx-dash-type__title">{ item?.title }</h3>
                                    </div>
                                    <p className="nx-dash-type__desc">{ item?.desc }</p>
                                    <button
                                        type="button"
                                        className={`nx-dash-type__action${locked ? ' upgrade-pro' : ''}`}
                                        onClick={ onClick }
                                    >
                                        { locked && <NxIcon name="crown" size={14} /> }
                                        { label }
                                        { !locked && <NxIcon name="arrowRight" size={14} /> }
                                    </button>
                                </div>
                            </article>
                        );
                    } ) }
                </div>
            </section>

            <div className='nx-dash-grid nx-dash-grid--half'>
                <section className='nx-card nx-dash-resources'>
                    <header className='nx-card__header'>
                        <div>
                            <h2 className="nx-card__title">{ __('Helpful Resources', 'notificationx') }</h2>
                            <p className="nx-card__subtitle">{ __('Step-by-step guides for popular setups.', 'notificationx') }</p>
                        </div>
                        <a className='nx-button nx-button--ghost' href={ sprintf('%s', 'https://notificationx.com/docs/') } target='_blank' rel="noopener noreferrer">
                            { __('Explore More', 'notificationx') }
                            <NxIcon name="external" size={14} />
                        </a>
                    </header>
                    <ul className='nx-dash-resources__list'>
                        { DOCS.map( (item) => (
                            <li key={ item?.url }>
                                <a href={item?.url} target='_blank' rel="noopener noreferrer">
                                    <span className="nx-dash-resources__icon"><NxIcon name="file" size={16} /></span>
                                    <span className="nx-dash-resources__text">{ item?.desc }</span>
                                    <NxIcon name="arrowRight" size={15} className="nx-dash-resources__arrow" />
                                </a>
                            </li>
                        ) ) }
                    </ul>
                </section>

                <section className='nx-card nx-dash-stories'>
                    <header className='nx-card__header'>
                        <div>
                            <h2 className="nx-card__title">{ __('Customer Success Stories', 'notificationx') }</h2>
                            <p className="nx-card__subtitle">{ __('See how others grow with NotificationX.', 'notificationx') }</p>
                        </div>
                        <a target='_blank' rel="noopener noreferrer" className='nx-button nx-button--ghost' href={ `https://notificationx.com/case-study/` }>
                            { __('Learn More', 'notificationx') }
                            <NxIcon name="external" size={14} />
                        </a>
                    </header>
                    <div className='nx-dash-stories__list'>
                        { stories.map( (story) => (
                            <a className='nx-dash-story' key={ story.name } href="https://notificationx.com/case-study/" target="_blank" rel="noopener noreferrer">
                                <span className="nx-dash-story__stars" aria-hidden="true">
                                    { [0, 1, 2, 3, 4].map( (i) => <NxIcon key={i} name="star" size={14} /> ) }
                                </span>
                                <h3 className="nx-dash-story__title">{ story.title }</h3>
                                <p className="nx-dash-story__role">{ story.role }</p>
                                <div className='nx-dash-story__author'>
                                    <img src={ story.avatar } alt="" />
                                    <div>
                                        <strong>{ story.name }</strong>
                                        <span>{ story.meta }</span>
                                    </div>
                                </div>
                            </a>
                        ) ) }
                    </div>
                </section>
            </div>
        </>
    )
}

export default NotificationTypeResource
