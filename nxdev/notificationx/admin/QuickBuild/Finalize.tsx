import { useBuilderContext } from '../../../form-builder';
import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom';

function Finalize(props) {
    const builderContext = useBuilderContext();
    const { title } = builderContext;

    return (
        <div className="nx-quick-builder-message">
            You are about to publish <strong>{title}</strong>. You can rename this and edit everything whenever you want from <Link to={{
                pathname: '/admin.php',
                search  : `?page=nx-admin`,
            }}>NotificationX</Link> Page.
        </div>
    )
}

export default Finalize
