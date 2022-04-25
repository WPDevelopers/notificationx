import React, { CSSProperties } from "react";
import classNames from "classnames";
import { Image, Content, Close } from "./helpers";
import moment from "moment";
// @ts-ignore
import { __experimentalGetSettings, gmdateI18n, date } from "@wordpress/date";
import { getThemeName } from "../../core/functions";

const Theme = (props) => {
    const splitThemes = ['theme-five', 'theme-six-free', 'conv-theme-nine', 'review-comment', 'page_analytics_pa-theme-two']
    const entry = props.data;
    const post = props.config;
    const themeName = getThemeName(post);
    const isSplit = splitThemes.includes(themeName);
    const isSplitCss = post?.advance_edit && isSplit;
    const settings: any = __experimentalGetSettings();

    // console.log("settings", post);
    // moment().utcOffset(settings?.timezone?.offset);

    // @todo check if adv template exists.
    let tmpl = post.template;
    // replace space with underscore inside {{}}

    let template = [];
    let regex = /{{(.*?)}}/g;
    tmpl?.forEach((row) => {
        if (!row) return;
        let cols = row.split(/\s+(?![^\{]*}})/);

        cols = cols.map((col) => {
            let match;
            while ((match = regex.exec(col))) {
                let key = match?.[1]?.replace("tag_", "")?.replace("product_", "");
                let val = entry?.[key] || '';

                if (key === "time") {
                    val =
                        entry?.updated_at &&
                        moment
                            .utc(entry?.updated_at)
                            .utcOffset(+settings?.timezone?.offset)
                            .fromNow();
                }
                else if (key == 'rating') {
                    val = `rating::${val}`;
                }
                col = col.replace(match?.[0], val);
            }
            return col;
        });
        template.push(cols);
    });

    // console.log(template);
    const componentClasses = classNames(
        "notificationx-inner",
        // `nx-notification-${themeName}`,
        // {
        //     "nx-has-close-btn": post?.close_button,
        //     "has-no-image": entry?.image_data === false,
        // },
        // // Themes >> Advanced Edit
        // {
        //     [`nx-customize-style-${post?.id}`]: post?.advance_edit,
        //     [`nx-img-${post?.image_position}`]: post?.advance_edit,
        //     [`nx-img-${post?.image_shape}`]: post?.advance_edit,
        //     "nx-flex-reverse":
        //         post?.advance_edit && post?.image_position === "right",
        // }
    );

    const componentCSS: CSSProperties = {};
    if (post?.advance_edit) {
        if (post.bg_color) componentCSS.backgroundColor = post.bg_color;
        if (post.text_color) componentCSS.color = post.text_color;
        if (+post.border && +post.border_size) {
            componentCSS.borderWidth = post.border_size;
            if (post.border_style) componentCSS.borderStyle = post.border_style;
            if (post.border_color) componentCSS.borderColor = post.border_color;
            // @todo
            // shadow post.bg_color;
            // shadow border - color;
        }
    }

    return (
        <div className={componentClasses} style={(post?.advance_edit && !isSplitCss) ? componentCSS : {}}>
            <Image {...props} theme={themeName} style={isSplitCss ? componentCSS : {}} isSplitCss={isSplitCss} isSplit={isSplit}/>
            <Content {...props} template={template} style={isSplitCss ? componentCSS : {}} themes = {themeName}/>
            <Close {...props} />
        </div>
    );
};

export default Theme;
