import React, { useState } from "react";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";
import { useNotificationXContext } from "../hooks";
import Select from "react-select";
import nxToast from "../core/ToasterMsg";

const NotificationXItemsMenu = ({
    notificationx,
    status,
    perPage,
    totalItems,
    filteredNotice,
    updateNotice,
    setTotalItems,
    setCheckAll,
}) => {
    const builderContext = useNotificationXContext();
    const [loading, setLoading] = useState(false);
    const defaultOption = { value: "", label: "Bulk Action", isDisabled: true };
    const [action, setAction] = useState<{
        label: string;
        value: string;
    }>(defaultOption);
    let bulkOptions: any = [{ ...defaultOption }];
    if (!builderContext.createRedirect) {
        bulkOptions = [
            ...bulkOptions,
            { value: "enable", label: "Enable" },
            { value: "disable", label: "Disable" },
            { value: "delete", label: "Delete" },
        ];
    }
    bulkOptions.splice(3, 0, { value: "regenerate", label: "Regenerate" });

    const request = (selectedItem) => {
        return nxHelper.post(`bulk-action/${action.value}`, {
            ids: selectedItem,
        });
    };

    const deleteAction = (selectedItem) => {
        nxHelper.swal({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "error",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete It",
            cancelButtonText: "No, Cancel",
            reverseButtons: true,
            customClass: { actions: "nx-delete-actions" },
            confirmedCallback: () => {
                setLoading(true);
                return request(selectedItem);
            },
            completeAction: (result) => {
                setCheckAll(false);
                setLoading(false);
                if (result?.success) {
                    const count = {
                        all: 0,
                        enabled: 0,
                        disabled: 0,
                    };
                    updateNotice((notices) =>
                        notices.filter((notice) => {
                            const isDeleted =
                                result?.count?.[notice.nx_id] &&
                                selectedItem.indexOf(parseInt(notice.nx_id)) !==
                                -1;
                            if (isDeleted) {
                                // if deleted then count them in.
                                count.all += 1;
                                count.enabled += notice.enabled ? 1 : 0;
                                count.disabled += !notice.enabled ? 1 : 0;
                            }
                            return !isDeleted;
                        })
                    );

                    setTotalItems((prev) => {
                        return {
                            all: Number(prev.all) - count.all,
                            enabled: Number(prev.enabled) - count.enabled,
                            disabled: Number(prev.disabled) - count.disabled,
                        };
                    });

                    return count;
                } else {
                    throw new Error("Something went wrong.");
                }
            },
            completeArgs: (result?) => {
                return ["deleted", `${result?.all || 0} notification Alerts have been
                Deleted.`];
            },
            afterComplete: () => { },
        });
    };
    const regenerateAction = (selectedItem, result) => {
        updateNotice((notices) =>
            notices.map((notice) => {
                return { ...notice };
            })
        );
        nxToast.regenerated(`${result?.count || 0} Notification Alerts have been
        Regenerated.`);
    };
    const enableAction = (selectedItem, result) => {
        let count = 0;
        updateNotice((notices) =>
            notices.map((notice) => {
                const isSelected =
                    result.count[notice.nx_id] &&
                    selectedItem.indexOf(parseInt(notice.nx_id)) !== -1;
                if (isSelected) {
                    count +=
                        notice.enabled == result.count[notice.nx_id] ? 0 : 1;
                    return { ...notice, enabled: true };
                }
                return { ...notice };
            })
        );

        setTotalItems((prev) => {
            return {
                all: Number(prev.all),
                enabled: Number(prev.enabled) + count,
                disabled: Number(prev.disabled) - count,
            };
        });
        nxToast.enabled(`${count} Notification Alerts have been Enabled.`);
    };
    const disableAction = (selectedItem, result) => {
        let count = 0;
        updateNotice((notices) =>
            notices.map((notice) => {
                const isSelected =
                    result?.count?.[notice.nx_id] &&
                    selectedItem.indexOf(parseInt(notice.nx_id)) !== -1;
                if (isSelected) {
                    count +=
                        notice.enabled == result.count[notice.nx_id] ? 1 : 0;
                    return { ...notice, enabled: false };
                }
                return { ...notice };
            })
        );

        setTotalItems((prev) => {
            return {
                all: Number(prev.all),
                enabled: Number(prev.enabled) - count,
                disabled: Number(prev.disabled) + count,
            };
        });
        nxToast.disabled(`${count} Notification Alerts have been Disabled.`);
    };

    const bulkAction = () => {
        if (!action.value || loading) {
            return;
        }

        // getting checked nx_id.
        const selectedItem = filteredNotice
            .filter((item) => {
                return item?.checked;
            })
            .map((item) => {
                return parseInt(item.nx_id);
            });
        if (!selectedItem.length) {
            return;
        }

        if (action.value == "delete") {
            deleteAction(selectedItem);
            return;
        }

        setLoading(true);
        request(selectedItem)
            .then((result: any) => {
                setCheckAll(false);
                setLoading(false);

                if (result?.success) {
                    if (action.value == "regenerate") {
                        regenerateAction(selectedItem, result);
                    }
                    if (action.value == "enable") {
                        enableAction(selectedItem, result);
                    }
                    if (action.value == "disable") {
                        disableAction(selectedItem, result);
                    }
                } else {
                    throw new Error("Something went wrong.");
                }
            })
            .catch((err) => {
                nxToast.error(`Unable to complete bulk action.`);
            });
    };

    return (
        <div className="nx-admin-menu">
            <ul>
                <li className={status === "all" ? "nx-active" : ""}>
                    <NavLink status="all" perPage={perPage}>
                        All ({totalItems?.all})
                    </NavLink>
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
                <button
                    className="nx-bulk-action-button"
                    onClick={bulkAction}
                    disabled={!action}
                >
                    {loading ? "Applying..." : "Apply"}
                </button>
            </div>
        </div>
    );
};

export default NotificationXItemsMenu;
