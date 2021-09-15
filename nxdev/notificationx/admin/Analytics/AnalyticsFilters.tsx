import React, { useEffect, useState } from "react";
import Select from "react-select";
import { useBuilderContext } from "../../../form-builder";
import DateControl from "../../../form-builder/src/fields/Date";
import AnalyticsCard from "./AnalyticsCard";
// @ts-ignore
import { __experimentalGetSettings, date } from "@wordpress/date";
import { useLocation } from "react-router";
import nxHelper from "../../core/functions";
import { __ } from "@wordpress/i18n";

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
            [target.name]:
                target.type == "date"
                    ? date(settings.formats.date, target.value, settings.timezone.string)
                    : target.value,
        });
    };

    const location = useLocation();

    const getComparison = () => {
        const query = nxHelper.useQuery(location.search);
        return query.get("comparison");
    };

    useEffect(() => {
        const selectedComparison = getComparison();
        let comparison = comparisonOptions.views;
        if (selectedComparison) {
            comparison = comparisonOptions?.[selectedComparison];
        }
        if (filterOptions === null) {
            setFilterOptions({
                nx: [options?.[0]],
                comparison: [comparison],
                startDate: date(
                    settings.formats.date,
                    new Date(Date.now() - 6 * 24 * 60 * 60 * 1000),
                    settings.timezone.string
                ),
                endDate: date(settings.formats.date, new Date(), settings.timezone.string),
            });
        }
        else {
            setFilterOptions({
                ...filterOptions,
                comparison: [comparison],
            });
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
                    value={filterOptions?.startDate}
                    onChange={onValueChange}
                    format={settings.formats.date}
                />
                <DateControl
                    name="endDate"
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
