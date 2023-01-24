import React, { useState, useEffect } from "react";
import ReactModal from "react-modal";
import { useBuilderContext } from "quickbuilder";
import { useNotificationXContext } from "../hooks";
import { Button } from "@wordpress/components";

const Modal = (props) => {
    const nxContext = useNotificationXContext();
    const _url = props.url + "?nx-preview=";
    const [isOpen, setIsOpen] = useState(false);
    const context = useBuilderContext();
    const [previewType, setPreviewType] = useState("desktop");
    const [nxData, setNxData] = useState("");
    const [url, setUrl] = useState("");

    // console.log(prevTab, nextTab);

    const openModal = () => {
        setIsOpen(!isOpen);

        if (!isOpen) {
            setUrl(
                _url +
                    encodeURIComponent(
                        JSON.stringify({ ...context.values, previewType })
                    )
            );
        }
    };

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
                        overflowY: "auto",
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
                        border: "2px solid #0302b5",
                        background: "#0302b5",
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
                            <img
                                src={`${nxContext.assets.admin}images/responsive/desktop.svg`}
                                alt="desktop"
                                style={{ width: 20 }}
                            />
                        </button>
                        <button
                            className={`nx-admin-modal-preview-button ${
                                previewType == "tablet" ? "active" : ""
                            }`}
                            type="button"
                            onClick={() => setPreviewType("tablet")}
                        >
                            <img
                                src={`${nxContext.assets.admin}images/responsive/tablet.svg`}
                                alt="tablet"
                                style={{ width: 17 }}
                            />
                        </button>
                        <button
                            className={`nx-admin-modal-preview-button ${
                                previewType == "phone" ? "active" : ""
                            }`}
                            type="button"
                            onClick={() => setPreviewType("phone")}
                        >
                            <img
                                src={`${nxContext.assets.admin}images/responsive/mobile.svg`}
                                alt="phone"
                                style={{ width: 15 }}
                            />
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
                                fill="#ffffff"
                            >
                                <g stroke="none">
                                    <g>
                                        <path d="M28.228 23.986L47.092 5.122a2.998 2.998 0 000-4.242 2.998 2.998 0 00-4.242 0L23.986 19.744 5.121.88a2.998 2.998 0 00-4.242 0 2.998 2.998 0 000 4.242l18.865 18.864L.879 42.85a2.998 2.998 0 104.242 4.241l18.865-18.864L42.85 47.091a2.991 2.991 0 002.121.879 2.998 2.998 0 002.121-5.121L28.228 23.986z"></path>
                                    </g>
                                </g>
                            </svg>
                        </button>
                    </div>
                    <div style={{}}>
                        <iframe
                            src={url}
                            width="100%"
                            height="600px"
                            style={{ display: "flex" }}
                        />
                    </div>
                </>
            </ReactModal>
        </>
    );
};

export default Modal;
