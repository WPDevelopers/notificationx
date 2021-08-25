import React from 'react'
import Loader from './Loader';

const WrapperWithLoader: React.FC<{ isLoading?: boolean, classes?: string, div?: boolean }> = ({ children, isLoading = true, classes = "nx-admin-wrapper", div = true }) => {

    if (div) {
        return (
            <div className={classes}>
                {isLoading && <Loader />}
                {!isLoading && children}
            </div>
        )
    }

    return (
        <>
            {isLoading && <Loader />}
            {!isLoading && children}
        </>
    )
}

export default WrapperWithLoader;