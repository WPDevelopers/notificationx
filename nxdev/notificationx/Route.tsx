import React, { useEffect, useState } from "react";
import { matchPath, useHistory } from "react-router";

import {
    Admin,
    AddNewNotification,
    EditNotification,
    Settings,
    Analytics,
    QuickBuild,
    Dashboard,
} from "./admin/index";

function Route(props) {
    const [Location, setLocation] = useState(location.search);
    const [matchedRoutes, setMatchedRoutes] = useState([]);

    const routes = [
        {
            path: "nx-dashboard",
            component: Dashboard,
            exact: true,
        },
        {
            path: "nx-admin",
            component: Admin,
            exact: true,
        },
        {
            path: "nx-edit",
            component: AddNewNotification,
            exact: true,
        },
        {
            path: "nx-edit/:edit",
            component: EditNotification,
            exact: true,
        },
        {
            path: "nx-settings",
            component: Settings,
            exact: true,
        },
        {
            path: "nx-analytics",
            component: Analytics,
            exact: true,
        },
        {
            path: "nx-builder",
            component: QuickBuild,
            exact: true,
        },
        {
            component: <></>,
        },
    ];
    let history = useHistory();
    history.listen(() => {
        setLocation(location.search);
    });

    useEffect(() => {
        const searchParams = new URLSearchParams(history.location.search);
        const page = searchParams.get("page");
        const id = searchParams.get("id");
        const pathname = page + (id ? `/${id}` : "");
        const _matchedRoutes = [];

        for (let i = 0; i < routes.length; i += 1) {
            const { component: RouteComponent, ...rest } = routes[i];

            const match = matchPath(pathname, { ...rest });

            if (match) {
                _matchedRoutes.push(
                    // @ts-ignore
                    <RouteComponent key={RouteComponent} {...props} match={match} />
                );

                if (match.isExact) {
                    break;
                }
            }
        }
        setMatchedRoutes(_matchedRoutes);
    }, [Location]);

    let getSiblings = function (e) {
        // for collecting siblings
        let siblings = [];
        // if no parent, return no sibling
        if(!e.parentNode) {
            return siblings;
        }
        // first child of the parent node
        let sibling  = e.parentNode.firstChild;
        // collecting siblings
        while (sibling) {
            if (sibling.nodeType === 1 && sibling !== e) {
                siblings.push(sibling);
            }
            sibling = sibling.nextSibling;
        }
        return siblings;
    };

    const handleClick = function(e: Event){
        e.preventDefault();
        const path   = this.getAttribute('href');
        if(path){
            history.push(path);
            const selected: Element = this.parentNode;
            let siblings = getSiblings(selected);

            siblings.forEach(element => {
                element.classList.remove("current");
            });
            selected.classList.add('current');
        }
    }

    useEffect(() => {
        const nx = document.querySelectorAll('#toplevel_page_nx-admin a');
        nx.forEach(element => {
            element.addEventListener('click', handleClick);
        });

        return () => {
            nx.forEach(element => {
                element.removeEventListener('click', handleClick);
            });
        }
    }, [])



    return (
        <>
            {matchedRoutes.map((item) => {
                return item;
            })}
        </>
    );

}

export default Route;
