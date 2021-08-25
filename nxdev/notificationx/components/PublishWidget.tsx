import React, { useCallback, useState } from "react";
import { Button, ButtonGroup } from "@wordpress/components";
import { Date } from "../../form-builder/src/fields";
import { isInTheFuture } from "@wordpress/date";
import nxHelper from "../core/functions";
import { Redirect } from "react-router";
import Swal from "sweetalert2";
import { toast } from "react-toastify";
import { useNotificationXContext } from "../hooks";
import classNames from "classnames";

const PublishWidget = (props) => {
    const { title, context, isEdit, setIsLoading, setIsCreated, id, ...rest } = props;
    const [redirect, setRedirect] = useState<string>()
    const builderContext = useNotificationXContext();

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
                        setIsLoading(false);
                        if (setIsCreated) {
                            setIsCreated(res?.nx_id);
                        } else {
                            context.setValues(res);
                            context.setSavedValues(res);
                            rest?.setIsUpdated('saved');
                        }
                    } else {
                        console.error("NX Not Created");
                    }
                })
                .catch((err) => console.error("Error: ", err));
        },
        [title, context]
    );

    const handleDelete = useCallback(() => {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete It',
            cancelButtonText: 'No, Cancel',
            customClass: { actions: 'nx-delete-actions' },
            reverseButtons: true,
            // target: "#notificationx",
        }).then((result) => {
            if (result.isConfirmed) {
                nxHelper
                    .delete(`nx/${id}`, { nx_id: id })
                    .then((res) => {
                        if (res) {
                            toast.error(
                                "Notification Alert has been Successfully Deleted!",
                                {
                                    position: "bottom-right",
                                    autoClose: 5000,
                                    hideProgressBar: false,
                                    closeOnClick: true,
                                    pauseOnHover: true,
                                    draggable: true,
                                    progress: undefined,
                                }
                            ),
                            setRedirect('/');
                        } else {
                            toast.error("Oops, Something went wrong. Please try again.", {
                                position: "bottom-right",
                                autoClose: 5000,
                                hideProgressBar: false,
                                closeOnClick: true,
                                pauseOnHover: true,
                                draggable: true,
                                progress: undefined,
                            });
                        }
                    })
                    .catch((err) => console.error("Delete Error: ", err));
            }
        });
    }, [isEdit]);

    return (
        <div className="sidebar-widget nx-widget">
            {
                redirect && <Redirect to={redirect} />
            }
            <div className="nx-widget-title">
                <h4>Publish</h4>
            </div>
            <div className="nx-widget-content">
                <div className="nx-publish-date-widget">
                    <label htmlFor="updated_at">

                        {isInTheFuture(context.values?.updated_at)
                            ? "Scheduled For"
                            : `Publish${isEdit ? 'ed' : ""} On`
                        }
                        {" "}
                        :{" "}
                    </label>
                    <Date
                        name="updated_at"
                        value={context.values?.updated_at}
                        onChange={(data) =>
                            context.setFieldValue(
                                "updated_at",
                                data.target.value
                            )
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
                        {isInTheFuture(context.values?.updated_at) ? "Schedule" : (isEdit ? "Update" : "Publish")}
                    </Button>
                </ButtonGroup>
            </div>
        </div>
    );
};

export default PublishWidget;
