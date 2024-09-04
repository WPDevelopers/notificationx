import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react'
import { assetsURL, getAlert, proAlert } from '../../core/functions';
import { Link } from 'react-router-dom';
import { DOCS, NotificationType, proFeaturePopupConfigCrossDomain } from '../../core/constants';
const NotificationTypeResource = ({ props, context }) => {
    const handleSalesRedirection = ( type, source ) => {
        if( ['inline', 'flashing_tab', 'cross-domain'].includes( type ) && !context?.is_pro_active ) {
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
        if(  !context?.is_pro_active ) {               
            proAlert(proFeaturePopupConfigCrossDomain).fire();
        }else{
            context.setRedirect({
                page: `nx-settings`,
                tab: 'tab-miscellaneous-settings',
                keepHash: true,
            });
        }
    }    

    return (
        <div className='nx-other-details-wrapper'>
            <div className='nx-notification-type-wrapper nx-admin-content-wrapper'>
                <div className='nx-notification-type-header nx-content-details header'>
                    <div className='header-content-ex'>
                        <h4>{ __('Exclusive Features & Notification Types', 'notificationx') }</h4>
                        <p>{ __('Get various types of notification support including', 'notificationx') }</p>
                    </div>
                    <Link className="nx-secondary-btn" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>
                        { __('Add New', 'notificationx') }
                    </Link>
                </div>
                <div className='nx-notification-type-body'>
                    { NotificationType.map( (item, index) => (
                        <div className='nx-body-content-wrapper' key={index}>
                            <div className="type-image">
                                <img src={ item?.img } alt="icon" />
                            </div>
                            <div className='nx-body-content nx-content-details'>
                                <h5>{ item?.title }</h5>
                                <p>{ item?.desc }</p>
                                { item?.type == 'cross-domain' ? 
                                    <button className={`nx-secondary-btn ${!context?.is_pro_active && ['inline', 'flashing_tab', 'cross-domain'].includes( item?.type ) ? 'upgrade-pro' : ''}`} onClick={ () => handleCrossDomain() }>{ !context?.is_pro_active ? __('Upgrade To Pro') : item?.button_text }</button>
                                    : 
                                    <button className={`nx-secondary-btn ${!context?.is_pro_active && ['inline', 'flashing_tab', 'cross-domain'].includes( item?.type ) ? 'upgrade-pro' : ''}`} onClick={ () => handleSalesRedirection( item?.type, item?.source ) }>{ ['inline', 'flashing_tab', 'cross-domain'].includes( item?.type ) && !context?.is_pro_active ? __('Upgrade To Pro') : item?.button_text }</button>
                                }
                            </div>
                        </div>
                    ) ) }
                </div>
            </div>

            <div className='nx-resource-stories-wrapper'>
                <div className='nx-resource-wrapper nx-admin-content-wrapper'>
                    <div className='nx-resource-header nx-content-details header'>
                        <h4>{ __('Helpful Resources', 'notificationx') }</h4>
                        <a className='nx-secondary-btn' href={ sprintf('%s', 'https://notificationx.com/docs/') } target='_blank'>{ __('Explore More', 'notificationx') }</a>
                    </div>

                    <div className='nx-resource-body'>
                        { DOCS.map( (item) => (
                            <div className='nx-resource-content nx-content-details'>
                                <span>
                                    <div dangerouslySetInnerHTML={ { __html: item?.svg } }></div>
                                </span>
                                <a href={item?.url} target='_blank'>{ item?.desc }</a>
                            </div>
                        ) ) }
                    </div>
                </div>

                <div className='nx-stories-wrapper nx-admin-content-wrapper'>
                    <div className='nx-stories-header nx-content-details header'>
                        <h4>{ __('Customer Success Stories', 'notificationx') }</h4>
                        <a target='_blank' className='nx-secondary-btn' href={ `https://notificationx.com/case-study/` }>{ __('Learn More', 'notificationx') }</a>
                    </div>

                    <div className='nx-stories-body'>
                        <div className='nx-stories-content'>
                            <img className='stories-bg' src={`https://notificationx.com/wp-content/uploads/2024/09/stories-2.png`} alt="stories img" />
                            <div className='nx-content-details'>
                                <h5 dangerouslySetInnerHTML={{ __html: __('How Emilio Johann <br> Got 1.4M+ Views with NotificationX Sales Alert') }}></h5>
                                <p>{ __('eCommerce & Entrepreneurship Consultant', 'notificationx') }</p>
                                <div className='nx-author-details'>
                                    <img src={ assetsURL('/images/new-img/author-1.png') } alt={ __("author img",'notificationx') } />
                                    <div>
                                        <h6>{ __('Emilio Johann', 'notificationx') }</h6>
                                        <p>{ __('Florida, USA.', 'notificationx') }</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className='nx-stories-content'>
                            <img className='stories-bg' src={`https://notificationx.com/wp-content/uploads/2024/09/stories-2.png`} alt="stories img" />
                            <div className='nx-content-details'>
                                <h5>{ __('Converting Prospects to Customers: Barn2\'s Success Story with NotificationX', 'notificationx') }</h5>
                                <p>{ __('WordPress Plugin Developer Company', 'notificationx') }</p>
                                <div className='nx-author-details'>
                                    <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/author-2.png" alt="author img" />
                                    <div>
                                        <h6>{ __('Katie Keith', 'notificationx') }</h6>
                                        <p>{ __('Co-Founder & CEO at Barn2', 'notificationx') }</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default NotificationTypeResource