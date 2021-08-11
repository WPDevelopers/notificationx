import React, { useEffect, useRef, useState } from "react";
import nxHelper from "../core/functions";
import { isArray } from "../../form-builder/src/core/utils";
import NotificationXInner from "./NotificationXInner";
import NotificationXItemsMenu from "./NotificationXItemsMenu";
import { Redirect, useLocation } from "react-router";
import Pagination from "rc-pagination";
import { Link } from "react-router-dom";
import NavLink from "../components/NavLink";
import { SelectControl } from "@wordpress/components";
import { WrapperWithLoader } from "../components";

export const NotificationXItems = (props) => {
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
    const [redirect, setRedirect] = useState<string>();
    const location = useLocation();

    const getParam = (param, d?) => {
        const query = nxHelper.useQuery(location.search);
        return query.get(param) || d;
    };
    const status = getParam("status", "all");

    const itemRender = (current, type, element) => {
        return <NavLink status={status} current={current} perPage={perPage}>{current}</NavLink>;
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
                console.error('NotificationX Fetch Error: ', err);
            });
    }, [currentPage, perPage, status]);

    React.useEffect(() => {
        setFilteredNotice(
            nxHelper.filtered(notificationx, status)
        );
    }, [notificationx]);

    React.useEffect(() => {
        if (perPage === 0) return;
        setRedirect(`/?status=${status}&per-page=${perPage}&p=${currentPage}`);
    }, [perPage]);

    return (
        <>
            {
                redirect && <Redirect to={redirect} />
            }
            <div className="notificationx-items">
                <NotificationXItemsMenu
                    status={status}
                    perPage={perPage}
                    notificationx={notificationx}
                    totalItems={totalItems}
                />

                <WrapperWithLoader isLoading={isLoading} div={false}>
                    <NotificationXInner
                        filteredNotice={filteredNotice}
                        getNotice={notificationx}
                        updateNotice={setNotificationx}
                        totalItems={totalItems}
                        setTotalItems={setTotalItems}
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
                                { value: "10", label: "10" },
                                { value: "20", label: "20" },
                                { value: "50", label: "50" },
                                { value: "100", label: "100" },
                                { value: "200", label: "200" },
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
                        />
                    </div>
                </WrapperWithLoader>
            </div>
        </>
    );
};

export default NotificationXItems;