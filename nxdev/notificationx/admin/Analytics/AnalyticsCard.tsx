import React from 'react'
import { NavLink } from 'react-router-dom'

const AnalyticsCard = ({ type, url, icon, title, count, is_pro }) => {

    return (
        <div>
            <div className={`nx-analytics-counter ${ !is_pro ? 'disabled' : '' }`}>
                <NavLink 
                    to={ is_pro ? {
                            pathname: '/admin.php',
                            search: "?page=nx-analytics&comparison=" + type,
                        } : {
                            pathname: '/#',
                        }
                    }
                    onClick={(e) => {
                        if (!is_pro) {
                            e.preventDefault();
                        }
                    }}
                >
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