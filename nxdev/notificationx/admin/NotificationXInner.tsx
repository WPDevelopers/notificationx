import React, { useState } from 'react'
import SingleNotificationX from './SingleNotificationX';

const NotificationXInner = ({ filteredNotice, getNotice, updateNotice, totalItems, setTotalItems }) => {
    const [checked, setChecked] = useState(false);
    const selectAll = () => {
        setChecked((prev) => {
            return !prev;
        });
    }

    return (
        <div className="nx-admin-items">
            <div className="nx-list-table-wrapper">
                <table className="wp-list-table widefat fixed striped notificationx-list">
                    <thead>
                        <tr>
                        <td>
                            <div className="nx-all-selector"><input type="checkbox" onChange={selectAll} name="nx_all" id="" /></div>
                        </td>
                            <td>NotificationX Title</td>
                            <td>Preview</td>
                            <td>Status</td>
                            <td>Type</td>
                            <td>Stats</td>
                            <td>Date</td>
                            <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>
                    {filteredNotice.map((item, i) => {
                        return <SingleNotificationX key={`nx-${item.nx_id}`} {...item} updateNotice={updateNotice} getNotice={getNotice} totalItems={totalItems} setTotalItems={setTotalItems} checked={checked} setChecked={setChecked} />
                    })}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

export default NotificationXInner;
