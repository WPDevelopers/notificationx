import React, { useEffect, useState } from "react";
import Chart from "react-apexcharts";
import { Header } from "../../components";
import nxHelper from "../../core/functions";
import {
    AnalyticsFilters,
    comparisonOptions,
    AnalyticsHeader,
    mergeByDate,
    groupByNX,
} from ".";
// @ts-ignore
import { __experimentalGetSettings, date } from "@wordpress/date";
import { useNotificationXContext } from "../../hooks";
import withDocumentTitle from "../../core/withDocumentTitle";
import { Redirect } from "react-router";

const Analytics = (props) => {
    const settings: any = __experimentalGetSettings();
    const builderContext = useNotificationXContext();
    const [redirect, setRedirect] = useState(builderContext?.analyticsRedirect);
    console.log("builderContext", builderContext);

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
                height: 'auto',
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
                position: 'top'
            },
        },
    });

    useEffect(() => {
        nxHelper.get("analytics").then((res: any) => {
            if (res?.stats) {
                setRawData(res.stats);
                setPosts(res?.posts);
            }
        });
    }, []);

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
        const isAllCombined = filterOptions.nx.some((item) => {
            return item.value == "all-combined";
        });
        const isAllSeparated = filterOptions.nx.some((item) => {
            return item.value == "all-separated";
        });

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
                    settings.timezone.string,
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

        mergedSeries = mergedSeries.map(element => {
            return {
                ...element,
                created_at: date(
                    settings.formats.date,
                    new Date(element.created_at),
                    settings.timezone.string,
                )
            }
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
                                    type.label + " " + (nx?.title || nx?.nx_id);
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
    }, [rawData, filterOptions]);

    return (
        <div>
            {redirect && <Redirect to="/" />}
            <Header addNew={true} />
            <AnalyticsHeader assetsURL={builderContext.assets} analytics={...builderContext?.analytics} />
            {
                builderContext?.is_pro_active &&
                <>
                    <AnalyticsFilters
                        posts={posts}
                        filterOptions={filterOptions}
                        setFilterOptions={setFilterOptions}
                    />
                    <div className="nx-analytics-graph-wrapper">
                        <Chart
                            // @ts-ignore
                            options={data.options}
                            series={data.series}
                            type="area"
                            height={500}
                        />
                    </div>
                </>
            }
            {
                !builderContext?.is_pro_active &&
                <div className="analytics-display-area nx-stats-tease" data-swal="true">
                    <img src={`${builderContext.assets.admin}images/analytics/analytics-image.png`} alt="Analytics Data" />
                    <div className="nx-stats-pro-tease">
                        <a href="http://wpdeveloper.net/in/upgrade-notificationx" target="_blank">
                            <p>Get PRO to Unlock</p>
                        </a>
                    </div>
                </div>

            }
        </div>
    );
};
export default withDocumentTitle(Analytics, "Analytics");
