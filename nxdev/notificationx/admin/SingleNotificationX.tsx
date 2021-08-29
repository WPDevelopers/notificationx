import React, { useState, useEffect } from "react";
import Toggle from "../components/Toggle";
import SingleNotificationAction from "./SingleNotificationAction";
import nxHelper, { proAlert } from "../core/functions";
import AlertEnableIcon from "../icons/Enabled";
import { toastDefaultArgs } from "../core/ToasterMsg";

import { __ } from "@wordpress/i18n";
import { ThemePreview } from "../components";
// @ts-ignore
import {
    __experimentalGetSettings,
    isInTheFuture,
    date,
} from "@wordpress/date";
import moment from "moment";
import { Link, NavLink } from "react-router-dom";
import { useNotificationXContext } from "../hooks";
import classNames from "classnames";
import { toast } from "react-toastify";
import EnableToastIcon from "../icons/Enabled";
import DisableToastIcon from "../icons/NXDisable";
import ErrorToastIcon from "../icons/Error";

const SingleNotificationX = ({
    i,
    nx_id: id,
    title,
    getNotice,
    updateNotice,
    totalItems,
    setTotalItems,
    checked,
    // _checked,
    checkItem,
    ...item
}) => {
    const builderContext = useNotificationXContext();
    const settings: any = __experimentalGetSettings();
    const updated_at = moment
        .utc(item?.updated_at)
        .utcOffset(+settings?.timezone?.offset); //
    const [loading, setLoading] = useState(false);
    const disabled =
        !builderContext.is_pro_active &&
        builderContext.is_pro_sources?.[item.source];
    const toggleStatus = (event: Event, enabled: string, props) => {
        if (loading) {
            return Promise.reject();
        }
        setLoading(true);
        return nxHelper
            .post("nx/" + id, {
                source: props.source,
                enabled,
                nx_id: id,
                update_status: true,
            })
            .then((res) => {
                setLoading(false);
                if (res) {
                    if (enabled) {
                        setTotalItems((prev) => {
                            return {
                                ...prev,
                                enabled: Number(prev.enabled) + 1,
                                disabled: Number(prev.disabled) - 1,
                            };
                        });
                    } else {
                        setTotalItems((prev) => {
                            return {
                                ...prev,
                                enabled: Number(prev.enabled) - 1,
                                disabled: Number(prev.disabled) + 1,
                            };
                        });
                    }
                    updateNotice((prev) =>
                        prev.map((val) => {
                            if (parseInt(val.nx_id) === parseInt(id)) {
                                return { ...val, enabled: enabled };
                            }
                            return { ...val };
                        })
                    );
                    if (enabled) {
                        const EnableMsg = <div className="nx-toast-wrapper">
                            <EnableToastIcon />
                            <p>Notification Alert has been Enabled.</p>
                        </div>
                        toast.info( EnableMsg, toastDefaultArgs );
                    }
                    else {
                        const DisableMsg = <div className="nx-toast-wrapper">
                            <DisableToastIcon />
                            <p>Notification Alert has been Disabled.</p>
                        </div>
                        toast.warning( DisableMsg, toastDefaultArgs )
                    }
                } else {
                    // const ErrorMsg = <div className="nx-toast-wrapper">
                    //     <ErrorToastIcon />
                    //     <p>Oops, Something went wrong. Please try again.</p>
                    // </div>
                    // toast.error( ErrorMsg, toastDefaultArgs );
                    proAlert(
                        enabled
                            ? "You need to upgrade to the <strong><a target='_blank' href='http://wpdeveloper.net/in/upgrade-notificationx' style='color:red'>Premium Version</a></strong> to use multiple notification."
                            : "Disabled"
                    ).fire();
                }
                return res;
            })
            .catch((err) => {
                setLoading(false);
                const ErrorMsg = <div className="nx-toast-wrapper">
                    <ErrorToastIcon />
                    <p>Oops, Something went wrong. Please try again.</p>
                </div>
                toast.error( ErrorMsg, toastDefaultArgs );
            });
    };
    // const [checked, setChecked] = useState(false);
    const onChecked = (e) => {
        checkItem(i);
        // setAllChecked(false);
    }
    // useEffect(() => {
    //     setChecked(_checked);
    // }, [_checked])

    return (
        <tr
            className={classNames({
                disabled: disabled,
            })}
        >
            <td>
                <div className="nx-item-selector">
                    <input type="checkbox" name={`check-${id}`} id={`check-${id}`} checked={checked} onChange={onChecked} />
                </div>
            </td>
            <td>
                <div className="nx-admin-title">
                    <strong>
                        <Link to={`/edit/${id}`}>{title || id}</Link>
                    </strong>
                </div>
            </td>
            <td>
                <ThemePreview name={item.themes} preview={item?.preview} />
            </td>
            <td>
                <Toggle
                    id={id}
                    name="_nx_meta_active_check"
                    value={item.enabled}
                    source={item.source}
                    onChange={toggleStatus}
                />
            </td>
            <td>
                <div className="nx-admin-type" title={item?.source_label}>
                    {item?.type_label || item.type}
                </div>
            </td>
            <td>
                <div className="nx-admin-stats">
                    <NavLink to={"/analytics/?comparison=views"}>
                        {item?.views || 0} views
                    </NavLink>
                </div>
            </td>
            <td>
                <div className="nx-admin-date">
                    {isInTheFuture(updated_at.format())
                        ? __("Scheduled For", "notificationx")
                        : __("Published", "notificationx")}
                    <br />
                    <span className="nx-admin-publish-date">
                        {date(
                            settings?.formats?.datetime,
                            updated_at,
                            undefined
                        )}
                    </span>
                </div>
            </td>
            <td>
                <SingleNotificationAction
                    getNotice={getNotice}
                    updateNotice={updateNotice}
                    id={id}
                    regenerate={item?.can_regenerate}
                    enabled={item.enabled}
                    setTotalItems={setTotalItems}
                />
            </td>
        </tr>
    );
};

export default SingleNotificationX;
