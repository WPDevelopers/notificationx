import React, { useCallback, useState } from "react";
import { Button, ButtonGroup } from "@wordpress/components";
import { Date } from "quickbuilder";
import { isInTheFuture } from "@wordpress/date";
import nxHelper, { proAlert } from "../core/functions";
import Swal from "sweetalert2";
import { useNotificationXContext } from "../hooks";
import classNames from "classnames";
import nxToast from "../core/ToasterMsg";
import { __, sprintf } from "@wordpress/i18n";

const PublishWidget = (props) => {
    const { title, context, isEdit, setIsLoading, setIsCreated, id, ...rest } = props;
    const builderContext = useNotificationXContext();
    const [ isEnabled, setIsEnabled  ] = useState(props?.context?.values?.enabled);
    const handleSubmit = useCallback(
        (event) => {
            context.setSubmitting(true);
            setIsLoading(true);
            let route = "nx" + (isEdit && id ? `/${id}` : "");
            nxHelper
                .post(route, {
                    ...context.values,
                    title,
                    currentTab: context.config.active,
                })
                .then((res: any) => {
                    if (res?.nx_id) {
                        if (setIsCreated) {
                            builderContext.setRedirect({
                                page: `nx-edit`,
                                id  : res?.nx_id,
                                state: { published: true }
                            });
                        } else {
                            setIsLoading(false);
                            context.setValues(res);
                            context.setSavedValues(res);
                            rest?.setIsUpdated('saved');
                        }
                    } else {
                        setIsLoading(false);
                        console.error(__("NX Not Created", 'notificationx'));
                    }
                })
                .catch((err) => console.error(__("Error: ", 'notificationx'), err));
        },
        [title, context]
    );

    const handleDelete = useCallback(() => {
        Swal.fire({
            title: __('Are you sure?', 'notificationx'),
            text: __("You won't be able to revert this!", 'notificationx'),
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: __('Yes, Delete It', 'notificationx'),
            cancelButtonText: __('No, Cancel', 'notificationx'),
            customClass: { actions: 'nx-delete-actions' },
            reverseButtons: true,
            // target: "#notificationx",
        }).then((result) => {
            if (result.isConfirmed) {
                nxHelper
                    .delete(`nx/${id}`, { nx_id: id })
                    .then((res) => {
                        if (res) {
                            nxToast.deleted( __(`Notification Alert has been Deleted.`, 'notificationx') );
                            builderContext.setRedirect({
                                page  : `nx-admin`,
                            });
                        } else {
                            nxToast.error( __(`Oops, Something went wrong. Please try again.`, 'notificationx') );
                        }
                    })
                    .catch((err) => console.error(__("Delete Error: ", 'notificationx'), err));
            }
        });
    }, [isEdit]);

  
    const toggleStatus = (event) => {        
        let   target  = event.target ? event.target : event.currentTarget;
        const source  = context?.values?.source;
        const enabled = target.checked;
        
        nxHelper
        .post("nx/" + id, {
            source: source,
            enabled: enabled,
            nx_id: id,
            update_status: true,
        })
        .then((res) => {
            if (res) {
                setIsEnabled(enabled);
                if (enabled) {
                    nxToast.enabled( __(`Notification Alert has been Enabled.`, "notificationx") );
                }else {
                    nxToast.disabled( __(`Notification Alert has been Disabled.`, "notificationx") );
                }
                context.setFieldValue( "enabled", enabled );
            } else if(res === 0) {
                proAlert(
                    enabled ? sprintf(__("You need to upgrade to the <strong><a target='_blank' href='%s' style='color:red'>Premium Version</a></strong> to use this feature.", "notificationx"), 'https://notificationx.com/#pricing')
                        : __("Disabled", "notificationx")
                ).fire();
            } else {
                proAlert(
                    enabled ? sprintf(__("You need to upgrade to the <strong><a target='_blank' href='%s' style='color:red'>Premium Version</a></strong> to use multiple notification.", "notificationx"), 'https://notificationx.com/#pricing')
                        : __("Disabled", "notificationx")
                ).fire();
            }
            return res;
        })
        .catch((err) => {
            nxToast.error( __(`Oops, Something went wrong. Please try again.`, "notificationx") );
        });
    };
    
    const meta_id = `_nx_meta_active_check_${id}`;
    return (
        <div className="sidebar-widget nx-widget">
            <div className="nx-widget-title">
                <h4>{ __('Publish','notificationx') }</h4>
                { id &&
                    <div className="nx-admin-status">
                        <input type="checkbox" name={"_nx_meta_active_check"} id={meta_id} onChange={ (event) => toggleStatus(event) } checked={isEnabled} />
                        <label htmlFor={meta_id}></label>
                        <span>{ isEnabled ? __('Active', 'notificationx') : __('Inactive', 'notificationx') } </span>
                    </div>
                } 
            </div>
            <div className="nx-widget-content">
                <div className="nx-publish-date-widget">
                    <label htmlFor="updated_at">

                        {isInTheFuture(context.values?.updated_at)
                            ? __("Scheduled For", 'notificationx')
                            : (isEdit ? __("Published On", 'notificationx') : __("Publish On", 'notificationx'))
                        }
                        {" "}
                        :{" "}
                        </label>
                        <Date
                        name="updated_at"
                        value={context.values?.updated_at}
                        position="bottom left"
                        onChange={(data) => {
                                context.setFieldValue(
                                    "updated_at",
                                    data.target.value
                                )
                            } 
                        }
                    />
                </div>
                <ButtonGroup>
                    {isEdit && (
                        <Button
                            className={classNames("nx-trash nx-btn is-danger",{
                                disabled: builderContext?.createRedirect,
                            })}
                            onClick={handleDelete}
                            disabled={builderContext?.createRedirect}
                        >
                            Delete
                        </Button>
                    )}
                    <Button
                        isPrimary
                        className={classNames("nx-save nx-btn",{
                            disabled: builderContext?.createRedirect,
                        })}
                        onClick={handleSubmit}
                        disabled={builderContext?.createRedirect}
                    >
                        {isInTheFuture(context.values?.updated_at) ? __("Schedule", 'notificationx') : (isEdit ? __("Update", 'notificationx') : __("Publish", 'notificationx'))}
                    </Button>
                </ButtonGroup>
            </div>
        </div>
    );
};

export default PublishWidget;
