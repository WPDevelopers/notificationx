import React, { useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder";
import Tooltip from 'react-power-tooltip';
import data from '@emoji-mart/data';
import Picker from '@emoji-mart/react';
import emojiIconImg from '../../../assets/images/happiness.png';
import editIcon from '../../../assets/images/editIcon.png';
import emojiAdd from '../../../assets/images/emojiAdd.png';
import uploadIcon from '../../../assets/images/uploadIcon.png';
import classNames from "classnames";

const FlashingMessageIcon = (props) => {
    // const builderContext = useBuilderContext();
    const [show, setShow]           = useState(false)
    const [showEmoji, setShowEmoji] = useState(false)
    const [value, setValue]         = useState<{icon?: string, image?: string, message?: string}>(props.value || {});

    useEffect(() => {
        setShow(false);
        if(props.value !== value){
            setValue(props.value);
        }
    }, [props.value]);

    useEffect(() => {

        props.onChange({
            target: {
                type: "advanced-template",
                value: value,
                name: props.name,
            },
        });
    }, [value])

    const imageClick = () => {
        setShow(!show)
    }
    const emojiClick = () => {
        setShowEmoji(!showEmoji)
    }


    const onTextUpdate = (event) => {
        setValue((value) => {
            return {
                ...value,
                'message': event.target.value,
            }
        });
    }
    const changeIcon = (option) => {
        setShow(false)
        setValue((value) => {
            return {
                ...value,
                'icon': option.icon,
            }
        });
    }

    const setImageData = (url) => {
        setShow(false)
        setValue((value) => {
            return {
                ...value,
                'icon': url,
            }
        });
    }
    const emojiOnClickOutside = (event) => {

        if(!event.target?.classList?.contains('emoji-picker')){
            setShowEmoji(false);
        }
    }
    const onEmojiSelect = (emoji) => {
        const size = 50;
        const canvas = document.createElement("canvas");
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
        setValue((value) => {
            return {
                ...value,
                'icon': png,
            }
        });
    }

    return (
        <>
            <span>
                <img src={value.icon} alt="iconImg" />
                <div className="img-overlay">
                    <img src={editIcon} alt="iconImg" onClick={imageClick} />
                </div>
                <Tooltip show={show} position="top center" arrowAlign="start">
                    {props.options.map((option,index)=> {
                        return (
                            <span onClick={()=>changeIcon(option)}>
                                <img src={option.icon} alt={option.label} />
                            </span>
                        )
                    })}
                    <span>
                        <img className="emoji-picker" src={emojiAdd} alt="iconImg" onClick={emojiClick} />
                        <Tooltip show={showEmoji} position="bottom center" arrowAlign="start">
                            <span className="emoji-wrapper">
                                <Picker
                                theme="light"
                                data={data}
                                onEmojiSelect={onEmojiSelect}
                                onClickOutside={emojiOnClickOutside} />
                            </span>
                        </Tooltip>
                    </span>
                    <span>
                        <MediaUpload
                            onSelect={(media) => {
                                setImageData(media.url);
                            }}
                            multiple={false}
                            allowedTypes={['image']}
                            value={value?.image}
                            render={({ open }) => {
                                return <>
                                    {/* {
                                        imageData != null &&
                                        <button className="wprf-btn wprf-image-remove-btn" onClick={() => setImageData(null)}>
                                            {props?.remove || 'Remove'}
                                        </button>
                                    } */}
                                    <img src={uploadIcon}
                                        className={classNames("wprf-btn wprf-image-upload-btn",{
                                            'uploaded-item': value?.image != null,
                                        })}
                                        onClick={open}
                                    />
                                </>
                            }}
                        />
                    </span>
                </Tooltip>
            </span>
            <Input type="text" value={value.message} onChange={onTextUpdate} />

        </>
    );
};

export const GenericInput = React.memo(FlashingMessageIcon);
export default withLabel(React.memo(FlashingMessageIcon));