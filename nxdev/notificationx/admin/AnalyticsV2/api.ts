import nxHelper from "../../core/functions";

/** Delete views, clicks and Audience data of one notification, or of all when nxId is 0. */
export const resetData = (nxId: number): Promise<any> =>
    nxHelper.delete(`analytics/report/data?nx_id=${encodeURIComponent(nxId || 0)}`);
