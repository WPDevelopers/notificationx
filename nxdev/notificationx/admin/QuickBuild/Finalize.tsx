import { useBuilderContext } from '../../../form-builder';
import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom';
import { sprintf, __ } from '@wordpress/i18n';
import { renderToString } from '@wordpress/element';
import parse from 'html-react-parser';

function Finalize(props) {
    const builderContext = useBuilderContext();
    const { title } = builderContext;

    return (
        <div className="nx-quick-builder-message">
            {parse(sprintf(
                // translators: %1$s: title, %2$s: link to the All NotificationX page.
                __(`You are about to publish %1$s. You can rename this and edit everything whenever you want from %2$s Page.`, 'notificationx'),
                renderToString(<strong>{title}</strong>),
                renderToString(<Link to={{
                    pathname: '/admin.php',
                    search  : `?page=nx-admin`,
                }}>{__("NotificationX", 'notificationx')}</Link>)
            ))}
        </div>
    )
}

export default Finalize
