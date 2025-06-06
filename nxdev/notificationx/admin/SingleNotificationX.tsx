import React, { useState, useEffect } from "react";
import Toggle from "../components/Toggle";
import SingleNotificationAction from "./SingleNotificationAction";
import nxHelper, { getAlert, permissionAlert, proAlert } from "../core/functions";
import nxToast from "../core/ToasterMsg";
import { getIn } from 'quickbuilder';
import { sprintf, __ } from "@wordpress/i18n";
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
    setReload,
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
            }, { get_error: true } )
            .then((res:any) => {
                setLoading(false);
                if (res && 403 !== parseInt(res?.data?.status) ) {
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
                        nxToast.enabled( __(`Notification Alert has been Enabled.`, "notificationx") );
                    }
                    else {
                        nxToast.disabled( __(`Notification Alert has been Disabled.`, "notificationx") );
                    }
                } else if(res === 0) {
                    const popup = getAlert(item?.type, builderContext);                    
                    proAlert(popup).fire();
                } else {
                    if ( 403 === parseInt(res?.data?.status) ) {
                        const popup = getAlert(item?.type, builderContext);
                        permissionAlert(popup).fire();
                    }else {
                        const popup = getAlert(item?.type, builderContext);                    
                        proAlert(popup).fire();
                    }
                }
                return res;
            })
            .catch((err) => {
                setLoading(false);
                nxToast.error( __(`Oops, Something went wrong. Please try again.`, "notificationx") );
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
                    <input type="checkbox" name={`check-${id}`} id={`check-${id}`} checked={checked || false} onChange={onChecked} />
                </div>
            </td>
            <td>
                <div className="nx-admin-title">
                    <strong>
                    <Link to={{
                            pathname: '/admin.php',
                            search: `?page=nx-edit&id=${id}`,
                        }}>{title || id}</Link>
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
                    <NavLink
                    title={sprintf(__("%s clicks", "notificationx"), (item?.clicks || 0))}
                    to={{
                        pathname: '/admin.php',
                        search  : "?page=nx-analytics&comparison=views&nx=" + id,
                    }}>
                        {/* translators: %d: Number of views for a Notification Alert. */}
                        {sprintf(__("%s views", "notificationx"), (item?.views || 0))}
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
                    setReload={setReload}
                    {...item}
                />
            </td>
        </tr>
    );
};

export default SingleNotificationX;
