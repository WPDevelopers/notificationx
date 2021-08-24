import React from "react";
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";

const NotificationXItemsMenu = ({
    notificationx,
    status,
    perPage,
    totalItems,
}) => {
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
                <div className="bulk-action-select-wrapper">
                    <select className="bulk-action-select">
                        <option selected disabled>Bulk Action</option>
                        <option value="delete">Delete</option>
                        <option value="regenerate">Regenerate</option>
                    </select>
                </div>
                <button className="nx-bulk-action-button">Apply</button>
            </div>
        </div>
    );
};

export default NotificationXItemsMenu;
