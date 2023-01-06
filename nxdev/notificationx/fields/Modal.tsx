import React, { useState, useEffect } from "react";
import ReactModal from "react-modal";
import { useBuilderContext } from 'quickbuilder';
import { Base64 } from 'js-base64';
import { useNotificationXContext } from "../hooks";
import { Button } from "@wordpress/components";


const Modal = ({prevTab, nextTab, ...props}) => {
    const _url = 'https://nxm.test/?nx-preview=';
    const [isOpen, setIsOpen] = useState(false);
    const context = useBuilderContext();
    const [previewType, setPreviewType] = useState("desktop");
    const [nxData, setNxData] = useState("");
    const [url, setUrl] = useState('')

    console.log(prevTab, nextTab);

    const openModal = () => {
        setIsOpen(!isOpen);

        if(!isOpen){
            // const search = new URLSearchParams(context.values);
            // url.search = search.toString();
            setUrl(_url + Base64.encode(JSON.stringify(context.values)));

            console.log(context.values);
            console.log(url.toString());
        }
    }



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
                        src={url}
                        width="100%"
                        height="500px"
                    />
                </div>
            </>
        </ReactModal>
        </>
    );
};

export default Modal;
