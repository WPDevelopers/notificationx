import React, { CSSProperties } from "react";
import { Branding as NXSvg, ThemeFiveShape } from ".";
import { Star } from "../../../icons";

const Content = (props) => {
    const { config } = props;
    let rowClasses = ["nx-first-row", "nx-second-row", "nx-third-row"];
    let themeFiveShapeStyle = {};
    if (props.style) {
        themeFiveShapeStyle = { fill: props.style?.backgroundColor };
    }
    let colClasses = [
        "nx-first-word",
        "nx-second-word",
        "nx-third-word",
        "nx-fourth-word",
        "nx-fifth-word",
        "nx-sixth-word",
        "nx-seventh-word",
        "nx-eighth-word",
        "nx-nineth-word",
        "nx-tenth-word",
    ];

    let template = props.template;
    if (props.template.length < 3) {
        props.template.push([]);
    }

    let content = props.template.map((row, i) => {
        const rowStyle: CSSProperties = {};
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

        if (config?.template_adv) {
            const rating = /rating::(\d+)/.exec(row.join(" "));
            if (rating?.[1]) {
                let i = 0;
                const _row = row
                    .join(" ")
                    .replace(/(<([^>]+)>)/gi, "")
                    .split(`rating::${rating[1]}`);
                advTmplRatingRow = [];
                if (_row[0])
                    advTmplRatingRow.push(
                        <span
                            className={colClasses[i++]}
                            dangerouslySetInnerHTML={{ __html: _row[0] }}
                        ></span>
                    );
                advTmplRatingRow.push(
                    <span className={colClasses[i++]}>
                        <Star star={parseInt(rating[1])} />
                    </span>
                );
                if (_row[1])
                    advTmplRatingRow.push(
                        <span
                            className={colClasses[i++]}
                            dangerouslySetInnerHTML={{ __html: _row[1] }}
                        ></span>
                    );
            }
        }

        return (
            <p key={i} className={rowClasses[i]} style={rowStyle}>
                {advTmplRatingRow && advTmplRatingRow}
                {config?.template_adv && !advTmplRatingRow && (
                    <span
                        dangerouslySetInnerHTML={{ __html: row.join(" ") }}
                    ></span>
                )}
                {!config?.template_adv &&
                    row.map((col: string, j) => {
                        if (col.includes("rating::") && col.substr(8)) {
                            // @todo for adv tmpl
                            return (
                                <span key={j} className={colClasses[j]}>
                                    <Star star={parseInt(col.substr(8))} />
                                </span>
                            );
                        } else {
                            return (
                                <span key={j} className={colClasses[j]}>
                                    {col}{" "}
                                </span>
                            );
                        }
                    })}
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
