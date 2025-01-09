import React, { Fragment, useCallback, useEffect, useRef, useState } from "react";
import { sprintf, __ } from "@wordpress/i18n";
import { Link, Redirect } from "react-router-dom";
import nxHelper, { getAlert, permissionAlert, proAlert } from "../core/functions";
import { CopyToClipboard } from "react-copy-to-clipboard";
import { useNotificationXContext } from "../hooks";
import classNames from "classnames";
import nxToast from "../core/ToasterMsg";
import Swal from "sweetalert2";
import copy from "copy-to-clipboard";
import editIcon from '../icons/edit.png';
import translateIcon from '../icons/translate.png';
import duplicateIcon from '../icons/duplicate.png';
import shortcodeIcon from '../icons/shortcode.png';
import regenerateIcon from '../icons/regenerate.png';
import refreshIcon from '../icons/refresh.svg';
import deleteIcon from '../icons/trash.png';
import xssIcon from '../icons/xss.png';
import threeDots from '../icons/three-dots.svg';

// import copyToClipboard from '../icons/cross.png';

const SingleNotificationAction = ({
    id,
    getNotice,
    updateNotice,
    regenerate,
    setTotalItems,
    enabled,
    setReload,
    ...item
}) => {
    const nxContext = useNotificationXContext();
    const [action, setAction] = useState(false);
    const buttonRef = useRef(null);    
    let xssText = null;
    if (nxContext?.is_pro_active) {
        let xss_id = {};
        if (item.source == "press_bar") {
            if (!item?.elementor_id) {
                xss_id = { pressbar: [id] };
            }
        } else if (item?.global_queue) {
            xss_id = { global: [id] };
        } else {
            xss_id = { active: [id] };
        }
        const xss_data = { ...nxContext.xss_data, ...xss_id, cross: true };
        xssText = sprintf(
            `<script>\n window.notificationXArr = window.notificationXArr || []; \nwindow.notificationXArr.push(%s);\n</script>%s`,
            JSON.stringify(xss_data),
            nxContext.xss_scripts
        );
    }

    console.log('setReload',setReload);
                        
    
    // @ts-ignore
    const ajaxurl = window.ajaxurl;
    const handleDelete = useCallback(
        (event) => {
            if (id) {
                nxHelper.swal({
                    title: __("Are you sure?", "notificationx"),
                    text: __(
                        "You won't be able to revert this!",
                        "notificationx"
                    ),
                    icon: "error",
                    showCancelButton: true,
                    confirmButtonText: __("Yes, Delete It", "notificationx"),
                    cancelButtonText: __("No, Cancel", "notificationx"),
                    reverseButtons: true,
                    customClass: { actions: "nx-delete-actions" },
                    confirmedCallback: () => {
                        return nxHelper.delete(`nx/${id}`, { nx_id: id });
                    },
                    completeAction: (response) => {
                        setReload(r => !r);
                    },
                    completeArgs: () => {
                        return [
                            "deleted",
                            __(
                                `Notification Alert has been Deleted.`,
                                "notificationx"
                            ),
                        ];
                    },
                    afterComplete: () => { },
                });
            }
        },
        [id, getNotice]
    );
    
    const handleRegenerate = (event) => {
        nxHelper.swal({
            title: __("Are you sure you want to Regenerate?", "notificationx"),
            text: __(
                "Regenerating will fetch new data based on settings",
                "notificationx"
            ),
            iconHtml: `<img alt="NotificationX" src="${nxContext.assets.admin}images/regenerate.svg" style="height: 85px; width:85px" />`,
            showCancelButton: true,
            iconColor: "transparent",
            confirmButtonText: __("Regenerate", "notificationx"),
            cancelButtonText: __("Cancel", "notificationx"),
            reverseButtons: true,
            customClass: { actions: "nx-delete-actions" },
            confirmedCallback: () => {
                return nxHelper.get(`regenerate/${id}`, { nx_id: id });
            },
            completeAction: (response) => { },
            completeArgs: () => {
                return [
                    "regenerated",
                    __(
                        "Notification Alert has been Regenerated.",
                        "notificationx"
                    ),
                ];
            },
            afterComplete: () => {
                // setRedirect('/');
            },
        });
    };

    // @ts-ignore
    const handleCopy = useCallback(
        (event) => {
            if (id) {

                if (item?.type == 'inline') {
                    copy(`[notificationx_inline id=${id}]`, {
                        format: "text/plain",
                        onCopy: () => {
                            nxToast.info(
                                __(
                                    `Inline Notification Alert has been copied to Clipboard.`,
                                    "notificationx"
                                )
                            );
                        },
                    });
                    return;
                }
                Swal.fire({
                    iconHtml: `<img alt="NotificationX" src="${nxContext.assets.admin}images/shortcode.svg" style="height: 45px; width:55px" class="shortcodeIcon" />`,
                    iconColor: "#6a4bff",
                    title: "Copy to Clipboard",
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: __("Cancel", "notificationx"),
                    cancelButtonColor: "#d14529",
                    html: `<div class="swal-shortcode-wrapper">
                        <label><img src="${nxContext.assets.admin}images/copy icon.svg"/>Copy Regular Shortcode: <code id="regulat-shortcode" title="click to copy">[notificationx id=${id}]</code>
                            <span>Note: Regular Shortcode will copy the notification content & its styles.</span>
                        </label>
                        <label><img src="${nxContext.assets.admin}images/copy icon.svg"/>Copy Inline Shortcode: <code id="inline-shortcode" title="click to copy">[notificationx_inline id=${id}]</code>
                            <span>Note: Inline Shortcode will only copy the notification content which you can insert anywhere on your page.</span>
                        </label>
                    </div>`,
                    didOpen: () => {
                        document
                            .getElementById("regulat-shortcode")
                            .addEventListener("click", () => {
                                copy(`[notificationx id=${id}]`, {
                                    format: "text/plain",
                                    onCopy: () => {
                                        nxToast.info(
                                            __(
                                                `Regular Notification Alert has been copied to Clipboard.`,
                                                "notificationx"
                                            )
                                        );
                                    },
                                });
                            });
                        document
                            .getElementById("inline-shortcode")
                            .addEventListener("click", () => {
                                copy(`[notificationx_inline id=${id}]`, {
                                    format: "text/plain",
                                    onCopy: () => {
                                        nxToast.info(
                                            __(
                                                `Inline Notification Alert has been copied to Clipboard.`,
                                                "notificationx"
                                            )
                                        );
                                    },
                                });
                            });
                    },
                });
            }
        },
        [id, getNotice]
    );

    const onCopyXSS = (text, result) => {
        if (nxContext?.is_pro_active) {
            nxToast.info(
                __(
                    `Cross Domain Notice code has been copied to Clipboard.`,
                    "notificationx"
                )
            );
        } else {
            proAlert(
                sprintf(
                    __(
                        "You need to upgrade to the <strong><a target='_blank' href='%s' style='color:red'>Premium Version</a></strong> to use <a target='_blank' href='%s' style='color:red'>Cross Domain Notice</a> feature.",
                        "notificationx"
                    ),
                    "https://notificationx.com/#pricing",
                    "https://notificationx.com/docs/notificationx-cross-domain-notice/"
                )
            ).fire();
        }
    };

    // handle reset 
    const handleReset = () => {
        nxHelper.swal({
            title: __("Are you sure you want to Reset?", "notificationx"),
            text: __(
                "Reset will delete All analytics report for this notification",
                "notificationx"
            ),
            iconHtml: `<img alt="NotificationX" src="${refreshIcon}" style="height: 85px; width:85px" />`,
            showCancelButton: true,
            iconColor: "transparent",
            confirmButtonText: __("Reset", "notificationx"),
            cancelButtonText: __("Cancel", "notificationx"),
            reverseButtons: true,
            customClass: {
                container: 'nx-reset-analytics-container',
                popup: 'nx-reset-analytics-popup',
                actions: "nx-delete-actions nx-reset-analytics-action",
            },
            confirmedCallback: () => {
                return nxHelper.get(`reset/${id}`, { nx_id: id });
            },
            completeAction: (response) => { 
                nxContext.setReset({
                    analytics: response.data,
                })
                setReload(r => !r);
            },
            completeArgs: () => {
                return [
                    "regenerated",
                    __(
                        "Notification Alert has been Reset.",
                        "notificationx"
                    ),
                ];
            },
            afterComplete: () => {
                // setRedirect('/');
            },
        });
    };

    useEffect(() => {
        function handleClickOutside(event) {
          if (buttonRef.current && !buttonRef.current.contains(event.target)) {
            setAction(false);
          }
        }
    
        window.addEventListener('click', handleClickOutside);
        return () => {
          window.removeEventListener('click', handleClickOutside);
        };
    }, []);

    const handleWPMLRedirection = (event) => {        
        if( !nxContext.settings.is_wpml_active ) {
            nxToast.warning(
                __(
                    `You need to Install, Activate & Setup WPML Multilingual CMS & WPML String Translation plugins to use this feature.`,
                    "notificationx"
                )
            );
        }else{
            window.open(`${ajaxurl}?action=nx-translate&id=${id}`);
        }
    }
    
    return (
        <div className="nx-admin-actions-wrapper">
            <div className="nx-admin-actions nx-admin-action-button" ref={buttonRef}>
                <a
                    className="nx-admin-three-dots"
                    title={__("Three Dots", "notificationx")}
                    onClick={ () =>{
                        if( 'gdpr' === item?.type && Boolean(!nxContext?.has_gdpr_permission) ) {
                            const popup = getAlert(item?.type, nxContext);
                            permissionAlert(popup).fire();
                        }else{
                            setAction(!action)
                        }
                    }  }
                >   
                    <img src={threeDots} alt={'three-dots'} />
                </a>
            </div>
            { action && 
                <div className="nx-admin-actions nx-admin-actions-lists">
                    {/*  || item?.elementor_id */}
                     <ul id="nx-admin-actions-ul">
                        <li>
                            <Link
                                className="nx-admin-title-edit"
                                title={__("Edit", "notificationx")}
                                to={{
                                    pathname: "/admin.php",
                                    search: `?page=nx-edit&id=${id}`,
                                }}
                            >
                                <img src={editIcon} alt={'edit-icon'} />
                                <span>{__("Edit", "notificationx")}</span>
                            </Link>
                        </li>
                        <li>
                            <a
                                className={classNames("nx-admin-title-translate", {
                                    hidden: !nxContext?.can_translate,
                                })}
                                title={__("Translate", "notificationx")}
                                onClick={ handleWPMLRedirection }
                            >
                                <img src={translateIcon} alt={'translate-icon'} />
                                <span>{__("Translate", "notificationx")}</span>
                            </a>
                        </li>
                        <li>
                            <Link
                                className={classNames("nx-admin-title-duplicate", {
                                    hidden: nxContext?.createRedirect,
                                })}
                                title={__("Duplicate", "notificationx")}
                                to={{
                                    pathname: "/admin.php",
                                    search: `?page=nx-edit`, //&clone=${id}
                                    state: { duplicate: true, _id: id },
                                }}
                            >
                                <img src={duplicateIcon} alt={'duplicate-icon'} />
                                <span>{__("Duplicate", "notificationx")}</span>
                            </Link>
                        </li>
                        {nxContext?.is_pro_active && item.source != "press_bar" && item.source != "flashing_tab" && item.themes !== 'woo_inline_stock-theme-one' && item.themes !== 'woocommerce_sales_inline_stock-theme-one' && item.themes !== 'woo_inline_stock-theme-two' && item.themes !== 'woocommerce_sales_inline_stock-theme-two' && (
                            <li>
                                <a
                                    className="nx-admin-title-shortcode nx-shortcode-btn"
                                    title={__("Shortcode", "notificationx")}
                                    onClick={handleCopy}
                                >
                                    <img src={shortcodeIcon} alt={'shortcode-icon'} />
                                    <span>{__("ShortCode", "notificationx")}</span>
                                </a>
                            </li>
                         )}
                        {!nxContext?.is_pro_active && item.source != "press_bar" && item.source != "flashing_tab" && item.themes !== 'woo_inline_stock-theme-one' && item.themes !== 'woocommerce_sales_inline_stock-theme-one' && item.themes !== 'woo_inline_stock-theme-two' && item.themes !== 'woocommerce_sales_inline_stock-theme-two' && (
                            <li>
                                <CopyToClipboard
                                    className="nx-admin-title-shortcode nx-shortcode-btn"
                                    title={__("Shortcode", "notificationx")}
                                    text={`[notificationx_inline id=${id}]`}
                                    options={{ format: "text/plain" }}
                                    onCopy={() => {
                                        nxToast.info(
                                            __(
                                                `Inline Notification Alert has been copied to Clipboard.`,
                                                "notificationx"
                                            )
                                        );
                                    }}
                                >
                                    <a><img src={shortcodeIcon} alt={'shortcode-icon'} />{ __('Shortcode', 'notificationx') }</a>
                                </CopyToClipboard>
                            </li>
                        )}
                        <li>
                            {!item?.elementor_id && item.source != "flashing_tab" && (
                                <CopyToClipboard
                                    className="nx-admin-title-xss"
                                    title={__("Cross Domain Notice", "notificationx")}
                                    text={xssText}
                                    options={{ format: "text/plain" }}
                                    onCopy={onCopyXSS}
                                >
                                    <a> <img src={xssIcon} alt={'cross-domain-notice'} /> {__("Cross Domain Notice", "notificationx")}</a>
                                </CopyToClipboard>
                            )}
                        </li>
                        <li>
                            {/* <Link className="nx-admin-title-duplicate" title="Entries" to={`/entries/${id}`}><span>{__('Entries', 'notificationx')}</span></Link> */}
                            {regenerate && (
                                <Fragment>
                                    <a
                                        className="nx-admin-title-regenerate"
                                        onClick={handleRegenerate}
                                        title={__("Regenerate", "notificationx")}
                                    >
                                        <img src={regenerateIcon} alt={'regenerate-icon'} />
                                        <span>{__("Regenerate", "notificationx")}</span>
                                    </a>
                                </Fragment>
                            )}
                        </li>
                        <li>
                            <a
                                className={classNames("nx-admin-title-reset", {
                                    hidden: nxContext?.createRedirect,
                                })}
                                title={__("Reset", "notificationx")}
                                onClick={ handleReset }
                            >
                                <img src={refreshIcon} alt={'reset-icon'} />
                                <span>{__("Reset", "notificationx")}</span>
                            </a>
                        </li>
                        <li>
                            <a
                                className={classNames("nx-admin-title-trash", {
                                    hidden: nxContext?.createRedirect,
                                })}
                                title={__("Delete", "notificationx")}
                                onClick={handleDelete}
                            >
                                <img src={deleteIcon} alt={'delete-icon'} />
                                <span>{__("Delete", "notificationx")}</span>
                            </a>
                        </li>
                    </ul>
                </div>  
            }
        </div>
    );
};

export default SingleNotificationAction;
