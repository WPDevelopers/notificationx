import React, { useCallback, useState } from 'react'
import { __ } from '@wordpress/i18n';
import { Link, Redirect } from 'react-router-dom';
import nxHelper from '../core/functions';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { useNotificationXContext } from '../hooks';
import classNames from 'classnames';
import nxToast, { ToastAlert } from "../core/ToasterMsg";

const SingleNotificationAction = ({
    id,
    getNotice,
    updateNotice,
    regenerate,
    setTotalItems,
    enabled,
}) => {
    const nxContext = useNotificationXContext();
    // @ts-ignore
    const ajaxurl = window.ajaxurl;
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
                        if (enabled) {
                            setTotalItems((prev) => {
                                return {
                                    all: Number(prev.all) - 1,
                                    enabled: Number(prev.enabled) - 1,
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
                    },
                    completeArgs: () => {
                        return ['deleted', `Notification Alert has been Deleted.`];
                    },
                    afterComplete: () => {
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
            iconHtml: `<img alt="NotificationX" src="${nxContext.assets.admin}images/regenerate.svg" style="height: 85px; width:85px" />`,
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
                return ['regenerated', 'Notification Alert has been Regenerated.'];
            },
            afterComplete: () => {
                // setRedirect('/');
            }

        });
    };

    const handleTranslate = (event) => {
        nxHelper.get(`translate/${id}`)
            .then((response: any) => {
                if(response?.redirect){
                    window.location = response.redirect;
                }
                else{
                    ToastAlert('error', __("Something went wrong.", 'notificationx'));
                }

            });
    };

    const onCopy = () => {
        nxToast.info( `Notification Alert has been Copied to Clipboard.` );
    }

    return (
        <div className="nx-admin-actions">
            <button className={classNames("nx-admin-title-edit", {hidden: !nxContext?.can_translate})} title="Translate" onClick={handleTranslate}>
                <span>{__("Translate", "notificationx")}</span>
            </button>
            <Link className="nx-admin-title-edit" title="Edit" to={{
                        pathname: '/admin.php',
                        search: `?page=nx-edit&id=${id}`,
                    }}><span>{__('Edit', 'notificationx')}</span></Link>
            <Link className={classNames("nx-admin-title-duplicate", {hidden: nxContext?.createRedirect})} title="Duplicate" to={{
                pathname: '/admin.php',
                search: `?page=nx-edit`, //&clone=${id}
                state: { duplicate: true, _id: id }
            }}><span>{__('Duplicate', 'notificationx')}</span></Link>
            {
                nxContext?.is_pro_active &&
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
            <button className={classNames("nx-admin-title-trash", {hidden: nxContext?.createRedirect})} title="Delete" onClick={handleDelete}>
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
