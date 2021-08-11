import React from 'react'
import { NavLink } from 'react-router-dom'

const AnalyticsCard = ({ type, url, icon, title, count }) => {

    return (
        <div>
            <div className="nx-analytics-counter">
                <NavLink to={"/analytics/?comparison=" + type}>
                    <>
                        <span className="nx-counter-icon">
                            <img src={icon} alt={title} />
                        </span>
                        <div>
                            <span className="nx-counter-number">
                                {count || 0}
                            </span>
                            <span className="nx-counter-label">{title}</span>
                        </div>
                    </>
                </NavLink>
            </div>
        </div>
    );
}

export default React.memo(AnalyticsCard);