import classNames from "classnames";
import React from "react";
import { Close, Content, Image } from "./helpers";
// @ts-ignore
import { escapeHTML } from "@wordpress/escape-html";
import { useNotificationContext } from "../core";
import { getThemeName } from "../core/functions";

const Theme = (props) => {
    const splitThemes = [
        "theme-five",
        "theme-six-free",
        "conv-theme-nine",
        "review-comment",
        "page_analytics_pa-theme-two",
    ];
    const entry = props.data;
    const post = props.config;
    const themeName = getThemeName(post);
    const isSplit = splitThemes.includes(themeName);
    const isSplitCss = post?.advance_edit && isSplit;
    const frontendContext = useNotificationContext();

    // console.log("settings", post);
    // moment().utcOffset(settings?.timezone?.offset);

    // @todo check if adv template exists.
    let tmpl = post.template;
    // replace space with underscore inside {{}}

    let template = [];
    tmpl?.forEach((row) => {
        if (!row) return;
        let match;
        let _row = row;
        let regex = /{{(.*?)}}/g;
        while ((match = regex.exec(_row))) {
            let key = match?.[1]?.replace("tag_", "")?.replace("product_", "");
            let val = entry?.[key] || "";
            val = 'string' === typeof val ? escapeHTML(val) : val;

            if (key === "time") {
                val =
                    entry?.updated_at &&
                    frontendContext.getTime(entry?.updated_at).fromNow();
            } else if (key == "rating") {
                val = `rating::${val}`;
            }
            row = row.replace(match?.[0], val);
        }
        template.push(row);
    });

    // console.log(template);
    const componentClasses = classNames(
        "notificationx-inner",
        {
            "no-advance-edit": !post.advance_edit,
        }
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

    const componentCSS: any = {};
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
        <div
            className={componentClasses}
            style={post?.advance_edit && !isSplitCss ? componentCSS : {}}
        >
            <Image
                {...props}
                theme={themeName}
                style={isSplitCss ? componentCSS : {}}
                isSplitCss={isSplitCss}
                isSplit={isSplit}
            />
            <Content
                {...props}
                template={template}
                style={isSplitCss ? componentCSS : {}}
                themes={themeName}
            />
            <Close {...props} />
        </div>
    );
};

export default Theme;
