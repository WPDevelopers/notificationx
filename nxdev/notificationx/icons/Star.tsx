import React from "react";

const Star = ({ star }) => {
    let ratings = [];
    star = star || 5;

    for (let i = 1; i <= 5; i++) {
        let fill;
        if(i <= star){
            fill = 100;
        }
        else if(star !== i && Math.ceil(star) == i){
            fill = (i - star) * 100;
        }
        else{
            fill = 0;
        }

        ratings.push(
            <svg
                key={Math.random()}
                fill="none"
                viewBox="0 0 511 510"
                height="14"
                width="14"
                xmlns="http://www.w3.org/2000/svg"
            >
                <g clipPath={`url(#clip-${i})`}>
                    <path
                        fill="#e3e3e3"
                        d="M115.078 508.008l140.191-77.648 140.192 77.648c11.445 6.254 25.762-3.404 23.4-16.593l-26.804-165.294L506.082 209.33c8.786-9.254 3.957-24.677-9.148-27.017L340.15 158.487 269.735 8.509C267.182 2.766 261.226 0 255.269 0c-5.956 0-11.913 2.766-14.466 8.51l-70.415 149.977-156.784 23.826C.478 184.653-4.33 200.076 4.456 209.33l114.025 116.791-26.804 165.294c-2.383 13.338 12.147 22.911 23.401 16.593z"
                    ></path>
                    <mask
                        id={`mask-${i}`}
                        style={{ maskType: "alpha" }}
                        width="511"
                        height="510"
                        x="0"
                        y="0"
                        maskUnits="userSpaceOnUse"
                    >
                        <path
                            fill="#e3e3e3"
                            d="M115.078 508.008l140.191-77.648 140.192 77.648c11.445 6.254 25.762-3.404 23.4-16.593l-26.804-165.294L506.082 209.33c8.786-9.254 3.957-24.677-9.148-27.017L340.15 158.487 269.735 8.509C267.182 2.766 261.226 0 255.269 0c-5.956 0-11.913 2.766-14.466 8.51l-70.415 149.977-156.784 23.826C.478 184.653-4.33 200.076 4.456 209.33l114.025 116.791-26.804 165.294c-2.383 13.338 12.147 22.911 23.401 16.593z"
                        ></path>
                    </mask>
                    <g mask={`url(#mask-${i})`}>
                        <rect
                            width={`${fill}%`}
                            height="510"
                            fill="#ffc107"
                        ></rect>
                    </g>
                </g>
                <defs>
                    <clipPath id={`clip-${i}`}>
                        <path fill="#e3e3e3" d="M0 0H511V510H0z"></path>
                    </clipPath>
                </defs>
            </svg>
        );
    }

    return <>{ratings}</>;
};

export default Star;
