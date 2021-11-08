import React from 'react'
import { assetsURL } from '../core/functions';

const Loader = () => {
    return (
        <div className="nx-preloader">
            <img src={assetsURL('images/logos/logo-preloader.gif')} />
        </div>
    )
}

export default Loader