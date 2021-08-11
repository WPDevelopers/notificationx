export const mergeByDate = (series) => {
    let obj = {};
    series.forEach((element) => {
        let prev = obj?.[element.created_at] || {
            views: 0,
            clicks: 0,
        };
        obj[element.created_at] = {
            views: Number(prev.views) + Number(element.views),
            clicks: Number(prev.clicks) + Number(element.clicks),
            created_at: element.created_at,
            nx_id: element?.nx_id,
        };
    });
    return obj;
};

export const groupByNX = (series): Array<Array<{ nx_id: number }>> => {
    let obj = {};
    series.forEach((element) => {
        if (!obj.hasOwnProperty(element.nx_id)) {
            obj[element.nx_id] = [];
        }
        obj[element.nx_id].push(element);
    });
    return Object.values(obj);
};
