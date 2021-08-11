import React, { useCallback, useState } from "react";
import { Button, ButtonGroup } from "@wordpress/components";
import { Date } from "../../form-builder/src/fields";
import { isInTheFuture } from "@wordpress/date";
import nxHelper, { SweetAlert } from "../core/functions";
import { Redirect } from "react-router";
import Swal from "sweetalert2";

const PublishWidget = (props) => {
    const { title, context, isEdit, setIsLoading, setIsCreated, id, ...rest } = props;
    const [redirect, setRedirect] = useState<string>()

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
        //FIXME: NEED SOME CSS FOR THIS
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
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                nxHelper
                    .delete(`nx/${id}`, { nx_id: id })
                    .then((res) => {
                        if (res) {
                            Swal.fire({
                                title: 'Complete',
                                text: "Deleted!",
                                icon: 'success',
                                timer: 2500,
                            }).then(() => {
                                setRedirect('/');
                            });
                        } else {
                            //TODO: add sweatalert
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
                            className="nx-trash nx-btn is-danger"
                            onClick={handleDelete}
                        >
                            Delete
                        </Button>
                    )}
                    <Button
                        isPrimary
                        className="nx-save nx-btn"
                        onClick={handleSubmit}
                    >
                        {isInTheFuture(context.values?.updated_at) ? "Schedule" : (isEdit ? "Update" : "Publish")}
                    </Button>
                </ButtonGroup>
            </div>
        </div>
    );
};

export default PublishWidget;
