import React, { useEffect, useState } from 'react'
import SingleNotificationX from './SingleNotificationX';

const NotificationXInner = ({ filteredNotice, setFilteredNotice, getNotice, updateNotice, totalItems, setTotalItems, checkAll, setCheckAll, setCurrentPage }) => {
    const selectAll = () => {
        const notices = filteredNotice.map((item, i) => {
            return {...item, checked: !checkAll};
        });
        setFilteredNotice(notices);
        setCheckAll(!checkAll);
    }

    const checkItem = (index) => {
        const notices = filteredNotice.map((item, i) => {
            if(index == i){
                return {...item, checked: !item?.checked};
            }
            return {...item};
        });
        setFilteredNotice(notices);
    }

    // useEffect(() => {
    //     setNotices(filteredNotice);
    // }, [filteredNotice])



    return (
        <div className="nx-admin-items">
            <div className="nx-list-table-wrapper">
                <table className="wp-list-table widefat fixed striped notificationx-list">
                    <thead>
                        <tr>
                        <td>
                            <div className="nx-all-selector"><input type="checkbox" checked={checkAll} onChange={selectAll} name="nx_all" id="" /></div>
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
                        return <SingleNotificationX i={i} key={`nx-${item.nx_id}`} {...item} updateNotice={updateNotice} getNotice={getNotice} totalItems={totalItems} setTotalItems={setTotalItems} checkItem={checkItem} setCurrentPage={setCurrentPage} />
                    })}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

export default NotificationXInner;
