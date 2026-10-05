import nxHelper from "../../core/functions";

const qs = (params: Record<string, any>) =>
    Object.keys(params)
        .filter((k) => params[k] !== undefined && params[k] !== null && params[k] !== "")
        .map((k) => `${encodeURIComponent(k)}=${encodeURIComponent(params[k])}`)
        .join("&");

export const getSummary = (range: string, nxId: number, compare: boolean, type = ""): Promise<any> =>
    nxHelper.get(`analytics/report/summary?${qs({ range, nx_id: nxId || 0, compare: compare ? 1 : 0, type })}`);

export const getNotifications = (range: string): Promise<any> =>
    nxHelper.get(`analytics/report/notifications?${qs({ range })}`);

export const getNotification = (id: number, range: string): Promise<any> =>
    nxHelper.get(`analytics/report/notification/${id}?${qs({ range })}`);

export const getAudience = (range: string, nxId: number, type = ""): Promise<any> =>
    nxHelper.get(`analytics/report/audience?${qs({ range, nx_id: nxId || 0, type })}`);

/** Delete views, clicks and Audience data of one notification, or of all when nxId is 0. */
export const resetData = (nxId: number): Promise<any> =>
    nxHelper.delete(`analytics/report/data?${qs({ nx_id: nxId || 0 })}`);

export const getExport = (module: string, range: string, nxId: number): Promise<any> =>
    nxHelper.get(`analytics/report/export?${qs({ module, range, nx_id: nxId || 0 })}`);

export const getLeadsReport = (range: string, nxId: number, type = "", compare = false): Promise<any> =>
    nxHelper.get(`analytics/report/leads?${qs({ range, nx_id: nxId || 0, type, compare: compare ? 1 : 0 })}`);

export const getLeads = (page: number, search: string): Promise<any> =>
    nxHelper.get(`feedback-entries?${qs({ page, per_page: 20, s: search })}`);

/** Save a CSV string returned by the export endpoint. */
export const downloadCsv = (filename: string, csv: string) => {
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
};
