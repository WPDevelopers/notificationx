import React, { Suspense, useCallback, useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import Tooltip from 'react-power-tooltip';
import classNames from "classnames";
import { withLabel } from "quickbuilder";
import editIcon from './images/editIcon.png';
import emojiAdd from './images/emojiAdd.png';
import uploadIcon from './images/uploadIcon.png';
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

const IconPicker = (props) => {
    const {
        name,
        value,
        onChange,
        options = [],
        iconPrefix = "",
        label,
        classes,
        id,
        placeholder,
        description,
    } = props;

    const [show, setShow] = useState(false);
    const [showEmoji, setShowEmoji] = useState(false);
    const [mediaUpload, setMediaUpload] = useState('');
    const [data, setData] = useState({});
    const [localValue, setLocalValue] = useState(getLocalValue(value, iconPrefix));
    const iconRef = useRef(null);

    const onImageClick = () => {
        setShow(!show);
        if (!show) {
            addListener();
        } else {
            removeListener();
        }
    };

    const onEmojiClick = () => {
        setShowEmoji(!showEmoji);
    };

    const onEmojiOnClickOutside = (event) => {
        if (!event.target?.classList?.contains('emoji-picker')) {
            setShowEmoji(false);
        }
    };

    const onChangeIcon = (option) => {
        setShow(false);
        setMediaUpload('');
        onChange({
            target: {
                type: "icon-picker",
                name: name,
                value: option.icon,
            },
        });
    };

    const onUploadImage = (url) => {
        setShow(false);
        setMediaUpload('');
        onChange({
            target: {
                type: "icon-picker",
                name: name,
                value: url,
            },
        });
    };

    // Alternative media upload using native WordPress media API
    const openNativeMediaModal = () => {
        if (window.wp && window.wp.media) {
            const frame = window.wp.media({
                title: 'Select Icon',
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                console.log('Native media selected:', attachment);
                onUploadImage(attachment.url);
                setMediaUpload(attachment.url);
            });

            // Close tooltip and open modal
            removeListener();
            setShow(false);
            setTimeout(() => {
                frame.open();
            }, 100);
        } else {
            console.error('WordPress media library is not available');
        }
    };

    const onEmojiSelect = (emoji) => {
        const size = 50;
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");

        canvas.width = canvas.height = size;

        // The size of the emoji is set with the font
        context.font = `${size}px serif`;

        // use these alignment properties for "better" positioning
        context.textAlign = "center";
        context.textBaseline = "top";

        // draw the emoji
        context.fillText(emoji.native, size / 2, 6, size);

        const png = context.canvas.toDataURL();

        setShow(false);
        setMediaUpload('');
        setShowEmoji(false);
        onChange({
            target: {
                type: "icon-picker",
                name: name,
                value: png,
            },
        });
    };

    const onToolTipClickOutside = useCallback((event) => {
        if (iconRef?.current) {
            const element = iconRef?.current;
            if (element && !element.contains(event.target)) {
                setShow(false);
                removeListener();
            }
        }
    }, [iconRef?.current]);

    const addListener = () => {
        document.addEventListener('click', onToolTipClickOutside);
    };

    const removeListener = () => {
        document.removeEventListener('click', onToolTipClickOutside);
    };

    useEffect(() => {
        setShow(false);
        setLocalValue(getLocalValue(value, iconPrefix));
    }, [value, iconPrefix]);

    // Check if WordPress media is available
    useEffect(() => {
        if (typeof window !== 'undefined' && !window.wp?.media) {
            console.warn('WordPress media library is not available. Make sure wp-media-utils is properly loaded.');
        }
    }, []);

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
        <div
            className={classNames(
                "wprf-control",
                "wprf-control-wrapper",
                "wprf-icon-picker",
                `wprf-${name}-icon-picker`,
                classes
            )}
        >
            {label && (
                <div className="wprf-control-label">
                    <label htmlFor={id}>{label}</label>
                    {description && (
                        <p className="wprf-control-description">{description}</p>
                    )}
                </div>
            )}
            <div className="wprf-icon-picker-wrapper">
                <span
                    className="nx-image-popup-wrapper"
                    ref={iconRef}
                >
                    {localValue ? (
                        <img src={localValue} className="icon-preview" />
                    ) : (
                        <div className="icon-placeholder">
                            {placeholder || "Select an icon"}
                        </div>
                    )}
                    <div className="img-overlay" onClick={onImageClick}>
                        <img src={editIcon} alt="Edit Icon" />
                    </div>
                    <Tooltip show={show} position="top center" arrowAlign="start">
                        <div className="icon-picker-options">
                            {/* Predefined Icons */}
                            {options.length > 0 && (
                                <div className="icon-options-section">
                                    <h4>Icons</h4>
                                    <div className="icon-grid">
                                        {options.map((option, index) => (
                                            <span
                                                key={index}
                                                className="icon-option"
                                                onClick={() => onChangeIcon(option)}
                                                title={option.label}
                                            >
                                                <img
                                                    src={iconPrefix + option.icon}
                                                    alt={option.label}
                                                />
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Emoji Picker */}
                            <div className="icon-options-section">
                                <h4>Emoji</h4>
                                <span className="emoji-container">
                                    <img
                                        className="emoji-picker"
                                        src={emojiAdd}
                                        alt="Add Emoji"
                                        onClick={onEmojiClick}
                                    />
                                    <Tooltip show={showEmoji} position="bottom center" arrowAlign="start">
                                        <span className="emoji-wrapper">
                                            <Suspense fallback={<div>Loading...</div>}>
                                                <Picker
                                                    theme="light"
                                                    data={data}
                                                    onEmojiSelect={onEmojiSelect}
                                                    onClickOutside={onEmojiOnClickOutside}
                                                />
                                            </Suspense>
                                        </span>
                                    </Tooltip>
                                </span>
                            </div>

                            {/* Media Upload */}
                            <div className="icon-options-section">
                                <h4>Upload</h4>
                                <span className="upload-container">
                                    {/* Try MediaUpload component first */}
                                    <MediaUpload
                                        onSelect={(media) => {
                                            console.log('Media selected:', media);
                                            onUploadImage(media.url);
                                            setMediaUpload(media.url);
                                        }}
                                        multiple={false}
                                        allowedTypes={['image']}
                                        value={mediaUpload}
                                        render={({ open }) => (
                                            <img
                                                src={uploadIcon}
                                                alt="Upload Icon"
                                                onClick={() => {
                                                    try {
                                                        removeListener();
                                                        setShow(false);
                                                        setTimeout(() => {
                                                            open();
                                                        }, 100);
                                                    } catch (error) {
                                                        console.warn('MediaUpload failed, trying native modal:', error);
                                                        openNativeMediaModal();
                                                    }
                                                }}
                                                className="upload-trigger"
                                            />
                                        )}
                                    />

                                    {/* Fallback: Native WordPress media button */}
                                    <img
                                        src={uploadIcon}
                                        alt="Upload Icon (Fallback)"
                                        onClick={openNativeMediaModal}
                                        className="upload-trigger upload-fallback"
                                        style={{ display: 'none' }}
                                        title="Click if main upload doesn't work"
                                    />
                                </span>
                            </div>
                        </div>
                    </Tooltip>
                </span>
            </div>
        </div>
    );
};

export default withLabel(IconPicker);