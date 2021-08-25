import React, { useState } from "react";
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";
import { useNotificationXContext } from "../hooks";
// import { SelectControl } from "@wordpress/components";
import Select from 'react-select';
// import Select from "../../form-builder/src/fields/Select";

const NotificationXItemsMenu = ({
    notificationx,
    status,
    perPage,
    totalItems,
    filteredNotice,
    setFilteredNotice,
}) => {
    const builderContext = useNotificationXContext();
    const defaultOption = { value: "", label: "Bulk Action", disabled: true };
    const [action, setAction] = useState<{
        label: string;
        value: string;
    }>(defaultOption);
    const bulkOptions: any = [
        { ...defaultOption },
        { value: "regenerate", label: "Regenerate" },
    ];
    if(!builderContext.createRedirect){
        bulkOptions.splice(1, 0, { value: "delete", label: "Delete" });
    }

    const bulkAction = () => {
        if(!action.value){
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
        nxHelper.post(`nx/bulk-action/${action.value}`, {
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
                <Select
                    name="bulk-action"
                    className="bulk-action-select"
                    value={action}
                    onChange={(value) => {
                        setAction(value);
                    }}
                    options={bulkOptions}
                />
                <button className="nx-bulk-action-button" onClick={bulkAction} disabled={!action}>Apply</button>
            </div>
        </div>
    );
};

export default NotificationXItemsMenu;
