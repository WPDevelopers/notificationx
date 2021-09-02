import React from 'react';
import { Link } from 'react-router-dom';

const NavLink = (props) => {
    const status  = props?.status || "all";
    const current = props?.current || 1;
    const perPage = props?.perPage || 20;
    return (<Link to={{
        pathname: '/admin.php',
        search: `?page=nx-admin&status=${status}&per-page=${perPage}&p=${current}`,
    }}>{props.children}</Link>);
}

export default NavLink;