import React, { useEffect, useRef, useState } from "react";
import nxHelper from "../core/functions";
import { isArray } from "../../form-builder/src/core/utils";
import NotificationXInner from "./NotificationXInner";
import NotificationXItemsMenu from "./NotificationXItemsMenu";
import { useLocation } from "react-router";
import Pagination from "rc-pagination";
import localeInfo from 'rc-pagination/es/locale/en_US';
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import { SelectControl } from "@wordpress/components";
import { WrapperWithLoader } from "../components";
import LargeLogoIcon from '../../../assets/admin/images/logos/large-logo-icon.png';
import { useNotificationXContext } from "../hooks";
import { __, sprintf } from "@wordpress/i18n";
import parse from 'html-react-parser';

export const NotificationXItems = (props) => {
    const builderContext = useNotificationXContext();
    const [checkAll, setCheckAll] = useState(false);
    const isMounted = useRef(null);
    const loading = {
        title: "loading...",
    };
    const [isLoading, setIsLoading] = useState(true);
    const [totalItems, setTotalItems] = useState({
        all: 0,
        enabled: 0,
        disabled: 0,
    });
    const [perPage, setPerPage] = useState(20);
    const [currentPage, setCurrentPage] = useState(1);
    const [notificationx, setNotificationx] = useState([loading]);
    const [filteredNotice, setFilteredNotice] = useState([loading]);
    const location = useLocation();

    const getParam = (param, d?) => {
        const query = nxHelper.useQuery(location.search);
        return query.get(param) || d;
    };
    const status = getParam("status", "all");

    const itemRender = (current, type, element) => {
        return <NavLink status={status} current={current} perPage={perPage}>{__(current)}</NavLink>;
    };

    useEffect(() => {
        isMounted.current = true;
        // Not Needed
        const p = getParam("p", 1);
        const pp = getParam("per-page", 20);
        setCurrentPage(Number(p));
        setPerPage(Number(pp));
        return () => {
            isMounted.current = false
        }
    }, []);

    useEffect(() => {
        if (currentPage === 0 || perPage === 0) return;
        setIsLoading(true);
        nxHelper
            .get(`nx?status=${status}&page=${currentPage}&per_page=${perPage}`)
            .then((res: any) => {
                setIsLoading(false);
                if (isArray(res?.posts) && isMounted.current) {
                    setNotificationx(res?.posts);
                    setFilteredNotice(
                        nxHelper.filtered(res?.posts, status)
                    );
                }
                if (res?.total && isMounted.current) {
                    setTotalItems({
                        all: res?.total || 0,
                        enabled: res?.enabled || 0,
                        disabled: res?.disabled || 0,
                    });
                }
            }).catch(err => {
                setIsLoading(false);
                console.error(__('NotificationX Fetch Error: ', 'notificationx'), err);
            });
    }, [currentPage, perPage, status]);

    React.useEffect(() => {
        setFilteredNotice(
            nxHelper.filtered(notificationx, status)
        );
    }, [notificationx]);

    React.useEffect(() => {
        if (perPage === 0) return;
        builderContext.setRedirect({
            page      : `nx-admin`,
            status    : status,
            p         : currentPage,
            'per-page': perPage,
        });
    }, [perPage, currentPage]);

    useEffect(() => {
        // if current page is empty() go to prev page.
        if(filteredNotice.length == 0 && currentPage > 1){
            setCurrentPage(pp => --pp);
        }
    }, [filteredNotice])

    return (
        <>
            <div className="notificationx-items">
                <NotificationXItemsMenu
                    status={status}
                    perPage={perPage}
                    notificationx={notificationx}
                    updateNotice={setNotificationx}
                    totalItems={totalItems}
                    filteredNotice={filteredNotice}
                    setTotalItems={setTotalItems}
                    setCheckAll={setCheckAll}
                />

                <WrapperWithLoader isLoading={isLoading} div={false}>
                    {filteredNotice.length == 0 &&
                        <div className="nx-no-items">
                            <img src={LargeLogoIcon} />

                            {status == 'all'
                            ? <>
                                <h4>{__("No notifications are found.", 'notificationx')}</h4>
                                <p>
                                    {__(`Seems like you haven’t created any notification alerts.`, 'notificationx')}
                                    <br />
                                    {parse(sprintf(__(`Hit on %1$s"Add New"%2$s button to get started`, 'notificationx'), '<b>', '</b>'))}
                                </p>
                            </>
                            : status == 'enabled' ?
                            <>
                                <h4>{__("No notifications are enabled.", 'notificationx')}</h4>
                                <p>
                                    {__(`There’s no enabled Notification Alerts.`, 'notificationx')}
                                    <br />
                                    {parse(sprintf(__(`Simply use the toggle switch to turn your notifications from %1$s"All NotificationX"%2$s page.`, 'notificationx'), '<b>', '</b>'))}</p>
                            </>
                            : <>
                                <h4>{__("No notifications are disabled.", 'notificationx')}</h4>
                                <p>{__("There’s no disabled Notification Alerts.", 'notificationx')}</p>
                            </>
                            }
                        </div>
                    }
                    {filteredNotice.length > 0 &&
                    <>
                        <NotificationXInner
                            filteredNotice={filteredNotice}
                            setFilteredNotice={setFilteredNotice}
                            getNotice={notificationx}
                            updateNotice={setNotificationx}
                            totalItems={totalItems}
                            setTotalItems={setTotalItems}
                            checkAll={checkAll}
                            setCheckAll={setCheckAll}
                        />
                        <div className="nx-admin-items-footer">
                            <SelectControl
                                label="Show Notifications :"
                                value={perPage.toString()}
                                onChange={(p) => {
                                    setPerPage(parseInt(p));
                                    setCurrentPage(1);
                                }}
                                options={[
                                    { value: "10", label: __("10") },
                                    { value: "20", label: __("20") },
                                    { value: "50", label: __("50") },
                                    { value: "100", label: __("100") },
                                    { value: "200", label: __("200") },
                                ]}
                            />
                            <Pagination
                                current={currentPage}
                                onChange={setCurrentPage}
                                total={totalItems?.[status]}
                                pageSize={perPage}
                                itemRender={itemRender}
                                showTitle={false}
                                hideOnSinglePage
                                locale={localeInfo}
                            />
                        </div>
                    </>
                    }

                </WrapperWithLoader>
            </div>
        </>
    );
};

export default NotificationXItems;
