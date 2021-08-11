import nxHelper from "../../core/functions";

const Analytics = (event, props) => {
    const nx_id = props?.data?.nx_id;
    const entry_id = props?.data?.entry_id;
    const link = props?.data?.link;
    const rest = window["notificationX"]?.rest;

    // @todo remove
    // event.preventDefault();

    if (!link) {
        event.preventDefault();
        return false;
    }

    nxHelper
        .post("analytics/?frontend=true", {
            nx_id,
            // entry_id,
            // link,
            // referrer: window.location.toString( ),
        })
        .then((response) => {
            // console.log("response: ", response);
        })
        .catch((err) => console.error("Fetch Error: ", err));
    // apiFetch({
    //     path: rest?.namespace + "/analytics/?frontend=true",
    //     data: {
    //         nx_id,
    //         // entry_id,
    //         // link,
    //         // referrer: window.location.toString( ),
    //     },
    //     method: "POST",
    // })
};

export default Analytics;
