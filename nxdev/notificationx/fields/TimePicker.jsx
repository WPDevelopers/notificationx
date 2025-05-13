import React, { useEffect, useState } from "react";
import { withLabel } from "quickbuilder";
import moment from "moment";

/**
 * TimePicker component for NotificationX
 * This component provides a simple time picker interface using the native HTML time input
 */
const TimePicker = (props) => {
    const { name, value, onChange, placeholder, className = '' } = props;

    // Convert the value to a time string (HH:MM format)
    const [timeValue, setTimeValue] = useState(() => {
        if (value) {
            const momentTime = moment(value);
            if (momentTime.isValid()) {
                return momentTime.format('HH:mm');
            }
        }
        return '';
    });

    // Initialize the time value when component mounts
    useEffect(() => {
        if (value) {
            const momentTime = moment(value);
            if (momentTime.isValid()) {
                setTimeValue(momentTime.format('HH:mm'));
            }
        }
    }, [value]);

    // Handle time input change
    const handleTimeChange = (e) => {
        const newTimeValue = e.target.value;
        setTimeValue(newTimeValue);

        // Create a moment object with today's date and the selected time
        const momentTime = moment();
        const [hours, minutes] = newTimeValue.split(':');
        momentTime.hours(hours || 0);
        momentTime.minutes(minutes || 0);
        momentTime.seconds(0);

        // Pass the value back to the parent component
        onChange({
            target: {
                type: 'time',
                name,
                value: momentTime.format()
            }
        });
    };

    // Format the time for display (if needed)
    const getFormattedTime = () => {
        if (timeValue) {
            const [hours, minutes] = timeValue.split(':');
            const momentTime = moment();
            momentTime.hours(hours || 0);
            momentTime.minutes(minutes || 0);
            return momentTime.format('h:mm A'); // Format as 12-hour time with AM/PM
        }
        return '';
    };

    return (
        <div className={`wprf-control wprf-control-timepicker ${className}`}>
            <input
                type="time"
                name={name}
                value={timeValue}
                onChange={handleTimeChange}
                placeholder={placeholder}
                className="wprf-timepicker-input"
                style={{
                    width: '100%',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    border: '1px solid #ddd',
                    fontSize: '14px',
                    lineHeight: '1.5',
                    color: '#333',
                    backgroundColor: '#fff',
                }}
            />
            {timeValue && (
                <div className="wprf-timepicker-formatted" style={{
                    marginTop: '5px',
                    fontSize: '12px',
                    color: '#666',
                    textAlign: 'right'
                }}>
                    {getFormattedTime()}
                </div>
            )}
        </div>
    );
};

export default withLabel(TimePicker);
