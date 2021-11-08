import React, { CSSProperties } from "react";
import { Branding as NXSvg, ThemeFiveShape } from ".";
import { Star } from "../../../icons";

const Content = (props) => {
    const { config } = props;
    let rowClasses = ["nx-first-row", "nx-second-row", "nx-third-row"];

    let themeFiveShapeStyle = {}
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
            const rating = /rating::(\d+)/.exec(row.join(' '));
            if (rating?.[1]) {
                let i = 0;
                const _row = row.join(' ').replace(/(<([^>]+)>)/gi, "").split(`rating::${rating[1]}`);
                advTmplRatingRow = [];
                if (_row[0])
                    advTmplRatingRow.push(<span className={colClasses[i++]} dangerouslySetInnerHTML={{ __html: _row[0] }}></span>)
                advTmplRatingRow.push(<span className={colClasses[i++]}><Star star={parseInt(rating[1])} /></span>)
                if (_row[1])
                    advTmplRatingRow.push(<span className={colClasses[i++]} dangerouslySetInnerHTML={{ __html: _row[1] }}></span>)
            }
        }

        return (
            <p key={i} className={rowClasses[i]} style={rowStyle}>
                {advTmplRatingRow && advTmplRatingRow}
                {config?.template_adv && !advTmplRatingRow && (
                    <span dangerouslySetInnerHTML={{ __html: row.join(' ') }}></span>
                )}
                {!config?.template_adv &&
                    row.map((col: string, j) => {
                        if (col.includes('rating::') && col.substr(8)) {
                            // @todo for adv tmpl
                            return (<span key={j} className={colClasses[j]}>
                                <Star star={parseInt(col.substr(8))} />
                            </span>)
                        }
                        else {
                            return (<span key={j} className={colClasses[j]}>
                                {col}{" "}
                            </span>)
                        }
                    }
                    )
                }
                {!config?.disable_powered_by &&
                    i == props.template.length - 1 ? (
                    <NXSvg {...props} />
                ) : null}
            </p>
        );
    });
    return (
        <div className={`notificationx-content ${config.template_adv ? 'adv-template' : ''}`} style={props.style}>

            <ThemeFiveShape style={themeFiveShapeStyle} />

            {/*?xml version="1.0" encoding="utf-8"?*/}
            {/* <svg
                id="themeFiveSVGShape"
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                xmlnsXlink="http://www.w3.org/1999/xlink"
                x="0px"
                y="0px"
                viewBox="0 0 4.4 20"
                style={{ enableBackground: "new 0 0 4.4 20" }}
                xmlSpace="preserve"
            >
                <path
                    className="st0"
                    d="M0.7,0C3,2.6,4.4,5.9,4.4,9.6c0,4-1.7,7.7-4.3,10.4c1.5,0,4,0,5.4,0V0C3.8,0,1.5,0,0.7,0z"
                />
            </svg> */}
            {content}
            {/* {post.nx_id} &gt; {props?.data?.entry_id} */}
        </div>
    );
};

export default Content;
