import React from 'react'

const Notice = ({ message, type = 'success' }: { message: string, type?: 'success' | 'error' }) => {
    return (
        <div className={`nx-admin-notice ${type}-notice`} role={type === 'error' ? 'alert' : undefined}><p>{message}</p></div>
    )
}

export default Notice;
