import React from 'react'
import { assetsURL } from '../core/functions';

const Loader = () => {
    const preloader = assetsURL('images/logos/logo-preloader.gif');
    return (
        <div className="nx-preloader">
            <img src={preloader} />
        </div>
    )
}

export default Loader
