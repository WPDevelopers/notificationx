import React from "react";
import { Branding as NXSvg, ThemeFiveShape } from ".";
import Star from "../../../icons/Star";

const Content = (props) => {
    const { config } = props;
    let rowClasses = ["nx-first-row", "nx-second-row", "nx-third-row"];
    let themeFiveShapeStyle = {};
    if (props.style) {
        themeFiveShapeStyle = { fill: props.style?.backgroundColor };
    }

    let template = props.template;
    if (props.template.length < 3) {
        props.template.push([]);
    }

    let content = props.template.map((row, i) => {
        const rowStyle: any = {};
        let advTmplRatingRow;
        if (config.advance_edit) {
            if (i == 0 && config.first_font_size)
                rowStyle.fontSize = `${config.first_font_size}px`;
            if (i == 1 && config.second_font_size)
                rowStyle.fontSize = `${config.second_font_size}px`;
            if (i == 2 && config.third_font_size)
                rowStyle.fontSize = `${config.third_font_size}px`;
            if (config.text_color) rowStyle.color = config.text_color;
        }

        const rating = /rating::(([0-9]*[.])?[0-9]+)/.exec(row);
        if (rating?.[1]) {
            let i = 0;
            const _row = row
                .replace(/(<([^>]+)>)/gi, "")
                .split(`rating::${rating[1]}`);
            advTmplRatingRow = [];
            if (_row[0])
                advTmplRatingRow.push(
                    <span dangerouslySetInnerHTML={{ __html: _row[0] }}></span>
                );
            advTmplRatingRow.push(
                <span key={Math.random()}>
                    <Star star={parseFloat(rating[1])} />
                </span>
            );
            if (_row[1])
                advTmplRatingRow.push(
                    <span
                        key={Math.random()}
                        dangerouslySetInnerHTML={{ __html: _row[1] }}
                    />
                );
        }

        return (
            <p key={i} className={rowClasses[i]} style={rowStyle}>
                {advTmplRatingRow && advTmplRatingRow}
                {!advTmplRatingRow && (
                    <span dangerouslySetInnerHTML={{ __html: row }}></span>
                )}
                {!config?.disable_powered_by &&
                i == props.template.length - 1 ? (
                    <NXSvg {...props} />
                ) : null}
            </p>
        );
    });
    
    return (
        <div
            className={`notificationx-content ${
                config.template_adv ? "adv-template" : ""
            }`}
            style={props.style}
        >
            {props.themes == "theme-five" && (
                <ThemeFiveShape style={themeFiveShapeStyle} />
            )}
            {content}
            {/* {post.nx_id} &gt; {props?.data?.entry_id} */}
        </div>
    );
};

export default Content;
