import React, { useCallback, useState } from 'react'
import { __ } from '@wordpress/i18n';
import { Link, Redirect } from 'react-router-dom';
import nxHelper from '../core/functions';
import { useBuilderContext } from '../../form-builder';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { useNotificationXContext } from '../hooks';
import classNames from 'classnames';
import { toast } from "react-toastify";
import DeleteToastIcon from "../icons/Deleted";
import RegenerateToastIcon from "../icons/Regenerated";
import ConnectedToastIcon from "../icons/ConnectedSuccessful";

const SingleNotificationAction = ({
    id,
    getNotice,
    updateNotice,
    regenerate,
    setTotalItems,
    enabled,
}) => {
    const builderContext = useNotificationXContext();
    const [redirect, setRedirect] = useState<string>()

    const handleDelete = useCallback(
        (event) => {
            if (id) {
                nxHelper.swal({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete It',
                    cancelButtonText: 'No, Cancel',
                    reverseButtons: true,
                    customClass: { actions: 'nx-delete-actions' },
                    confirmedCallback: () => {
                        return nxHelper.delete(`nx/${id}`, { nx_id: id });
                    },
                    completeAction: (response) => {
                        updateNotice(notices => notices.filter(
                            (notice) => parseInt(notice.nx_id) !== parseInt(id)
                        ));
                    },
                    completeArgs: () => {
                        if (enabled) {
                            setTotalItems((prev) => {
                                return {
                                    all: Number(prev.all) - 1,
                                    enabled: Number(prev.enabled) + 1,
                                    disabled: Number(prev.disabled),
                                };
                            });
                        } else {
                            setTotalItems((prev) => {
                                return {
                                    all: Number(prev.all) - 1,
                                    enabled: Number(prev.enabled),
                                    disabled: Number(prev.disabled) - 1,
                                };
                            });
                        }
                        const DeleteMsg = <div className="nx-toast-wrapper">
                            <DeleteToastIcon />
                            <p>Notification Alert has been Deleted.</p>
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
                    },
                    afterComplete: () => {
                        setRedirect('/');
                    }

                });
            }
        },
        [id, getNotice]
    );

    const handleRegenerate = (event) => {
        nxHelper.swal({
            title: 'Are you sure you want to Regenerate?',
            text: "Regenerating will fetch new data based on settings",
            iconHtml: `<img alt="NotificationX" src="${builderContext.assets.admin}images/regenerate.svg" style="height: 85px; width:85px" />`,
            showCancelButton: true,
            iconColor: 'transparent',
            confirmButtonText: 'Regenerate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: { actions: 'nx-delete-actions' },
            confirmedCallback: () => {
                return nxHelper.get(`regenerate/${id}`, { nx_id: id });
            },
            completeAction: (response) => {

            },
            completeArgs: () => {
                const RegenerateMsg = <div className="nx-toast-wrapper">
                    <RegenerateToastIcon />
                    <p>Notification Alert has been Regenerated.</p>
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
            },
            afterComplete: () => {
                setRedirect('/');
            }

        });
    };

    const onCopy = () => {
        const CopyMsg = <div className="nx-toast-wrapper">
            <ConnectedToastIcon />
            <p>Notification Alert has been Copied to Clipboard.</p>
        </div>
        toast.info( CopyMsg,
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

    return (
        <div className="nx-admin-actions">
            {
                redirect && <Redirect to={redirect} />
            }
            <Link className="nx-admin-title-edit" title="Edit" to={`/edit/${id}`}><span>{__('Edit', 'notificationx')}</span></Link>
            <Link className={classNames("nx-admin-title-duplicate", {hidden: builderContext?.createRedirect})} title="Duplicate" to={{
                pathname: '/add-new',
                state: { duplicate: true, _id: id }
            }}><span>{__('Duplicate', 'notificationx')}</span></Link>
            {
                builderContext?.is_pro_active &&
                <CopyToClipboard className="nx-admin-title-shortcode nx-shortcode-btn" title="Shortcode" text={`[notificationx id=${id}]`} onCopy={onCopy} >
                    <a></a>
                </CopyToClipboard>
            }
            {/* <Link className="nx-admin-title-duplicate" title="Entries" to={`/entries/${id}`}><span>{__('Entries', 'notificationx')}</span></Link> */}
            {regenerate && (
                <button
                    className="nx-admin-title-regenerate"
                    onClick={handleRegenerate}
                    title="Re Generate"
                >
                    <span>{__("Re Generate", "notificationx")}</span>
                </button>
            )}
            <button className={classNames("nx-admin-title-trash", {hidden: builderContext?.createRedirect})} title="Delete" onClick={handleDelete}>
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
