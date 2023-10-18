import React, { useEffect, useRef, useState } from "react";

const theme1 = ({ offer_discount }) => {
    const [num, setNum] = useState(0);
    const ref = useRef();

    useEffect(() => {
        setInterval(() => {
            setNum((num) => num + 1);
            console.log(num);
        }, 1000);
    }, []);

    useEffect(() => {
        if (ref.current) {
            const { width } = ref.current.getBBox();
            ref.current.setAttribute("x", `${(92 - width) / 2}`);
        }

    }, [num]);

    return (
        <svg
            width="92"
            height="98"
            viewBox="0 0 92 98"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M10 0H82V78C82 84.6274 76.6274 90 70 90H22C15.3726 90 10 84.6274 10 78V0Z"
                fill="#4F19CD"
            />
            <path d="M82 0L87 5L92 10H82V0Z" fill="#806FF6" />
            <path d="M10 0L5 5L0 10H10V0Z" fill="#806FF6" />
            <g>
                <text
                    ref={ref}
                    fill="white"
                    xmlSpace="preserve"
                    style={{whiteSpace: "pre"}}
                    font-family="DM Sans"
                    font-size="24"
                    font-weight="bold"
                    letter-spacing="0em"
                >
                    <tspan x="16" y="53.548">
                        {num}%
                    </tspan>
                </text>
            </g>
            <g filter="url(#filter1_d_620_42)">
                <text
                    fill="white"
                    xmlSpace="preserve"
                    style={{whiteSpace: "pre"}}
                    font-family="DM Sans"
                    font-size="16"
                    font-weight="bold"
                    letter-spacing="0em"
                >
                    <tspan x="37" y="73.456">
                        OFF
                    </tspan>
                </text>
            </g>
            <rect x="13" y="3" width="66" height="17" rx="2" fill="#806FF6" />
            <g filter="url(#filter2_d_620_42)">
                <text
                    fill="white"
                    xmlSpace="preserve"
                    style={{whiteSpace: "pre"}}
                    font-family="DM Sans"
                    font-size="10"
                    font-weight="500"
                    letter-spacing="0em"
                >
                    <tspan x="22.709" y="14">
                        Grab Now
                    </tspan>
                </text>
            </g>
            <defs>
                <filter
                    id="filter0_d_620_42"
                    x="21.428"
                    y="34.064"
                    width="45.2434"
                    height="21.272"
                    filterUnits="userSpaceOnUse"
                    color-interpolation-filters="sRGB"
                >
                    <feFlood flood-opacity="0" result="BackgroundImageFix" />
                    <feColorMatrix
                        in="SourceAlpha"
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                        result="hardAlpha"
                    />
                    <feOffset dy="1" />
                    <feComposite in2="hardAlpha" operator="out" />
                    <feColorMatrix
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"
                    />
                    <feBlend
                        mode="normal"
                        in2="BackgroundImageFix"
                        result="effect1_dropShadow_620_42"
                    />
                    <feBlend
                        mode="normal"
                        in="SourceGraphic"
                        in2="effect1_dropShadow_620_42"
                        result="shape"
                    />
                </filter>
                <filter
                    id="filter1_d_620_42"
                    x="37.72"
                    y="61.608"
                    width="29.0688"
                    height="12.584"
                    filterUnits="userSpaceOnUse"
                    color-interpolation-filters="sRGB"
                >
                    <feFlood flood-opacity="0" result="BackgroundImageFix" />
                    <feColorMatrix
                        in="SourceAlpha"
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                        result="hardAlpha"
                    />
                    <feOffset dy="1" />
                    <feComposite in2="hardAlpha" operator="out" />
                    <feColorMatrix
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"
                    />
                    <feBlend
                        mode="normal"
                        in2="BackgroundImageFix"
                        result="effect1_dropShadow_620_42"
                    />
                    <feBlend
                        mode="normal"
                        in="SourceGraphic"
                        in2="effect1_dropShadow_620_42"
                        result="shape"
                    />
                </filter>
                <filter
                    id="filter2_d_620_42"
                    x="23.1689"
                    y="6.8"
                    width="45.9189"
                    height="8.31999"
                    filterUnits="userSpaceOnUse"
                    color-interpolation-filters="sRGB"
                >
                    <feFlood flood-opacity="0" result="BackgroundImageFix" />
                    <feColorMatrix
                        in="SourceAlpha"
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                        result="hardAlpha"
                    />
                    <feOffset dy="1" />
                    <feComposite in2="hardAlpha" operator="out" />
                    <feColorMatrix
                        type="matrix"
                        values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"
                    />
                    <feBlend
                        mode="normal"
                        in2="BackgroundImageFix"
                        result="effect1_dropShadow_620_42"
                    />
                    <feBlend
                        mode="normal"
                        in="SourceGraphic"
                        in2="effect1_dropShadow_620_42"
                        result="shape"
                    />
                </filter>
            </defs>
        </svg>
    );
};

export default theme1;
