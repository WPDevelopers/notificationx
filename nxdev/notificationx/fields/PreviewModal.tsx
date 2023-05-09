import React, { useState, useEffect } from "react";
import ReactModal from "react-modal";
import { useBuilderContext } from "quickbuilder";
import { useNotificationXContext } from "../hooks";
import { Button } from "@wordpress/components";
import { ReactComponent as DesktopIcon } from "../icons/responsive/desktop.svg";
import { ReactComponent as TabletIcon } from "../icons/responsive/tablet.svg";
import { ReactComponent as MobileIcon } from "../icons/responsive/mobile.svg";

const PreviewModal = (props) => {
    const nxContext = useNotificationXContext();
    const [isOpen, setIsOpen] = useState(false);
    const context = useBuilderContext();
    const [previewType, setPreviewType] = useState("desktop");
    const [nxData, setNxData] = useState("");
    const [url, setUrl] = useState("");

    // console.log(prevTab, nextTab);

    const buildUrl = () => {
        const { source } = context.values;
        let _url = props.urls?.[source]
            ? props.urls[source]
            : props.urls["default"];
        const url = new URL(_url);
        const data = encodeURIComponent(
            JSON.stringify({ ...context.values, previewType })
        );

        url.searchParams.append("nx-preview", data);

        setUrl(url.toString());
    };

    const openModal = () => {
        setIsOpen(!isOpen);

        if (!isOpen) {
            buildUrl();
        }
    };

    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "unset";
        }
    }, [isOpen]);

    useEffect(() => {
        return () => {
            document.body.style.overflow = "unset";
        };
    }, []);

    return (
        <>
            <Button
                className={`wprf-btn wprf-step-btn-${props.name}`}
                onClick={openModal}
            >
                {props.label}
            </Button>
            <ReactModal
                isOpen={isOpen}
                onRequestClose={() => setIsOpen(false)}
                style={{
                    overlay: {
                        position: "fixed",
                        display: "flex",
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: "rgba(3, 6, 60, 0.7)",
                        zIndex: 9999,
                        padding: "60px 15px",
                        // overflowY: "auto",
                    },
                    content: {
                        position: "static",
                        width:
                            previewType == "desktop"
                                ? "1440px"
                                : previewType == "tablet"
                                ? "768px"
                                : "480px",
                        margin: "auto",
                        border: "0px solid #5414D0",
                        background: "#5414D0",
                        overflow: "auto",
                        WebkitOverflowScrolling: "touch",
                        borderRadius: "4px",
                        outline: "none",
                        padding: "0px",
                    },
                }}
            >
                <>
                    <div className="nx-admin-modal-head">
                        <button
                            className={`nx-admin-modal-preview-button ${
                                previewType == "desktop" ? "active" : ""
                            }`}
                            type="button"
                            onClick={() => setPreviewType("desktop")}
                        >
                            <DesktopIcon style={{ width: 20 }} />
                        </button>
                        <button
                            className={`nx-admin-modal-preview-button ${
                                previewType == "tablet" ? "active" : ""
                            }`}
                            type="button"
                            onClick={() => setPreviewType("tablet")}
                        >
                            <TabletIcon style={{ width: 17 }} />
                        </button>
                        <button
                            className={`nx-admin-modal-preview-button ${
                                previewType == "phone" ? "active" : ""
                            }`}
                            type="button"
                            onClick={() => setPreviewType("phone")}
                        >
                            <MobileIcon style={{ width: 15 }} />
                        </button>
                        <button
                            className="nx-admin-modal-close-button"
                            type="button"
                            onClick={() => setIsOpen(false)}
                        >
                            <svg
                                width="6px"
                                height="6px"
                                viewBox="0 0 48 48"
                                fill="#000000"
                            >
                                <g stroke="none">
                                    <g>
                                        <path d="M28.228 23.986L47.092 5.122a2.998 2.998 0 000-4.242 2.998 2.998 0 00-4.242 0L23.986 19.744 5.121.88a2.998 2.998 0 00-4.242 0 2.998 2.998 0 000 4.242l18.865 18.864L.879 42.85a2.998 2.998 0 104.242 4.241l18.865-18.864L42.85 47.091a2.991 2.991 0 002.121.879 2.998 2.998 0 002.121-5.121L28.228 23.986z"></path>
                                    </g>
                                </g>
                            </svg>
                        </button>
                    </div>
                    <div className="nx-admin-modal-body">
                        {!props.urls?.[context.values.source] ? (
                            <div
                                style={{
                                    height: "600px",
                                    display: "flex",
                                    alignItems: "center",
                                    justifyContent: "center",
                                    fontSize: 24,
                                    color: "#7c8db5",
                                }}
                            >
                                {props.errors[context.values.source]}
                            </div>
                        ) : (
                            <iframe
                                src={url + "#" + previewType}
                                width="100%"
                                height="600px"
                                style={{ display: "flex" }}
                            />
                        )}
                    </div>
                </>
            </ReactModal>
        </>
    );
};

export default PreviewModal;
