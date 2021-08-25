import { SelectControl } from "@wordpress/components";
import React, { useState } from "react";
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";
import { useNotificationXContext } from "../hooks";
import Select from 'react-select';

const NotificationXItemsMenu = ({
    notificationx,
    status,
    perPage,
    totalItems,
    filteredNotice,
    setFilteredNotice,
}) => {
    const [action, setAction] = useState('');
    const builderContext = useNotificationXContext();
    const bulkOptions = [
        { value: "", label: "Bulk Action", disabled: true },
        { value: "regenerate", label: "Regenerate" },
    ];
    if(!builderContext.createRedirect){
        bulkOptions.splice(1, 0, { value: "delete", label: "Delete" });
    }

    const bulkAction = () => {
        if(!action){
            return;
        }
        // getting checked nx_id.
        const selectedItem = filteredNotice.filter((item) => {
            return item?.checked;
        }).map((item) => {
            return item.nx_id;
        });
        if(!selectedItem.length){
            return;
        }
        nxHelper.post(`nx/bulk-action/${action}`, {
            ids: selectedItem,
        });
    }

    return (
        <div className="nx-admin-menu">
            <ul>
                <li className={status === "all" ? "nx-active" : ""}>
                    <NavLink status="all" perPage={perPage}>All ({totalItems?.all})</NavLink>
                </li>
                <li className={status === "enabled" ? "nx-active" : ""}>
                    <NavLink status="enabled" perPage={perPage}>
                        Enabled ({totalItems?.enabled})
                    </NavLink>
                </li>
                <li className={status === "disabled" ? "nx-active" : ""}>
                    <NavLink status="disabled" perPage={perPage}>
                        Disabled ({totalItems?.disabled})
                    </NavLink>
                </li>
            </ul>
            <div className="nx-bulk-action-wrapper">
                <ReactSelect
                <SelectControl
                    className="bulk-action-select"
                    value={action}
                    onChange={(val) => {
                        setAction(val);
                    }}
                    options={bulkOptions}
                />
                <button className="nx-bulk-action-button" onClick={bulkAction} disabled={!action}>Apply</button>
            </div>
        </div>
    );
};

export default NotificationXItemsMenu;
