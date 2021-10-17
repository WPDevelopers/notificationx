import nxHelper from "../../core/functions";

const Analytics = (event, link, config) => {
    const nx_id = config?.nx_id;
    const enable_analytics = config?.enable_analytics;

    // @todo remove
    // event.preventDefault();

    if (!link) {
        event.preventDefault();
        return false;
    }
    if(!enable_analytics){
        return;
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
