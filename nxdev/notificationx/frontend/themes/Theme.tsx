import classNames from "classnames";
import React, { useState } from "react";
import { Close, Content, Image } from "./helpers";
// @ts-ignore
import { escapeHTML } from "@wordpress/escape-html";
import { useNotificationContext } from "../core";
import { getResThemeName, getThemeName } from "../core/functions";
import { __, _x } from "@wordpress/i18n";
import Button from "./helpers/Button";
import Cookies from 'js-cookie';

const Theme = (props) => {
    let splitThemes = [
        "theme-five",
        "theme-six-free",
        "conv-theme-nine",
        "review-comment",
        "page_analytics_pa-theme-two",
    ];
    splitThemes = splitThemes.concat(props?.splitThemes);
    const entry = props.data;
    const post = props.config;
    const themeName = getThemeName(post);
    const resThemeName = getResThemeName(post) || null;
    const isSplit = splitThemes.includes(themeName) || splitThemes.includes(resThemeName);
    const isSplitCss = post?.advance_edit && isSplit;
    const frontendContext = useNotificationContext();

    const [randomNumber, setRandomNumber] = useState(null);

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
            // Escape EVERY value, not just string-typed ones. A value supplied
            // as a one-element array (e.g. name => ["<img src=x onerror=…>"])
            // would otherwise skip escapeHTML, then get coerced back to its raw
            // string by row.replace() below and injected via
            // dangerouslySetInnerHTML — a stored XSS (Patchstack, NX Pro
            // <= 3.1.3). String(val) collapses arrays/objects/numbers to a
            // string first so nothing bypasses the escape.
            val = escapeHTML(String(val));

            if (key === "time") {
                // Some sources never give us a real timestamp. Facebook renders
                // "2 weeks ago" and nothing more, so the date we store for those
                // reviews is reconstructed and accurate to about a day. When a
                // source supplies its own label we show that instead of our
                // computed relative time, which would otherwise claim a
                // precision we do not have — and, for a review with no date at
                // all, would announce a years-old review as "a few seconds ago".
                if (entry?.time_label) {
                    val = escapeHTML(String(entry.time_label));
                } else {
                    const suffix = ['announcements'].includes(post.source);
                    val =
                        entry?.updated_at &&
                        frontendContext.getTime(entry?.updated_at).fromNow(suffix);
                    val += suffix ? _x(" remaining", "Announcements: 5 days remaining", 'notificationx') : "";
                }
            } else if (key == "rating") {
                // Only mark it as a rating when there actually is one. The
                // renderer's pattern needs digits to match, so a source with no
                // star rating — a Facebook recommendation, or a review whose
                // stars were never captured — would otherwise leave the literal
                // text "rating::" visible in the notification.
                const stars = Number(val);
                val = val !== "" && Number.isFinite(stars) && stars > 0 ? `rating::${val}` : "";
            } else if (key === "city_country") {
                // Join city + country with a comma only when both exist, so an
                // order missing one (or both) never renders a stray ", ". Prefer a
                // value the source already composed (e.g. the builder preview).
                val =
                    entry?.city_country ||
                    [entry?.city, entry?.country].filter(Boolean).join(", ");
            } else if (key.includes('random:')) {
                /*
                The key string format is "random:min-max:range:expiry", where:
                - "random" is a fixed string.
                - "min" is the minimum value for the random number.
                - "max" is the maximum value for the random number.
                - "range" (optional) is the range within which the new random number should be close to a previously stored cookie value. If not provided, it defaults to 10.
                - "expiry" (optional) is the expiry time in minutes for the cookie. If not provided, it defaults to 15 minutes.

                The generated random number is then stored in a cookie named 'randomNumber' with an expiry time specified by 'expiry'. The random number is also set to a state variable using 'setRandomNumber(val)'.
                */

                let matches = key.match(/(random):(\d+)-(\d+)(?::(\d+))?(?::(\d+))?/);
                let cookieValue = Cookies.get('randomNumber');

                if (randomNumber) {
                    val = randomNumber;
                } else if (matches && matches[2] && matches[3]) {
                    let min = parseInt(matches[2]);
                    let max = parseInt(matches[3]);
                    let range = matches[4] ? parseInt(matches[4]) : 10; // Use the optional range if it's present, otherwise default to 10
                    let expiry = matches[5] ? parseInt(matches[5]) : 15; // Use the optional expiry if it's present, otherwise default to 15 minutes

                    val = Math.floor(Math.random() * (max - min + 1)) + min;
                    if (cookieValue) {
                        // Generate a random number close to the cookie value
                        while (Math.abs(val - parseInt(cookieValue)) > range) {
                            val = Math.floor(Math.random() * (max - min + 1)) + min;
                        }
                    }

                    setRandomNumber(val);
                    Cookies.set('randomNumber', val.toString(), { expires: expiry / (24 * 60) }); // Cookie will expire after expiry minutes
                    // console.log('views', key, val);
                }
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
            {["announcements_theme-13"].includes(props?.config?.themes) &&
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
            {["announcements_theme-15"].includes(props?.config?.themes) &&
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
