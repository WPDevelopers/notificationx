import React, { lazy, Suspense, useCallback, useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import Tooltip from 'react-power-tooltip';
import classNames from "classnames";
import editIcon from '../images/editIcon.png';
import emojiAdd from '../images/emojiAdd.png';
import uploadIcon from '../images/uploadIcon.png';
import { lazyWithPreload } from "react-lazy-with-preload";
const Picker = lazyWithPreload(() => import('@emoji-mart/react'));

// A function that takes a value and an iconPrefix and returns a localValue
const getLocalValue = (value, iconPrefix) => {
    // Check if the value is empty
    if (!value) {
      // Return an empty string
      return "";
    }
    // Check if the value is a URL or a data URL using regular expressions
    const isUrl = /^https?:\/\//.test(value); // returns true if value starts with http:// or https://
    const isDataUrl = /^data:/.test(value); // returns true if value starts with data:
    // If the value is neither a URL nor a data URL, prepend the iconPrefix
    const localValue = isUrl || isDataUrl ? value : iconPrefix + value;
    // Return the localValue
    return localValue;
};

const Icon = ({name, value, onChange, options, count, iconPrefix}: FlashingIcon) => {
    const [show, setShow]               = useState(false)
    const [showEmoji, setShowEmoji]     = useState(false)
    const [mediaUpload, setMediaUpload] = useState('');
    const [isMediaOpen, setIsMediaOpen] = useState(false);
    const [data, setData]               = useState({});
    const [localValue, setLocalValue]   = useState(getLocalValue(value, iconPrefix));
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
        setLocalValue(getLocalValue(value, iconPrefix));
    }, [value]);

    useEffect(() => {
        Picker.preload();

        import("@emoji-mart/data").then((data) => {
            setData(data.default);
        });

        return () => {
            removeListener();
        };
    }, []);

    return (
        <>
            <span className={classNames("nx-image-popup-wrapper", {
                'has-count': count,
            })} data-count={count} ref={iconRef}>
                <img src={localValue} alt="iconImg" />
                <div className="img-overlay" onClick={onImageClick}>
                    <img src={editIcon} alt="iconImg" />
                </div>
                <Tooltip show={show} position="top center" arrowAlign="start">
                    {options.map((option, index)=> {
                        return (
                            <span key={index} onClick={()=>onChangeIcon(option)}>
                                <img src={iconPrefix + option.icon} alt={option.label} />
                            </span>
                        )
                    })}
                    <span className="emoji-container">
                        <img className="emoji-picker" src={emojiAdd} alt="iconImg" onClick={onEmojiClick} />
                        <Tooltip show={showEmoji} position="bottom center" arrowAlign="start">
                            <span className="emoji-wrapper">
                                <Suspense fallback={<div>Loading...</div>}>
                                    <Picker
                                    theme="light"
                                    data={data}
                                    onEmojiSelect={onEmojiSelect}
                                    onClickOutside={onEmojiOnClickOutside} />
                                </Suspense>
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
