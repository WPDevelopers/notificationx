import { useBuilderContext } from 'quickbuilder';
import React from 'react'
import { Link } from 'react-router-dom';

function Finalize(props) {
    const builderContext = useBuilderContext();
    const { title } = builderContext;

    return (
        <div className="nx-quick-builder-message">
            You are about to publish <strong>{title}</strong>. You can rename this and edit everything whenever you want from <Link to="/">NotificationX</Link> Page.
        </div>
    )
}

export default Finalize
