import { getThemeName } from "../core/functions";

const GetTemplate = (settings) => {
    const themeName = getThemeName(settings);
    // @todo
    const defaults = {
        first_param: '',
        second_param: '',
        third_param: '',
        fourth_param: '',
        fifth_param: '',
        sixth_param: '',
        map_fourth_param: '',
        ga_fourth_param: '',
        ga_fifth_param: '',
        review_fourth_param: '',
    }
    const params = {...defaults, ...settings?.["notification-template"]};

    for (const param in params) {
        if (Object.hasOwnProperty.call(params, param)) {
            let element = params[param] || '';
            if (element == "tag_custom" && params?.["custom_" + param]) {
                // getting value of custom params.
                element = params?.["custom_" + param] || '';
            }
            if (element == 'tag_siteview' || element == 'tag_realtime_siteview') {
                params[param] = "{{views}}";
            } else if (element == 'ga_title') {
                params[param] = "{{title}}";
            }else if (element.indexOf("tag_") === 0) {
                params[param] = "{{" + element.replace("tag_", "") + "}}";
            } else if (element.indexOf("product_") === 0) {
                params[param] = "{{" + element.replace("product_", "") + "}}";
            } else {
                params[param] = element || '';
            }
        }
    }
    console.log(settings.themes);

    switch (settings.themes) {
        case "donation_theme-one":
        case "donation_theme-two":
        case "donation_theme-three":
        case "donation_theme-four":
        case "donation_theme-five":
            return [
                `${params?.first_param} ${params?.second_param} ${params?.third_param}`,
                `${params?.fourth_param}`,
                `${params?.fifth_param}`,
            ];
        case "donation_conv-theme-seven":
        case "donation_conv-theme-eight":
        case "donation_conv-theme-nine":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `in ${params?.third_param} ${params?.fourth_param}`,
            ];
        case "google_reviews_maps_theme":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param}`,
                `${params?.map_fourth_param}`,
            ];
    }



    switch (themeName) {
        case "theme-one":
        case "theme-two":
        case "theme-three":
        case "theme-four":
        case "theme-five":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
            break;
        // conversion start
        case "conv-theme-six":
            return [
                `${params?.first_param} ${params?.second_param} ${params?.third_param}`,
                `${params?.map_fourth_param} ${params?.fourth_param}`,
                `${params?.fifth_param}`,
            ];
            break;
        case "conv-theme-seven":
        case "conv-theme-eight":
        case "conv-theme-nine":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param} ${params?.fourth_param}`,
            ];
            break;
        // conversion end

        // comments theme start.
        case "theme-six-free":
        case "theme-seven-free":
        case "theme-eight-free":
        // review themes
        case "review-comment":
        case "review-comment-2":
        case "review-comment-3":
            return [
                `${params?.first_param} ${params?.second_param} `,
                `${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
            break;
        // comments theme end.

        // reviews theme start.
        case "total-rated":
        case "reviewed":
            return [
                `${params?.first_param} ${params?.second_param} `,
                `${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
        case "review_saying":
            return [
                `${params?.first_param} ${params?.second_param} ${params?.third_param} ${params?.review_fourth_param}`,
                `${params?.fifth_param}`,
                `${params?.sixth_param}`,
            ];
            break;
        // reviews theme end.
        // start download stats
        case "today-download":
            return [
                `${params?.first_param} `,
                `${params?.second_param} ${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
        case "7day-download":
            return [
                `${params?.first_param} `,
                `${params?.second_param} ${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
        case "actively_using":
            return [
                `${params?.first_param} ${params?.second_param} `,
                `${params?.third_param}`,
            ];
        case "total-download":
            return [
                `${params?.first_param} `,
                `${params?.second_param} ${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
        // end download stats

        case "maps_theme":
            return [
                `${params?.first_param} ${params?.second_param} ${params?.third_param} ${params?.map_fourth_param}`,
                `${params?.fourth_param}`,
                `${params?.fifth_param}`,
            ];
            break;
        // PA
        case "pa-theme-one":
            return [
                `${params?.first_param}`,
                `${params?.second_param} ${params?.third_param} ${params?.ga_fourth_param} ${params?.ga_fifth_param} ${params?.sixth_param}`,
            ];
        case "pa-theme-two":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param} ${params?.ga_fourth_param} ${params?.ga_fifth_param} ${params?.sixth_param}`,
            ];
        case "pa-theme-three":
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param} ${params?.ga_fourth_param}`,
            ];
        case 'stock-theme-one':
        case 'stock-theme-two':
            return [
                `${params?.second_param} ${params?.third_param} ${params?.fourth_param} ${params?.fifth_param}`
            ];
            break;
        default:
            console.error("Please select a theme", settings);
            return [
                `${params?.first_param} ${params?.second_param}`,
                `${params?.third_param}`,
                `${params?.fourth_param}`,
            ];
            break;
    }
};

export default GetTemplate;
