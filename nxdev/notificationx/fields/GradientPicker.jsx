import React, { useEffect, useRef, useState } from 'react';
import GColorPicker from 'react-gcolor-picker';
import { __ } from '@wordpress/i18n';
import { withLabel } from 'quickbuilder';

const GradientPicker = (props) => {
    const { value, name, id, onChange } = props;
    const [showPicker, setShowPicker] = useState(false);
    const [gradient, setGradient] = useState(value || '');
    const [defaultGradient, setDefaultGradient] = useState('');
    const pickerRef = useRef(null);

    useEffect(() => {
        setDefaultGradient(value || '');
    }, []);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (pickerRef.current && !pickerRef.current.contains(event.target)) {
                setShowPicker(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    useEffect(() => {
        onChange({
            target: {
                type: 'gradientpicker',
                name,
                value: gradient,
            },
        });
    }, [gradient]);

    return (
        <div className="wprf-gradientpicker-wrap" ref={pickerRef}>
            <input type="hidden" name={name} id={id} value={gradient} />
            <span
                className="wprf-picker-display"
                onClick={() => setShowPicker(!showPicker)}
                style={{
                    background: gradient,
                    border: '1px solid #ccc',
                    width: '40px',
                    height: '40px',
                    display: 'inline-block',
                    cursor: 'pointer',
                }}
            />
            {showPicker && (
                <div className="wprf-gradientpicker-popup" style={{ position: 'relative', zIndex: 9999 }}>
                    <button
                        className="wprf-gradientpicker-reset"
                        onClick={(e) => {
                            e.preventDefault();
                            setGradient(defaultGradient);
                            setShowPicker(false);
                        }}
                    >
                        {__('Reset', 'notificationx')}
                    </button>
                    <GColorPicker
                        value={gradient}
                        onChange={(newGradient) => {
                            setGradient(newGradient);
                        }}
                        showInputs={true}
                        gradient={true}
                        solid={true}
                    />
                </div>
            )}
        </div>
    );
};

export default withLabel(GradientPicker);
