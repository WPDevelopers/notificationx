import React from 'react'
import usePortal from '../hooks/usePortal';
import classNames from 'classnames';
import { isAdminBar } from './utils';
import { createPortal } from 'react-dom';
import GdprActions from '../gdpr/utils/GdprActions';
import GdprFooter from '../gdpr/utils/GdprFooter';
import CloseIcon from '../../icons/Close';

const GDPR = ({ position, gdpr, dispatch }) => {
    const target = usePortal(`nx-gdpr-${position}`, position == 'bottom_left', true);
    const { config: settings, data: content } = gdpr;
    
    const wrapper = (
        // @todo advanced style.
        <div
            id={`nx-gdpr-${settings.nx_id}`}
            className={classNames(
                `nx-gdpr`,
                settings.themes,
                settings?.gdpr_theme,
                `nx-gdpr-${settings.nx_id}`,

                {
                    "nx-position-top": "top" == settings?.position,
                    "nx-position-bottom":
                        "bottom" == settings?.position,
                    [`nx-close-${settings?.bar_close_position}`]: settings?.bar_close_position,
                    "nx-admin": isAdminBar(),
                    "nx-sticky-bar": settings?.sticky_bar,
                    "nx-gdpr-has-elementor": settings?.elementor_id,
                    "nx-gdpr-has-gutenberg": settings?.gutenberg_id,
                }
            )}
        >
            <div className="nx-gdpr">
                <div className="nx-gdpr-card">
                    {/* Header Section */}
                    <div className="nx-gdpr-card-header">
                        { settings?.gdpr_custom_logo &&
                            <img src={settings?.gdpr_custom_logo?.url} alt={settings?.gdpr_custom_logo?.title} className="nx-gdpr-logo" />
                        }
                        <h3 className="nx-gdpr-title">{settings?.gdpr_title}</h3>
                    </div>

                    {/* Content Section */}
                    <div className="nx-gdpr-card-body">
                        <p className="nx-gdpr-description">
                            {settings?.gdpr_message}
                            { settings?.gdpr_cookies_policy_toggle &&
                                <a href={settings?.gdpr_cookies_policy_link_url} target='_blank' className="nx-gdpr-link">{ settings?.gdpr_cookies_policy_link_text }</a>
                            }
                        </p>
                        <GdprActions settings={settings}/>
                    </div>
                   <GdprFooter settings={settings} />

                    {/* Close Icon */}
                    <button type="button" className="nx-gdpr-close" aria-label="Close">
                        <CloseIcon/>
                    </button>
                </div>
            </div>
        </div>
    );
    return createPortal(wrapper, target);
}

export default GDPR