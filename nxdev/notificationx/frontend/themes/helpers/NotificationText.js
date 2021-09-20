import React from "react";

const NotificationText = ({ post }) => {
    const style = {};
    if (post?.text_color) style.fill = post.text_color;
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 78 9.5"
            style={style}
        >
            <title>{__(NotificationX, 'notificationx')}</title>
            <g id="Layer_2">
                <g id="Layer_1-2">
                    <path d="M6.2.6a.86.86,0,0,0-.6.3.76.76,0,0,0-.2.5V6.9S2.5,2.4,1.5,1A.67.67,0,0,0,.8.6.86.86,0,0,0,.2.9a.76.76,0,0,0-.2.5V8.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V3.1S4.5,7.5,5.4,8.9a.75.75,0,0,0,.7.4,1.42,1.42,0,0,0,.6-.2A.55.55,0,0,0,7,8.6V1.4A.76.76,0,0,0,6.8.9.86.86,0,0,0,6.2.6Z" />
                    <path d="M14.4,4.1a2.41,2.41,0,0,0-.8-.6,3.55,3.55,0,0,0-.9-.4,3.4,3.4,0,0,0-1-.1,3.4,3.4,0,0,0-1,.1,4.18,4.18,0,0,0-1,.4c-.3.2-.5.4-.8.6a2.62,2.62,0,0,0-.5.9,5,5,0,0,0-.2,1.2,5,5,0,0,0,.2,1.2,1.93,1.93,0,0,0,.5.9,2.41,2.41,0,0,0,.8.6,3.55,3.55,0,0,0,.9.4,3.4,3.4,0,0,0,1,.1,3.4,3.4,0,0,0,1-.1,3.55,3.55,0,0,0,.9-.4,5.55,5.55,0,0,0,.8-.6,2.62,2.62,0,0,0,.5-.9A5,5,0,0,0,15,6.2,5,5,0,0,0,14.8,5,1.49,1.49,0,0,0,14.4,4.1ZM13,7.9a1.76,1.76,0,0,1-1.3.6,2,2,0,0,1-1.4-.6,2.86,2.86,0,0,1-.5-1.7,2.86,2.86,0,0,1,.5-1.7,1.82,1.82,0,0,1,1.4-.6,1.49,1.49,0,0,1,1.3.6,2.31,2.31,0,0,1,.5,1.7A2,2,0,0,1,13,7.9Z" />
                    <path d="M20.1,4.1a.35.35,0,0,0,.4-.4.35.35,0,0,0-.4-.4H18v-1a1.42,1.42,0,0,0-.2-.6.73.73,0,0,0-.6-.2,1.42,1.42,0,0,0-.6.2c-.2.2-.2.3-.2.6V7.2a2,2,0,0,0,.7,1.6,2.19,2.19,0,0,0,1.7.7,4.25,4.25,0,0,0,1.1-.2,2.17,2.17,0,0,0,.7-.6c.1-.1.1-.1.1-.2s0-.2-.1-.2-.1-.1-.2-.1-.1,0-.2.1a1.23,1.23,0,0,1-.8.4,1.14,1.14,0,0,1-.8-.3A1.53,1.53,0,0,1,18,7.2V4.1Z" />
                    <path d="M22.4.5a1.42,1.42,0,0,0-.6.2c-.2.2-.2.3-.2.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2.73.73,0,0,0,.2-.6A1.42,1.42,0,0,0,23,.7,1.42,1.42,0,0,0,22.4.5Z" />
                    <path d="M22.4,3a.86.86,0,0,0-.6.3.55.55,0,0,0-.2.5V8.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V3.8a.76.76,0,0,0-.2-.5A.6.6,0,0,0,22.4,3Z" />
                    <path d="M28.2.3A2,2,0,0,0,27.1,0a2.19,2.19,0,0,0-1.7.7,2.13,2.13,0,0,0-.7,1.6V8.6a.76.76,0,0,0,.2.5.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2.55.55,0,0,0,.2-.5V4.1h1.9a.35.35,0,0,0,.4-.4.35.35,0,0,0-.4-.4H26.3v-1a1.09,1.09,0,0,1,.4-.9,1.14,1.14,0,0,1,.8-.3.91.91,0,0,1,.8.4c.1.1.1.1.2.1s.2,0,.2-.1c.2-.3.3-.4.3-.5s0-.2-.1-.2A1.79,1.79,0,0,0,28.2.3Z" />
                    <path d="M30.5,3a.86.86,0,0,0-.6.3.55.55,0,0,0-.2.5V8.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V3.8a.76.76,0,0,0-.2-.5A.6.6,0,0,0,30.5,3Z" />
                    <path d="M30.5.5a1.42,1.42,0,0,0-.6.2c-.2.2-.2.3-.2.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2.73.73,0,0,0,.2-.6,1.42,1.42,0,0,0-.2-.6A1.07,1.07,0,0,0,30.5.5Z" />
                    <path d="M36.3,3.9a1.93,1.93,0,0,1,1,.3,2.7,2.7,0,0,1,.8.7c0,.1.1.1.2.1s.2,0,.2-.1.1-.1.1-.2V4.5a4.26,4.26,0,0,0-1.2-1.1A3.17,3.17,0,0,0,35.8,3a5,5,0,0,0-1.2.2,2.07,2.07,0,0,0-1,.6,3.92,3.92,0,0,0-.8,1,3.19,3.19,0,0,0-.3,1.4,3.19,3.19,0,0,0,.3,1.4,2,2,0,0,0,.8,1,2.66,2.66,0,0,0,1,.6,5,5,0,0,0,1.2.2A2.93,2.93,0,0,0,37.4,9a3.18,3.18,0,0,0,1.2-1.1V7.7c0-.1,0-.2-.1-.2s-.1-.1-.2-.1a.35.35,0,0,0-.2.1,2.7,2.7,0,0,1-.8.7,1.93,1.93,0,0,1-1,.3,2,2,0,0,1-1.5-.6,2.27,2.27,0,0,1-.6-1.6,2.11,2.11,0,0,1,.6-1.6A1.66,1.66,0,0,1,36.3,3.9Z" />
                    <path d="M42.7,3a3.29,3.29,0,0,0-1.7.4,2.71,2.71,0,0,0-1.1,1c-.1.1-.1.1-.1.2s0,.2.1.2.1.1.2.1a.35.35,0,0,0,.2-.1,2.27,2.27,0,0,1,1.8-.9,2,2,0,0,1,1.5.6,1.61,1.61,0,0,1,.6,1.3,2.18,2.18,0,0,0-.8-.5,5.07,5.07,0,0,0-1.3-.2,5.9,5.9,0,0,0-1.4.2,2.55,2.55,0,0,0-1,.7,1.59,1.59,0,0,0-.4,1.2,1.75,1.75,0,0,0,.4,1.2,2.29,2.29,0,0,0,1,.7,3.18,3.18,0,0,0,1.4.2A3.53,3.53,0,0,0,43.5,9a2.36,2.36,0,0,0,.9-.6v.1a.7.7,0,1,0,1.4,0V6a2.88,2.88,0,0,0-.9-2.1A2.79,2.79,0,0,0,42.7,3Zm1.2,5.2a2.55,2.55,0,0,1-2.4,0,1.05,1.05,0,0,1-.5-.9.87.87,0,0,1,.5-.9,2.55,2.55,0,0,1,2.4,0,1.05,1.05,0,0,1,.5.9A1.35,1.35,0,0,1,43.9,8.2Z" />
                    <path d="M51.2,4.1a.35.35,0,0,0,.4-.4.35.35,0,0,0-.4-.4H49.1v-1a1.42,1.42,0,0,0-.2-.6.73.73,0,0,0-.6-.2,1.42,1.42,0,0,0-.6.2c-.2.2-.2.3-.2.6V7.2a2,2,0,0,0,.7,1.6,2.19,2.19,0,0,0,1.7.7A4.25,4.25,0,0,0,51,9.3a2.17,2.17,0,0,0,.7-.6c.1-.1.1-.1.1-.2s0-.2-.1-.2-.1-.1-.2-.1-.1,0-.2.1a1.23,1.23,0,0,1-.8.4,1.14,1.14,0,0,1-.8-.3,1.27,1.27,0,0,1-.4-.9V4.1Z" />
                    <path d="M53.5,3a.86.86,0,0,0-.6.3.55.55,0,0,0-.2.5V8.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V3.8a.76.76,0,0,0-.2-.5A.6.6,0,0,0,53.5,3Z" />
                    <path d="M53.5.5a1.42,1.42,0,0,0-.6.2c-.2.2-.2.3-.2.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2.73.73,0,0,0,.2-.6,1.42,1.42,0,0,0-.2-.6A.85.85,0,0,0,53.5.5Z" />
                    <path d="M61.8,4.1a2.41,2.41,0,0,0-.8-.6,3,3,0,0,0-1-.4,5,5,0,0,0-2,0,3.55,3.55,0,0,0-.9.4,5.55,5.55,0,0,0-.8.6,2.62,2.62,0,0,0-.5.9,5,5,0,0,0-.2,1.2,5,5,0,0,0,.2,1.2,1.93,1.93,0,0,0,.5.9,2.41,2.41,0,0,0,.8.6,3.55,3.55,0,0,0,.9.4,5.05,5.05,0,0,0,2,0,3.55,3.55,0,0,0,.9-.4,5.55,5.55,0,0,0,.8-.6,2.62,2.62,0,0,0,.5-.9,5,5,0,0,0,.2-1.2A5,5,0,0,0,62.2,5,1.49,1.49,0,0,0,61.8,4.1ZM60.4,7.9a2.17,2.17,0,0,1-1.4.5,2,2,0,0,1-1.4-.6,2.18,2.18,0,0,1-.5-1.7,2.86,2.86,0,0,1,.5-1.7A2.39,2.39,0,0,1,59,3.9a1.61,1.61,0,0,1,1.3.6,2.31,2.31,0,0,1,.5,1.7A2.29,2.29,0,0,1,60.4,7.9Z" />
                    <path d="M68.6,3.3A2.77,2.77,0,0,0,67.3,3a2,2,0,0,0-1.3.4,2.7,2.7,0,0,0-.8.7V3.7a.76.76,0,0,0-.2-.5.73.73,0,0,0-1,0,.76.76,0,0,0-.2.5V8.6a1.42,1.42,0,0,0,.2.6.73.73,0,0,0,.6.2,1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V5.5a1.5,1.5,0,0,1,.4-1.1A1.37,1.37,0,0,1,66.9,4a1.5,1.5,0,0,1,1.1.4,1.5,1.5,0,0,1,.4,1.1V8.6a1.42,1.42,0,0,0,.2.6c.2.2.3.2.6.2a1.42,1.42,0,0,0,.6-.2c.2-.2.2-.3.2-.6V5.7a2.3,2.3,0,0,0-.4-1.4A3.18,3.18,0,0,0,68.6,3.3Z" />
                    <path d="M77.8,8,75.4,4.8l2.5-3.3a.63.63,0,0,0-.2-.8.61.61,0,0,0-.8.1l-2.3,3L72.3.8c-.1-.2-.3-.2-.6-.2a.76.76,0,0,0-.5.2,1.42,1.42,0,0,0-.2.6.76.76,0,0,0,.2.5l2.5,3.3L71.2,8.5a.62.62,0,0,0,.1.8.37.37,0,0,0,.3.1.52.52,0,0,0,.4-.2l2.3-3.1,2.3,3a.73.73,0,0,0,.6.2.76.76,0,0,0,.5-.2.86.86,0,0,0,.3-.6A1.69,1.69,0,0,0,77.8,8Z" />
                </g>
            </g>
        </svg>
    );
};

export default NotificationText;
