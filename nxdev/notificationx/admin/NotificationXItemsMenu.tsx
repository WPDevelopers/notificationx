import React, { useState } from "react";
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";
import { useNotificationXContext } from "../hooks";
// import { SelectControl } from "@wordpress/components";
import Select from 'react-select';
// import Select from "../../form-builder/src/fields/Select";
import { toast } from "react-toastify";
import DeleteToastIcon from "../icons/Deleted";
import RegenerateToastIcon from "../icons/Regenerated";
import EnableToastIcon from "../icons/Enabled";
import DisableToastIcon from "../icons/NXDisable";
import ErrorToastIcon from "../icons/Error";

const NotificationXItemsMenu = ({
    notificationx,
    status,
    perPage,
    totalItems,
    filteredNotice,
    updateNotice,
    setTotalItems,
    setCheckAll
}) => {
    const builderContext = useNotificationXContext();
    const [loading, setLoading] = useState(false);
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
        bulkOptions.push({ value: "enable", label: "Enable" });
        bulkOptions.push({ value: "disable", label: "Disable" });
    }

    const bulkAction = () => {
        if(!action.value || loading){
            return;
        }

        // getting checked nx_id.
        const selectedItem = filteredNotice.filter((item) => {
            return item?.checked;
        }).map((item) => {
            return parseInt(item.nx_id);
        });
        if(!selectedItem.length){
            return;
        }

        setLoading(true);
        nxHelper.post(`bulk-action/${action.value}`, {
            ids: selectedItem,
        }).then((result: any) => {
            console.log(result);
            setCheckAll(false);
            setLoading(false);
            if(result?.success){
                if(action.value == 'delete'){
                    const count = {
                        enabled : 0,
                        disabled: 0,
                    }
                    updateNotice(notices => notices.filter((notice) => {
                        const isDeleted = selectedItem.indexOf(parseInt(notice.nx_id)) !== -1;
                        if(isDeleted){
                            // if deleted then count them in.
                            count.enabled  += notice.enabled ? 1 : 0;
                            count.disabled += !notice.enabled ? 1 : 0;
                        }
                        return !isDeleted;
                    }));

                    setTotalItems((prev) => {
                        return {
                            all     : Number(prev.all) - result?.count,
                            enabled : Number(prev.enabled)  - count.enabled,
                            disabled: Number(prev.disabled) - count.disabled,
                        };
                    });

                    const DeleteMsg = <div className="nx-toast-wrapper">
                        <DeleteToastIcon />
                        <p>{result?.count} notification Alerts have been Deleted.</p>
                    </div>
                    toast.error( DeleteMsg,
                        {
                            position: "bottom-right",
                            autoClose: 5000,
                            hideProgressBar: false,
                            closeOnClick: true,
                            pauseOnHover: true,
                            draggable: true,
                            progress: undefined,
                        }
                    );
                }
                if(action.value == 'regenerate'){
                    const RegenerateMsg = <div className="nx-toast-wrapper">
                        <RegenerateToastIcon />
                        <p>{selectedItem.length} Notification Alerts have been Regenerated.</p>
                    </div>
                    toast.info( RegenerateMsg,
                        {
                            position: "bottom-right",
                            autoClose: 5000,
                            hideProgressBar: false,
                            closeOnClick: true,
                            pauseOnHover: true,
                            draggable: true,
                            progress: undefined,
                        }
                    );
                }
                if(action.value == 'enable'){
                    let count = 0;
                    updateNotice(notices => notices.map((notice) => {
                        const isSelected = selectedItem.indexOf(parseInt(notice.nx_id)) !== -1;
                        if(isSelected){
                            count  += notice.enabled ? 0 : 1;
                            return {...notice, enabled: true};
                        }
                        return {...notice};
                    }));

                    setTotalItems((prev) => {
                        return {
                            all     : Number(prev.all),
                            enabled : Number(prev.enabled)  + count,
                            disabled: Number(prev.disabled) - count,
                        };
                    });
                    const EnableMsg = <div className="nx-toast-wrapper">
                        <EnableToastIcon />
                        <p>{count} Notification Alerts have been Enabled.</p>
                    </div>
                    toast.info( EnableMsg ,
                        {
                            position: "bottom-right",
                            autoClose: 5000,
                            hideProgressBar: false,
                            closeOnClick: true,
                            pauseOnHover: true,
                            draggable: true,
                            progress: undefined,
                        }
                    );
                }
                if(action.value == 'disable'){
                    let count = 0;
                    updateNotice(notices => notices.map((notice) => {
                        const isSelected = selectedItem.indexOf(parseInt(notice.nx_id)) !== -1;
                        if(isSelected){
                            count  += notice.enabled ? 1 : 0;
                            return {...notice, enabled: false};
                        }
                        return {...notice};
                    }));

                    setTotalItems((prev) => {
                        return {
                            all     : Number(prev.all),
                            enabled : Number(prev.enabled)  - count,
                            disabled: Number(prev.disabled) + count,
                        };
                    });
                    const DisableMsg = <div className="nx-toast-wrapper">
                        <DisableToastIcon />
                        <p>{count} Notification Alerts have been Disabled.</p>
                    </div>
                    toast.warning( DisableMsg,
                        {
                            position: "bottom-right",
                            autoClose: 5000,
                            hideProgressBar: false,
                            closeOnClick: true,
                            pauseOnHover: true,
                            draggable: true,
                            progress: undefined,
                        }
                    );
                }
            }
            else {
                throw new Error("Something went wrong.");
            }
        }).catch(err => {
            const ErrorMsg = <div className="nx-toast-wrapper">
                <ErrorToastIcon />
                <p>Unable to complete bulk action.</p>
            </div>
            toast.error( ErrorMsg, 
                {
                    position: "bottom-right",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                }
            );
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
                    classNamePrefix="bulk-action-select"
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
