import React, { useState, useEffect } from "react";
import ReactModal from "react-modal";

const Modal = ({ isOpen, setIsOpen }) => {
    const [previewType, setPreviewType] = useState("desktop");
    return (
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
                    backgroundColor: "rgba(255, 255, 255, 0.75)",
                },
                content: {
                    position: 'static',
                    width: "600px",
                    margin: "auto",
                    border: "1px solid #ccc",
                    background: "#fff",
                    overflow: "auto",
                    WebkitOverflowScrolling: "touch",
                    borderRadius: "4px",
                    outline: "none",
                    padding: "20px",
                },
            }}
        >
            <>
                <div>
                    <button
                        type="button"
                        onClick={() => setPreviewType("desktop")}
                    >
                        Desk
                    </button>
                    <button
                        type="button"
                        onClick={() => setPreviewType("tablet")}
                    >
                        Tab
                    </button>
                    <button
                        type="button"
                        onClick={() => setPreviewType("phone")}
                    >
                        Mob
                    </button>
                </div>
                <div style={{}}>
                    <iframe
                        src="https://notificationx.com/"
                        width="100%"
                        height="500px"
                    />
                </div>
            </>
        </ReactModal>
    );
};

export default Modal;
