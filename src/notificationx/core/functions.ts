import apiFetch from "@wordpress/api-fetch";
import Swal from 'sweetalert2';

/**
 * apiFetch setup
 */
// apiFetch.use(apiFetch.createNonceMiddleware(api_nonce));
// apiFetch.use(apiFetch.createRootURLMiddleware(rest_url));

class NotificationXHelpers {
    namespace = "/notificationx";
    version = "v1";
    getPath = (path) => {
        return `${this.namespace}/${this.version}/${path}`;
    };
    post = (endpoint, data = {}, args = {}) => {
        let path = this.getPath(endpoint);
        args = { path, method: "POST", data, ...args };
        return apiFetch(args)
            .then((res) => res)
            .catch((err) => console.error(err));
    };
    delete = (endpoint, data = {}, args = {}) => {
        let path = this.getPath(endpoint);
        args = { path, method: "DELETE", data, ...args };
        return apiFetch(args)
            .then((res) => res)
            .catch((err) => console.error(err));
    };
    get = (endpoint, args = {}) => {
        let path = this.getPath(endpoint);
        args = { path, method: "GET", ...args };
        return apiFetch(args)
            .then((res) => res)
            .catch((err) => console.error(err));
    };
    // getData: (args) => {
    //     apiFetch({
    //         path: "/notificationx/v1/get-data",
    //         method: "POST",
    //         data: args?.data,
    //     })
    //         .then((res) => {
    //             console.log("res", res);
    //         })
    //         .catch((err) => console.error(err));
    // },
    useQuery = (search) => {
        search = search;
        return new URLSearchParams(search);
    };
    filtered = (notices, status) => {
        return notices.filter((val) => {
            switch (status) {
                case "enabled":
                    return !!val?.enabled;
                    break;
                case "disabled":
                    return !val?.enabled;
                    break;
                case "all":
                default:
                    return true;
                    break;
            }
        });
    };
    swal = ({confirmedCallback, completeAction, completeArgs, afterComplete, ...args}) => {
        Swal.fire(args).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                confirmedCallback().then((res) => {
                    if (res?.success) {
                        completeAction(res);
                        Swal.fire(completeArgs).then(afterComplete);
                    }
                })
                .catch((err) => console.error("Delete Error: ", err));
            }
        });
    }
}

const nxHelper = new NotificationXHelpers();

export const SweetAlert = ( args: any = {} ) => {
	return Swal.mixin({
		target: args?.target ?? "#notificationx",
		type: args?.type ?? "success",
		html: args?.html,
		title: args?.title ?? "Title Goes Here: title",
		text: args?.text ?? "Test Goes Here: text",
		icon: args?.icon ?? (args?.type || "success"),
		timer: args?.timer ?? null,
		...args,
	});
};

export const getThemeName = (settings) => {
    let themeName = settings.themes.replace(settings.source + "_", "");
    themeName = themeName.replace(settings.type + "_", "");
    if (settings?.custom_type) {
        themeName = themeName.replace(settings?.custom_type + "_", "");
    }
    return themeName;
}

export const proAlert = ( html = null ) => {
    if( html === null ) {
        html = "You need to upgrade to the <strong><a href='http://wpdeveloper.net/in/upgrade-notificationx' target='_blank'>Premium Version</a></strong> to use this feature.";
    }
    return SweetAlert({
        showConfirmButton: false,
        showDenyButton: true,
        type: 'warning',
        title: 'Opps...',
        customClass: { actions: 'nx-pro-alert-actions' },
        denyButtonText: 'Close',
        html
    });
}

export default nxHelper;