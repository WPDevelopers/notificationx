import React from 'react'
import PreLoader from '../../../assets/admin/images/logos/logo-preloader.gif';

const Loader = () => {
    return (
        <div className="nx-preloader">
            <img src={PreLoader} />
        </div>
    )
}

export default Loader