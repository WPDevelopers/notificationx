import React, { createRef, useCallback, useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder";
import Tooltip from 'react-power-tooltip';
import data from '@emoji-mart/data';
import Picker from '@emoji-mart/react';
import classNames from "classnames";
import editIcon from '../images/editIcon.png';
import emojiAdd from '../images/emojiAdd.png';
import uploadIcon from '../images/uploadIcon.png';



const Icon = ({name, value, onChange, options, count}: FlashingIcon) => {
    const [show, setShow]               = useState(false)
    const [showEmoji, setShowEmoji]     = useState(false)
    const [mediaUpload, setMediaUpload] = useState('');
    const [isMediaOpen, setIsMediaOpen] = useState(false);
    const iconRef                       = useRef<HTMLSpanElement>();

    const onImageClick = () => {
        setShow(!show)
        if(!show){
            addListener()
        }
        else{
            removeListener();
        }
    }

    const onEmojiClick = () => {
        setShowEmoji(!showEmoji)
    }

    const onEmojiOnClickOutside = (event) => {

        if(!event.target?.classList?.contains('emoji-picker')){
            setShowEmoji(false);
        }
    }

    const onChangeIcon = (option) => {
        setShow(false)
        setMediaUpload('')
        onChange({
            target: {
                type: "flashing-icon",
                name : name,
                value: option.icon,
            },
        });
    }

    const onUploadImage = (url) => {
        setShow(false)
        setMediaUpload('')
        onChange({
            target: {
                type: "flashing-icon",
                name : name,
                value: url,
            },
        });
    }

    const onEmojiSelect = (emoji) => {
        const size    = 50;
        const canvas  = document.createElement("canvas");
        const context = canvas.getContext ("2d");

        canvas.width = canvas.height = size;

          // The size of the emoji is set with the font
        context.font = `${size}px serif`

          // use these alignment properties for "better" positioning
        context.textAlign    = "center";
        context.textBaseline = "middle";

          // draw the emoji
        context.fillText (emoji.native, size / 2 - 1, size / 2 + 6, size)

        const png = context.canvas.toDataURL();

        setShow(false)
        setMediaUpload('')
        onChange({
            target: {
                type: "flashing-icon",
                name : name,
                value: png,
            },
        });
    }

    const onToolTipClickOutside = useCallback((event) => {
        if(iconRef?.current){
            const element = iconRef?.current;
            if(element && !element.contains(event.target)){
                setShow(false)
                removeListener();
            }
        }
    }, [iconRef?.current]);

    const addListener = () => {
        document.addEventListener('click', onToolTipClickOutside);
    }

    const removeListener = () => {
        document.removeEventListener('click', onToolTipClickOutside);
    }


    useEffect(() => {
        setShow(false);
    }, [value]);

    useEffect(() => {

        return () => {
            removeListener();
        };
    }, []);

    return (
        <>
            <span className={classNames("nx-image-popup-wrapper", {
                'has-count': count,
            })} data-count={count} ref={iconRef}>
                <img src={value} alt="iconImg" />
                <div className="img-overlay" onClick={onImageClick}>
                    <img src={editIcon} alt="iconImg" />
                </div>
                <Tooltip show={show} position="top center" arrowAlign="start">
                    {options.map((option, index)=> {
                        return (
                            <span key={index} onClick={()=>onChangeIcon(option)}>
                                <img src={option.icon} alt={option.label} />
                            </span>
                        )
                    })}
                    <span className="emoji-container">
                        <img className="emoji-picker" src={emojiAdd} alt="iconImg" onClick={onEmojiClick} />
                        <Tooltip show={showEmoji} position="bottom center" arrowAlign="start">
                            <span className="emoji-wrapper">
                                <Picker
                                theme="light"
                                data={data}
                                onEmojiSelect={onEmojiSelect}
                                onClickOutside={onEmojiOnClickOutside} />
                            </span>
                        </Tooltip>
                    </span>
                    <span>
                        <MediaUpload
                            onSelect={(media) => {
                                setIsMediaOpen(false);
                                onUploadImage(media.url);
                                setMediaUpload(media.url);
                            }}
                            multiple={false}
                            allowedTypes={['image']}
                            value={mediaUpload}
                            render={({ open }) => {
                                return <>
                                    <img src={uploadIcon}
                                        className={classNames("wprf-btn wprf-image-upload-btn", {
                                            'uploaded-item': mediaUpload != null,
                                        })}
                                        onClick={() => {
                                            removeListener();
                                            open();
                                        }}
                                    />
                                </>
                            }}
                        />
                    </span>
                </Tooltip>
            </span>
        </>
    );
};

export default Icon;
