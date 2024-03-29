import React, { useCallback } from "react";
import { sprintf, __ } from "@wordpress/i18n";
import { Link } from "react-router-dom";
import nxHelper, { proAlert } from "../core/functions";
import { CopyToClipboard } from "react-copy-to-clipboard";
import { useNotificationXContext } from "../hooks";
import classNames from "classnames";
import nxToast from "../core/ToasterMsg";
import Swal from "sweetalert2";
import copy from "copy-to-clipboard";

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
                    "http://wpdeveloper.com/in/upgrade-notificationx",
                    "https://notificationx.com/docs/notificationx-cross-domain-notice/"
                )
            ).fire();
        }
    };

    return (
        <div className="nx-admin-actions">
            {/*  || item?.elementor_id */}
            <Link
                className="nx-admin-title-edit"
                title={__("Edit", "notificationx")}
                to={{
                    pathname: "/admin.php",
                    search: `?page=nx-edit&id=${id}`,
                }}
            >
                <span>{__("Edit", "notificationx")}</span>
            </Link>
            <a
                className={classNames("nx-admin-title-translate", {
                    hidden: !nxContext?.can_translate,
                })}
                title={__("Translate", "notificationx")}
                href={`${ajaxurl}?action=nx-translate&id=${id}`}
            >
                <span>{__("Translate", "notificationx")}</span>
            </a>
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
                <span>{__("Duplicate", "notificationx")}</span>
            </Link>
            {nxContext?.is_pro_active && item.source != "press_bar" && item.source != "flashing_tab" && item.themes !== 'woo_inline_stock-theme-one' && item.themes !== 'woocommerce_sales_inline_stock-theme-one' && item.themes !== 'woo_inline_stock-theme-two' && item.themes !== 'woocommerce_sales_inline_stock-theme-two' && (
                <button
                    className="nx-admin-title-shortcode nx-shortcode-btn"
                    title={__("Shortcode", "notificationx")}
                    onClick={handleCopy}
                >
                    <span>{__("ShortCode", "notificationx")}</span>
                </button>
            )}
            {!nxContext?.is_pro_active && item.source != "press_bar" && item.source != "flashing_tab" && item.themes !== 'woo_inline_stock-theme-one' && item.themes !== 'woocommerce_sales_inline_stock-theme-one' && item.themes !== 'woo_inline_stock-theme-two' && item.themes !== 'woocommerce_sales_inline_stock-theme-two' && (
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
                    <a></a>
                </CopyToClipboard>
            )}
            {!item?.elementor_id && item.source != "flashing_tab" && (
                <CopyToClipboard
                    className="nx-admin-title-xss"
                    title={__("Cross Domain Notice", "notificationx")}
                    text={xssText}
                    options={{ format: "text/plain" }}
                    onCopy={onCopyXSS}
                >
                    <a></a>
                </CopyToClipboard>
            )}
            {/* <Link className="nx-admin-title-duplicate" title="Entries" to={`/entries/${id}`}><span>{__('Entries', 'notificationx')}</span></Link> */}
            {regenerate && (
                <button
                    className="nx-admin-title-regenerate"
                    onClick={handleRegenerate}
                    title={__("Regenerate", "notificationx")}
                >
                    <span>{__("Regenerate", "notificationx")}</span>
                </button>
            )}
            <button
                className={classNames("nx-admin-title-trash", {
                    hidden: nxContext?.createRedirect,
                })}
                title={__("Delete", "notificationx")}
                onClick={handleDelete}
            >
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
