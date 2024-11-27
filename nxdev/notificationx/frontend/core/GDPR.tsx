import React from 'react'
import usePortal from '../hooks/usePortal';
import classNames from 'classnames';
import { isAdminBar } from './utils';
import { createPortal } from 'react-dom';

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
            <div className="nx-gdpr-inner">
                <h3>Hello There!!</h3>
            </div>
        </div>
    );
    return createPortal(wrapper, target);
}

export default GDPR