import React, { Suspense, useEffect, useState } from "react";
import {
    AnalyticsFilters,
    AnalyticsHeader,
    comparisonOptions,
    groupByNX,
    mergeByDate,
} from ".";
import { Header, WrapperWithLoader } from "../../components";
import nxHelper from "../../core/functions";
// @ts-ignore
import { __experimentalGetSettings, date } from "@wordpress/date";
import { __ } from "@wordpress/i18n";
import withDocumentTitle from "../../core/withDocumentTitle";
import { useNotificationXContext } from "../../hooks";

import { escapeHTML } from "@wordpress/escape-html";
import { lazyWithPreload } from "react-lazy-with-preload";

const Chart = lazyWithPreload(() => import("react-apexcharts"));


// import Chart from "react-apexcharts";
// const Chart = lazy(() => import('react-apexcharts'));

const Analytics = (props) => {
    const settings: any = __experimentalGetSettings();
    const builderContext = useNotificationXContext();
    const [isLoading, setIsLoading] = useState(false);

    const [posts, setPosts] = useState([]);
    const [rawData, setRawData] = useState([]);
    const [filterOptions, setFilterOptions] = useState<{
        nx: { label: string; value: string }[];
        comparison: { label: string; value: string }[];
        startDate: string;
        endDate: string;
    }>(null);

    const [data, setData] = useState({
        series: [],
        options: {
            chart: {
                type: "area",
                height: "auto",
                zoom: {
                    enabled: false,
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: "smooth",
            },
            labels: [],
            xaxis: {
                type: "date",
            },
            yaxis: {
                opposite: true,
                showForNullSeries: true,
                min: 0,
            },
            legend: {
                show: false,
                horizontalAlign: "center",
                position: "top",
            },
        },
    });

    useEffect(() => {
        if(builderContext?.analyticsRedirect){
            builderContext.setRedirect({
                page  : `nx-admin`,
            });
            return;
        }

        // // Preload the component when needed
        Chart.preload();

        // setIsLoading(true);
        // nxHelper.post("analytics/get", {
        //     ...filterOptions
        // }).then((res: any) => {
        //     if (res?.stats) {
        //         setRawData(res.stats);
        //         setPosts(res?.posts);
        //         setIsLoading(false);
        //     }
        // });
    }, []);

    useEffect(() => {
        if (filterOptions === null) return;

        setIsLoading(true);
        nxHelper.post("analytics/get", {
            startDate: filterOptions?.startDate,
            endDate  : filterOptions?.endDate,
        }).then((res: any) => {
            if (res?.stats) {
                setRawData(res.stats);
                setPosts(res?.posts);
                setIsLoading(false);
            }
        });
    }, [filterOptions?.startDate, filterOptions?.endDate])

    useEffect(() => {
        if (filterOptions === null) return;
        let series = [];
        let mergedSeries = [];
        let labels = [];
        const arr = ["all-combined", "all-separated"];
        const startDate = new Date(filterOptions?.startDate).getTime();
        const endDate = new Date(filterOptions?.endDate).getTime();
        const diff = (endDate - startDate) / 1000 / 24 / 60 / 60;
        const comparison =
            filterOptions?.comparison || Object.values(comparisonOptions);
        const isAllCombined = filterOptions.nx?.some((item) => {
            return item.value == "all-combined";
        });
        // const isAllSeparated = filterOptions.nx?.some((item) => {
        //     return item.value == "all-separated";
        // });

        if (
            !startDate ||
            !endDate ||
            !filterOptions.nx.length ||
            !comparison.length
        ) {
            // console.log("Please select the filters.");
            return;
        }

        for (let index = 0; index <= diff; index++) {
            labels.push(
                date(
                    settings.formats.date,
                    new Date(startDate + index * 1000 * 24 * 60 * 60),
                    settings.timezone.string
                )
            );
        }

        mergedSeries = rawData.filter((element) => {
            // filter by nx id.
            const hasNX = filterOptions.nx.some((val) => {
                if (arr.includes(val.value)) return true;
                return val.value == element.nx_id;
            });
            const iDate = new Date(element.created_at + "T00:00:00").getTime();
            // filter by date.
            if (hasNX && iDate >= startDate && iDate <= endDate) {
                return true;
            }
        });

        mergedSeries = mergedSeries.map((element) => {
            return {
                ...element,
                created_at: date(
                    settings.formats.date,
                    new Date(element.created_at),
                    settings.timezone.string
                ),
            };
        });

        // merge by date and group by nx_id.
        if (isAllCombined) {
            mergedSeries = [mergeByDate(mergedSeries)];
        } else {
            mergedSeries = groupByNX(mergedSeries).map((group) => {
                return mergeByDate(group);
            });
        }

        // generating data.series
        mergedSeries.forEach((group, index) => {
            for (let i = 0; i < comparison.length; i++) {
                const type = comparison[i];
                const name = type.value;
                let label = type.label;

                const data = labels.map((date) => {
                    if (group?.[date]) {
                        const element = group?.[date];
                        if (!isAllCombined && element?.nx_id) {
                            const nx = posts.find((current) => {
                                if (element?.nx_id == current?.nx_id) {
                                    return true;
                                }
                            });
                            if (nx?.title || nx?.nx_id)
                                label =
                                    type.label + " " + (escapeHTML(nx?.title) || nx?.nx_id);
                        }
                        switch (name) {
                            case "clicks":
                                return element.clicks;
                            case "views":
                                return element.views;
                            case "ctr":
                                return (element.views
                                    ? element.clicks / element.views
                                    : 0
                                ).toFixed(2);
                            default:
                                break;
                        }
                    }
                    return 0;
                });
                series.push({
                    name: label,
                    data: data,
                });
            }
        });

        setData({
            ...data,
            series: series,
            options: { ...data.options, labels: labels },
        });
    }, [rawData, filterOptions?.nx, filterOptions?.comparison]);

    return (
        <div>
            <Header addNew={true} />
            <AnalyticsHeader
                assetsURL={builderContext.assets}
                analytics={...builderContext?.analytics}
            />
            {builderContext?.is_pro_active && (
                <WrapperWithLoader isLoading={isLoading} div={false}>
                    <AnalyticsFilters
                        posts={posts}
                        filterOptions={filterOptions}
                        setFilterOptions={setFilterOptions}
                    />
                    <div className="nx-analytics-graph-wrapper">
                        <Suspense fallback={<div>Loading...</div>}>
                            <Chart
                                // @ts-ignore
                                options={data.options}
                                series={data.series}
                                type="area"
                                height={500}
                            />
                        </Suspense>
                    </div>
                </WrapperWithLoader>
            )}

            {!builderContext?.is_pro_active && (
                <div
                    className="analytics-display-area nx-stats-tease"
                    data-swal="true"
                >
                    <img
                        src={`${builderContext.assets.public}image/reports/graph.png`}
                        alt={__("Analytics Data", 'notificationx')}
                    />
                    <div className="nx-stats-pro-tease">
                        <a
                            href="http://wpdeveloper.com/in/upgrade-notificationx"
                            target="_blank"
                        >
                            <p>{__("Get PRO to Unlock", 'notificationx')}</p>
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
};
export default withDocumentTitle(Analytics, __("Analytics", 'notificationx'));
