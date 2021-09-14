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
                    title: __('Are you sure?', 'notificationx'),
                    text: __("You won't be able to revert this!", 'notificationx'),
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: __('Yes, Delete It', 'notificationx'),
                    cancelButtonText: __('No, Cancel', 'notificationx'),
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
                        return ['deleted', __(`Notification Alert has been Deleted.`, 'notificationx')];
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
            title: __('Are you sure you want to Regenerate?', 'notificationx'),
            text: __("Regenerating will fetch new data based on settings", 'notificationx'),
            iconHtml: `<img alt="NotificationX" src="${nxContext.assets.admin}images/regenerate.svg" style="height: 85px; width:85px" />`,
            showCancelButton: true,
            iconColor: 'transparent',
            confirmButtonText: __('Regenerate', 'notificationx'),
            cancelButtonText: __('Cancel', 'notificationx'),
            reverseButtons: true,
            customClass: { actions: 'nx-delete-actions' },
            confirmedCallback: () => {
                return nxHelper.get(`regenerate/${id}`, { nx_id: id });
            },
            completeAction: (response) => {

            },
            completeArgs: () => {
                return ['regenerated', __('Notification Alert has been Regenerated.', 'notificationx')];
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
                    window.open(response.redirect, '_blank');
                }
                else{
                    ToastAlert('error', __("Something went wrong.", 'notificationx'));
                }

            });
    };

    const onCopy = () => {
        nxToast.info( __(`Notification Alert has been Copied to Clipboard.`, 'notificationx') );
    }

    return (
        <div className="nx-admin-actions">
            <button className={classNames("nx-admin-title-translate", {hidden: !nxContext?.can_translate})} title={__("Translate", "notificationx")} onClick={handleTranslate}>
                <span>{__("Translate", "notificationx")}</span>
            </button>
            <Link className="nx-admin-title-edit" title={__('Edit', 'notificationx')} to={{
                        pathname: '/admin.php',
                        search: `?page=nx-edit&id=${id}`,
                    }}><span>{__('Edit', 'notificationx')}</span></Link>
            <Link className={classNames("nx-admin-title-duplicate", {hidden: nxContext?.createRedirect})} title={__('Duplicate', 'notificationx')} to={{
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
                    title={__("Re Generate", "notificationx")}
                >
                    <span>{__("Re Generate", "notificationx")}</span>
                </button>
            )}
            <button className={classNames("nx-admin-title-trash", {hidden: nxContext?.createRedirect})} title={__("Delete", "notificationx")} onClick={handleDelete}>
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
