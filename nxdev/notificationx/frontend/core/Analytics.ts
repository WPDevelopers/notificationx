const Analytics = (event, link, config, context?) => {
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


    let url = context.rest.root + context.rest.namespace + '/analytics/?frontend=true';


    fetch(url, {
        // mode: 'no-cors',
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nx_id
        })
    })
    .then((response) => {
        // console.log("response: ", response);
    })
    .catch((err) => console.error("Fetch Error: ", err));
};

export default Analytics;
