import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react'
import SingleNotificationX from './SingleNotificationX';

const NotificationXInner = ({ filteredNotice, setFilteredNotice, getNotice, updateNotice, totalItems, setTotalItems, checkAll, setCheckAll, setReload }) => {
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
                            <td>{__("NotificationX Title", 'notificationx')}</td>
                            <td>{__("Preview", 'notificationx')}</td>
                            <td>{__("Status", 'notificationx')}</td>
                            <td>{__("Type", 'notificationx')}</td>
                            <td>{__("Stats", 'notificationx')}</td>
                            <td>{__("Date", 'notificationx')}</td>
                            <td>{__("Action", 'notificationx')}</td>
                        </tr>
                    </thead>
                    <tbody>
                    {filteredNotice.map((item, i) => {
                        return <SingleNotificationX i={i} key={`nx-${item.nx_id}`} {...item} updateNotice={updateNotice} getNotice={getNotice} totalItems={totalItems} setTotalItems={setTotalItems} checkItem={checkItem} setReload={setReload} />
                    })}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

export default NotificationXInner;
