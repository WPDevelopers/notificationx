import React from 'react'

const Sidebar = ({ children }) => {
    return (
        <div className="nx-admin-sidebar">
            <div className="nx-admin-sidebar-wrapper">
                {children}
            </div>
        </div>
    )
}

export default Sidebar;