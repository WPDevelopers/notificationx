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
    const builderContext = useBuilderContext();
    const [value, setValue] = useState(props.value || {});

    // useEffect(() => {
      
    // if(!value.icon) {
    //     setValue({...value, 'icon': props.options[0].icon})
    // }
      
    // }, [])
    
    const [show, setShow] = useState(false)
    const [showEmoji, setShowEmoji] = useState(false)

    const imageClick = () => {
        setShow(!show)
    }
    const emojiClick = () => {
        setShowEmoji(!showEmoji)
    }


    const changeIcon = (option) => {
        const _value = {...value, 'icon': option.icon}
        setShow(false)
        setValue(_value);
        props.onChange({
            target: {
                type: "advanced-template",
                value: _value,
                name: props.name,
            },
        });
    }

    const setImageData = (option) => {
        const _value = {...value, 'image': option}
        setShow(false)
        setValue(_value);
        props.onChange({
            target: {
                type: "advanced-template",
                value: _value,
                name: props.name,
            },
        });
    }

    const onTextUpdate = (event) => {
        const _value = {...value, 'message': event.val}
        setValue(_value);
        props.onChange({
            target: {
                type: "advanced-template",
                value: _value,
                name: props.name,
            },
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
                        <img src={emojiAdd} alt="iconImg" onClick={emojiClick} />
                        <Tooltip show={showEmoji} position="bottom center" arrowAlign="start">
                            <span className="emoji-wrapper"><Picker data={data} /></span>
                        </Tooltip>
                    </span>
                    <span>
                        <MediaUpload
                            onSelect={(media) => {
                                setImageData({
                                    id: media.id,
                                    title: media.title,
                                    url: media.url
                                });
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
            <Input type="text" onChange={onTextUpdate} />
            
        </>
    );
};

export const GenericInput = React.memo(FlashingMessageIcon);
export default withLabel(React.memo(FlashingMessageIcon));
