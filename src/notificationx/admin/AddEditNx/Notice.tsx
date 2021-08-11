import React from 'react'

const Notice = ({ message }) => {
    return (
        <div className="nx-admin-notice success-notice"><p>{message}</p></div>
    )
}

export default Notice;
