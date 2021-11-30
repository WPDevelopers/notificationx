import React, { useEffect } from "react";
import Select from "react-select";
import { useBuilderContext, Date as DateControl } from "quickbuilder";
// @ts-ignore
import { __experimentalGetSettings, date } from "@wordpress/date";
import { useLocation } from "react-router";
import nxHelper from "../../core/functions";
import { __ } from "@wordpress/i18n";
import { getTime } from "../../frontend/core/utils";
import { useNotificationXContext } from "../../hooks";

export const comparisonOptions = {
    views: {
        value: "views",
        label: __("Views", 'notificationx'),
    },
    clicks: {
        value: "clicks",
        label: __("Clicks", 'notificationx'),
    },
    ctr: {
        value: "ctr",
        label: __("CTR", 'notificationx'),
    },
};

const AnalyticsFilters = ({ posts, filterOptions, setFilterOptions }) => {
    const settings: any = __experimentalGetSettings();
    const builderContext = useNotificationXContext();

    let options: { label: string; value: string }[] = posts?.map((item) => {
        return {
            value: item?.nx_id,
            label: item?.title || item?.nx_id,
        };
    });
    options = [
        {
            value: "all-combined",
            label: __("All Combined", 'notificationx'),
        },
        {
            value: "all-separated",
            label: __("All Separated", 'notificationx'),
        },
        ...options,
    ];

    const nxChange = (values, options) => {
        if (options?.action == "select-option") {
            const arr = ["all-combined", "all-separated"];

            if (arr.includes(options?.option?.value)) {
                values = values.filter((val, i) => {
                    return val?.value == options?.option?.value;
                });
            } else {
                values = values.filter((val, i) => {
                    if (arr.includes(val?.value)) return false;
                    return true;
                });
            }
        }
        const sortedValue = values.sort((a, b) => {
            if (a.value.includes("all-")) return -1;
            if (b.value.includes("all-")) return 1;
            return Number(a.value) - Number(b.value);
        });
        setFilterOptions({
            ...filterOptions,
            nx: sortedValue,
        });
    };

    const onValueChange = ({ target }) => {
        setFilterOptions({
            ...filterOptions,
            [target.name]: target.value,
        });
        builderContext.setRedirect({
            page  : `nx-analytics`,
        });
    };

    const location = useLocation();
    const query = nxHelper.useQuery(location.search);

    const getNX = () => {
        let nx = query.get("nx");
        if(nx){
            return options.filter(item => {
                return item.value == nx;
            });
        }
        return null;
    };
    function shallowEqual(object1, object2) {
        const keys1 = Object.keys(object1);
        const keys2 = Object.keys(object2);
        if (keys1.length !== keys2.length) {
            return false;
        }
        for (let key of keys1) {
            if (object1[key] !== object2[key]) {
                return false;
            }
        }
        return true;
    }
    useEffect(() => {
        const selectedComparison = query.get("comparison");
        let comparison = Object.values(comparisonOptions);
        if (selectedComparison) {
            comparison = [comparisonOptions?.[selectedComparison]];
        }
        if (filterOptions === null) {
            setFilterOptions({
                nx: getNX() || [options?.[0]],
                comparison: comparison,
                startDate: new Date(Date.now() - 6 * 24 * 60 * 60 * 1000),
                endDate: new Date(),
            });
        }
        else {
            // making sure
            if((getNX() && !shallowEqual(getNX(), filterOptions.nx)) || (selectedComparison && !shallowEqual(filterOptions.comparison, comparison))){
                setFilterOptions({
                    ...filterOptions,
                    nx: getNX() || filterOptions.nx,
                    comparison: comparison,
                });
            }
        }
    }, [location]);

    return (
        <div className="nx-analytics-filter-wrapper">
            <div className="nx-analytics-filter">
                <Select
                    options={options}
                    isMulti={true}
                    value={filterOptions?.nx}
                    onChange={nxChange}
                    className="nx-analytic-select-wrapper"
                    classNamePrefix="analytics-select"
                />
                <DateControl
                    name="startDate"
                    type="date"
                    value={filterOptions?.startDate}
                    onChange={onValueChange}
                    format={settings.formats.date}
                />
                <DateControl
                    name="endDate"
                    type="date"
                    value={filterOptions?.endDate}
                    onChange={onValueChange}
                    format={settings.formats.date}
                />
                <Select
                    options={Object.values(comparisonOptions)}
                    isMulti={true}
                    value={filterOptions?.comparison}
                    className="nx-analytic-select-wrapper"
                    classNamePrefix="analytics-select"
                    onChange={(val) => {
                        onValueChange({
                            target: {
                                type: "select",
                                name: "comparison",
                                value: val,
                            },
                        });
                    }}
                />
            </div>
        </div>
    );
};

export default AnalyticsFilters;
