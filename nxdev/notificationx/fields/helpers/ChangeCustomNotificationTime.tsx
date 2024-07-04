import React, { useState } from 'react'
import { Date as DateControl } from "quickbuilder";
import CloseIcon from '../../icons/Close';
import AdvancedDateTimePicker from './AdvancedDateTimePicker';
import moment from 'moment';

const ChangeCustomNotificationTime = ( { handleChangeTime, setChangeTimeToggle } ) => {
    const [ startDate, setStartDate ] = useState(null);
    const [ endDate, setEndDate ]     = useState(null);
    const day = moment().toISOString();

    const handleDateChange = (value, type) => {
        const selectedDate = moment(value?.target?.value).toISOString();
        if (moment(selectedDate).isSameOrAfter(day)) {
            const currentDate = moment().toISOString();
            if( 'start' === type ) {
                setStartDate(currentDate);
            }
            if( 'end' === type ) {
                setEndDate(currentDate);
            }
        } else {
            if( 'start' === type ) {
                setStartDate(selectedDate);
            }
            if( 'end' === type ) {
                setEndDate(selectedDate);
            }
        }
    };

    return (
        <div className="wprf-change-time-wrapper">
            <div className="wprf-change-time-header">
                <div className="wprf-change-time-header-content">
                    <h4>Change Time</h4>
                    <span>This will effect on all selected Items</span>
                </div>
                <div className="wprf-change-time-header-icon">
                    <button onClick={() => setChangeTimeToggle(false)}>
                        <CloseIcon />
                    </button>
                </div>
            </div>
            <div className="wprf-change-time-content">
                <div className="wrf-change-time-from">
                    <label>From</label>
                    <AdvancedDateTimePicker
                        name="startDate"
                        type="date"
                        value={startDate}
                        onChange={(value) => handleDateChange(value, 'start')}
                        isInvalidDate={(value) => {
                            const selectedDate = moment(value).toISOString();
                            return moment(selectedDate).isSameOrAfter(day);
                        }}
                    />
                </div>
                <div className="wrf-change-time-to">
                    <label>To</label>
                    <AdvancedDateTimePicker
                        name="startDate"
                        type="date"
                        value={endDate}
                        onChange={(value) => handleDateChange(value, 'end')}
                        isInvalidDate={(value) => {
                            const selectedDate = moment(value).toISOString();
                            return moment(selectedDate).isSameOrAfter(day);
                        }}
                    />
                </div>
            </div>
            <div className="wprf-change-time-bottom">
                <button className='wprf-change-time-apply-change-btn' onClick={ () => handleChangeTime(startDate, endDate) }>Apply Changes</button>
            </div>
        </div>
    )
}

export default ChangeCustomNotificationTime