import { useState } from "react";
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";

import attributes from "./components/attributes";
import Edit from "./components/edit";

registerBlockType("notificationx-pro/notificationx", {
  title: "Inline Notification",
  namespace: "notificationx",
  apiVersion: 2,
  attributes,
  icon: (
    <svg
      height="55"
      viewBox="0 0 387 392"
      width="53"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g fill="none" fillRule="evenodd">
        <g fillRule="nonzero">
          <path
            d="m135.45 358.68h113.62c-2.05 13.15-27.83 29.91-49.81 32.3-25.34 2.75-56.03-12.6-63.81-32.3z"
            fill="#5614d5"
          />
          <path
            d="m372.31 305.79c-2.34-.2-4.71-.08-7.07-.08-5.61-.01-11.22 0-18.16 0 0-4.28 0-7.29 0-10.3-.01-46.66.17-93.32-.17-139.98-.08-10.54-1.03-21.24-3.12-31.56-17.4-85.97-103.85-140.06-188.98-118.65-67.97 17.09-116.9 79.04-116.62 149.48.17 42.42.02 84.84.01 127.26 0 3.84-.02 15.83-.04 23.74-5.18-.04-20.09-.13-25.3.18-7.73.45-12.92 6.43-12.82 14.09.1 7.46 5.04 12.77 12.63 13.45 2.11.19 4.24.15 6.36.15 115.71.04 231.43.07 347.14.09 2.12 0 4.25.03 6.36-.18 7.48-.75 12.61-6.25 12.75-13.53.13-7.37-5.42-13.51-12.97-14.16z"
            fill="#5614d5"
          />
          <g fill="#836eff">
            <circle cx="281.55" cy="255.92" r="15.49" />
            <path d="m295.67 140.1.24-.16c-.21-1.31-.39-2.65-.64-3.92-9.4-46.45-49.44-80.68-96.48-83.49-.06 0-.12-.01-.18-.01-2.02-.12-4.04-.2-6.08-.2-.05 0-.09 0-.14 0s-.09 0-.14 0c-2.04 0-4.07.08-6.08.2-.06 0-.12.01-.18.01-47.04 2.81-87.08 37.04-96.48 83.49-.26 1.27-.44 2.61-.64 3.92l.24.16c-.91 5.5-1.39 11.12-1.37 16.8.02 4.52.03 99.87.04 112.84l32.13 34.68c0-24.28-.01-133.85-.06-147.64-.13-32.6 22.96-62.09 54.91-70.12 2.65-.67 5.33-1.16 8.02-1.53.45-.06.89-.13 1.35-.18 1.02-.12 2.04-.21 3.05-.29 1.46-.1 2.92-.18 4.4-.19.27 0 .54-.02.81-.03.27 0 .54.02.81.03 1.48.01 2.94.09 4.4.19 1.02.08 2.04.17 3.05.29.45.05.9.12 1.35.18 2.69.37 5.37.86 8.02 1.53 31.94 8.03 55.04 37.53 54.91 70.12-.02 5.17-.03 50.29-.04 71.4l32.14-21.45c0-12.23.01-48.45.01-49.82.02-5.7-.45-11.31-1.37-16.81z" />
          </g>
        </g>
        <path d="m31.94 305.72c-6.36.13-12.74-.21-19.08.16-7.73.45-12.92 6.43-12.82 14.09.1 7.46 5.04 12.77 12.63 13.45 2.11.19 4.24.15 6.36.15 115.71.04 231.42.06 347.14.09 2.12 0 4.25.03 6.36-.18 7.48-.75 12.61-6.25 12.75-13.53.14-7.37-5.41-13.5-12.96-14.16-2.34-.2-4.71-.08-7.07-.08-5.61-.01-11.22 0-18.16 0 0-4.28 0-7.29 0-10.3-.01-40.67.11-81.34-.08-122l-215.39 143.62-78.04-84.22 33.47-30.79 51.67 55.6 204.48-136.36c-18.61-84.45-104.12-137.24-188.38-116.05-67.97 17.09-116.9 79.04-116.62 149.48.17 42.42.02 84.84.01 127.26 0 5.89.09 11.79-.05 17.67" />
        <path
          d="m346.91 155.42c.04 5.99.06 11.99.09 17.98l39.14-25.99-25.24-37.84-17.7 11.69c.19.87.42 1.72.6 2.59 2.08 10.33 3.04 21.04 3.11 31.57z"
          fill="#00f9ac"
          fillRule="nonzero"
        />
        <path d="m87.05 202.03-33.47 30.79 78.04 84.22 215.38-143.63c-.03-5.99-.04-11.99-.09-17.98-.08-10.54-1.03-21.24-3.12-31.56-.18-.88-.4-1.73-.6-2.59l-204.47 136.35z" />
        <path
          d="m87.05 202.03-33.47 30.79 78.04 84.22 215.38-143.63c-.03-5.99-.04-11.99-.09-17.98-.08-10.54-1.03-21.24-3.12-31.56-.18-.88-.4-1.73-.6-2.59l-204.47 136.35z"
          fill="#21d8a3"
          fillRule="nonzero"
          opacity=".9"
        />
      </g>
    </svg>
  ),
  edit: Edit,
  // save: () => {
  //   return {};
  // },
});
registerBlockType("notificationx-pro/notificationx-render", {
  title: "NotificationX render",
  namespace: "notificationx",
  parent: ["notificationx-pro/notificationx"],
  apiVersion: 2,
  attributes: {
    nx_id: {
      type: "string",
      default: null,
    },
    product_id: {
      type: "string",
      default: null,
    },
  },
  // save: () => {
  //   return {};
  // },
});
