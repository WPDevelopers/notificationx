import React, { useCallback, useState } from 'react'
import { sprintf, __ } from '@wordpress/i18n';
import { Link, Redirect } from 'react-router-dom';
import nxHelper, { proAlert } from '../core/functions';
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
    ...item
}) => {
    const nxContext = useNotificationXContext();
    let xssText = null;
    if(nxContext?.is_pro_active){
        let xss_id = {};
        if(item.source == 'press_bar'){
            if(!item?.elementor_id){
                xss_id = {pressbar: [id]};
            }
        }
        else{
            xss_id = {active: [id]};
        }
        const xss_data = {...nxContext.xss_data, ...xss_id};
        xssText = sprintf(`<script>\nnotificationX = JSON.parse('%s');\n</script>%s`, JSON.stringify(xss_data), nxContext.xss_scripts);
    }

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

    const onCopy = (text, result) => {
        nxToast.info(__(`Notification Alert has been copied to Clipboard.`, 'notificationx'));
    }

    const onCopyXSS = (text, result) => {
        nxToast.info(__(`Cross Domain Tracking code has been copied to Clipboard.`, 'notificationx'));
    }

    return (
        <div className="nx-admin-actions">
            {/*  || item?.elementor_id */}
            <Link className="nx-admin-title-edit" title={__('Edit', 'notificationx')} to={{
                pathname: '/admin.php',
                search: `?page=nx-edit&id=${id}`,
            }}><span>{__('Edit', 'notificationx')}</span></Link>
            <a className={classNames("nx-admin-title-translate", { hidden: !nxContext?.can_translate })} title={__("Translate", "notificationx")} href={`${ajaxurl}?action=nx-translate&id=${id}`}>
                <span>{__("Translate", "notificationx")}</span>
            </a>
            <Link className={classNames("nx-admin-title-duplicate", { hidden: nxContext?.createRedirect })} title={__('Duplicate', 'notificationx')} to={{
                pathname: '/admin.php',
                search: `?page=nx-edit`, //&clone=${id}
                state: { duplicate: true, _id: id }
            }}><span>{__('Duplicate', 'notificationx')}</span></Link>
            {
                nxContext?.is_pro_active &&
                <CopyToClipboard className="nx-admin-title-shortcode nx-shortcode-btn" title={__("Shortcode", 'notificationx')} text={`[notificationx id=${id}]`} onCopy={onCopy} >
                    <a></a>
                </CopyToClipboard>
            }
            {
                nxContext?.is_pro_active && !item?.elementor_id &&
                <CopyToClipboard className="nx-admin-title-xss nx-shortcode-btn" title={__("XSS", 'notificationx')} text={xssText} options={{format: 'text/plain'}} onCopy={onCopyXSS} >
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
            <button className={classNames("nx-admin-title-trash", { hidden: nxContext?.createRedirect })} title={__("Delete", "notificationx")} onClick={handleDelete}>
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
