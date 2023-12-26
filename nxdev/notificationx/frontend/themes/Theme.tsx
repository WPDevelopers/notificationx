import classNames from "classnames";
import React from "react";
import { Close, Content, Image } from "./helpers";
// @ts-ignore
import { escapeHTML } from "@wordpress/escape-html";
import { useNotificationContext } from "../core";
import { getThemeName } from "../core/functions";
import { __, _x } from "@wordpress/i18n";
import Button from "./helpers/Button";

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
                const suffix = ['announcements'].includes(post.source);
                val =
                    entry?.updated_at &&
                    frontendContext.getTime(entry?.updated_at).fromNow(suffix);
                val += suffix ? _x(" remaining", "Announcements: 5 days remaining", 'notificationx') : "";
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
    const announcementCSS: any = {};
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
        // Add announcementCSS
        if (post.discount_text_color) announcementCSS.discountTextColor = post.discount_text_color;
        if (post.discount_background) announcementCSS.discountBackground = post.discount_background;
        if (post.link_button_bg_color) announcementCSS.linkButtonBgColor = post.link_button_bg_color;
        if (post.link_button_font_size) announcementCSS.linkButtonFontSize = post.link_button_font_size;
        if (post.link_button_text_color) announcementCSS.linkButtonTextColor = post.link_button_text_color;
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
                announcementCSS={announcementCSS}
            />
            { ["announcements_theme-13"].includes(props?.config?.themes) && 
                <Button
                    {...props}
                    announcementCSS={announcementCSS}
                    icon={true}
                />
            }
            <Content
                {...props}
                template={template}
                style={isSplitCss ? componentCSS : {}}
                themes={themeName}
                isSplitCss={isSplitCss}
                isSplit={isSplit}
                announcementCSS={announcementCSS}
            />
            { ["announcements_theme-15"].includes(props?.config?.themes) && 
                <Button
                    {...props}
                    announcementCSS={announcementCSS}
                />
            }
            <Close {...props} />
        </div>
    );
};

export default Theme;
