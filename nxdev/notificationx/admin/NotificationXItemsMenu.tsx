import React, { useState } from "react";
import NavLink from "../components/NavLink";
import nxHelper from "../core/functions";
import { useNotificationXContext } from "../hooks";
import Select from "react-select";
import nxToast from "../core/ToasterMsg";
import { sprintf, _n, __ } from "@wordpress/i18n";
import copy from "copy-to-clipboard";

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
    const defaultOption = { value: "", label: __("Bulk Action", 'notificationx'), isDisabled: true };
    const [action, setAction] = useState<{
        label: string;
        value: string;
    }>(defaultOption);
    let bulkOptions: any = [{ ...defaultOption }];
    if (!builderContext.createRedirect) {
        bulkOptions = [
            ...bulkOptions,
            { value: "enable",  label: __("Enable", 'notificationx') },
            { value: "disable", label: __("Disable", 'notificationx') },
            { value: "delete",  label: __("Delete", 'notificationx') },
        ];
    }
    bulkOptions.splice(3, 0, { value: "regenerate", label: __("Regenerate", 'notificationx') });
    if(builderContext?.is_pro_active && builderContext?.xss_data){
        bulkOptions.splice(4, 0, { value: "xss", label: __("Cross Domain Notice", 'notificationx') });
    }

    const request = (selectedItem) => {
        return nxHelper.post(`bulk-action/${action.value}`, {
            ids: selectedItem,
        });
    };

    const deleteAction = (selectedItem) => {
        nxHelper.swal({
            title: __("Are you sure?", 'notificationx'),
            html: sprintf(_n("You're about to delete %s notification alert,<br />", "You're about to delete %s notification alerts,<br />", selectedItem.length, 'notificationx'), selectedItem.length) + __("You won't be able to revert this!", 'notificationx'),
            icon: __("error", 'notificationx'),
            showCancelButton: true,
            confirmButtonText: __("Yes, Delete It", 'notificationx'),
            cancelButtonText: __("No, Cancel", 'notificationx'),
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
                    throw new Error(__("Something went wrong.", 'notificationx'));
                }
            },
            completeArgs: (result?) => {
                // translators: %d: Number of Notification Alerts deleted.
                return ["deleted", sprintf(__(`%d notification Alerts have been
                Deleted.`, 'notificationx'), (result?.all || 0))];
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
        // translators: %d: Number of Notification Alerts Regenerated.
        nxToast.regenerated(sprintf(__("%d Notification Alerts have been Regenerated.", 'notificationx'), (result?.count || 0)));
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
        // translators: %d: Number of Notification Alerts Enabled.
        nxToast.enabled(sprintf(__(`%d Notification Alerts have been Enabled.`, 'notificationx'), count));
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
        // translators: %d: Number of Notification Alerts Disabled.
        nxToast.disabled(sprintf(__(`%d Notification Alerts have been Disabled.`, 'notificationx'), count));
    };
    const generateXSS = (selectedItem) => {

        let xss_id = {
            pressbar: [],
            global: [],
            active: [],
        };
        for(let notice of filteredNotice){
            const id = parseInt(notice.nx_id);
            if(selectedItem.includes(id)){
                if(notice.source == 'press_bar'){
                    if(!notice?.elementor_id){
                        xss_id.pressbar.push(id);
                    }
                }
                else{
                    if(notice?.global_queue){
                        xss_id.global.push(id);
                    }
                    else{
                        xss_id.active.push(id);
                    }
                }
            }
        }

        const xss_data = {...builderContext.xss_data, ...xss_id};
        const xssText = sprintf(`<script>\nnotificationX = %s;\n</script>%s`, JSON.stringify(xss_data), builderContext.xss_scripts);

        copy(xssText, {
            format: 'text/plain',
            onCopy: () => {
                nxToast.info(
                    __(
                        `Cross Domain Notice code has been copied to Clipboard.`,
                        "notificationx"
                    )
                );
            },
        });
    }

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
        if (action.value == "xss") {
            generateXSS(selectedItem);
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
                    throw new Error(__("Something went wrong.", 'notificationx'));
                }
            })
            .catch((err) => {
                nxToast.error(__(`Unable to complete bulk action.`, 'notificationx'));
            });
    };

    return (
        <div className="nx-admin-menu">
            <ul>
                <li className={status === "all" ? "nx-active" : ""}>
                    <NavLink status="all" perPage={perPage}>
                        {/* translators: %d: Number of total Notification Alerts. */}
                        {sprintf(__("All (%d)", 'notificationx'), totalItems.all)}
                    </NavLink>
                </li>
                <li className={status === "enabled" ? "nx-active" : ""}>
                    <NavLink status="enabled" perPage={perPage}>
                        {/* translators: %d: Number of total Notification Alerts enabled. */}
                        {sprintf(__("Enabled (%d)", 'notificationx'), totalItems.enabled)}
                    </NavLink>
                </li>
                <li className={status === "disabled" ? "nx-active" : ""}>
                    <NavLink status="disabled" perPage={perPage}>
                        {/* translators: %d: Number of total Notification Alerts disabled. */}
                        {sprintf(__("Disabled (%d)", 'notificationx'), totalItems.disabled)}
                    </NavLink>
                </li>
            </ul>
            <div className="nx-bulk-action-wrapper">
                <Select
                    name="bulk-action"
                    className="bulk-action-select"
                    classNamePrefix="bulk-action-select"
                    value={action}
                    isSearchable={false}
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
                    {loading ? __("Applying...", 'notificationx') : __("Apply", 'notificationx')}
                </button>
            </div>
        </div>
    );
};

export default NotificationXItemsMenu;
