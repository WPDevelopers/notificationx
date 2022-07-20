export const getThemeName = (settings) => {
    let themeName = settings.themes.replace(settings.source + "_", "");
    themeName = themeName.replace(settings.type + "_", "");
    if (settings?.custom_type) {
        themeName = themeName.replace(settings?.custom_type + "_", "");
    }
    return themeName;
};

class NotificationXHelpers {
    getPath = (rest, path, query = {}) => {
        query = {...query, frontend: 'true'}
        const url = new URL(`${rest.root}${rest.namespace}/${path}`);
        for (var key in query) {
            if (!query.hasOwnProperty(key)) continue;
            url.searchParams.set(key, query[key]);
        }
        return url.toString();
    };
    post = (url, data = {}, args = {}) => {
        return fetch(url, {
            method: 'POST',
            credentials: 'omit',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
            ...args,
        })
        .then(response => response.json())
        .catch((err) => console.error(err));
    };
}

const nxHelper = new NotificationXHelpers();

export default nxHelper;
