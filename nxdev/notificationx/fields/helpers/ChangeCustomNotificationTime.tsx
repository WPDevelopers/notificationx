import React, { useState } from 'react'
import { Date as DateControl } from "quickbuilder";
import CloseIcon from '../../icons/Close';
import AdvancedDateTimePicker from './AdvancedDateTimePicker';
import moment from 'moment';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

const ChangeCustomNotificationTime = ( { handleChangeTime, setChangeTimeToggle } ) => {
    const [ startDate, setStartDate ] = useState(null);
    const [ endDate, setEndDate ]     = useState(null);
    const [applyDisabled, setApplyDisabled] = useState(false);
    const day = moment().toISOString();

    const handleDateChange = (value, type) => {
        let selectedDate = moment(value?.target?.value).startOf('day');
        const currentDate = moment().startOf('day');        
        if (selectedDate.isAfter(currentDate)) {
            selectedDate = currentDate;
        }

        if (type === 'start') {
            setStartDate(selectedDate.toISOString());
        } else if (type === 'end') {
            if (selectedDate.isBefore(startDate)) {
                setApplyDisabled(true);
            } else {
                setApplyDisabled(false);
            }
            setEndDate(selectedDate.toISOString());
        }
    };

    return (
        <div className="wprf-change-time-wrapper">
            <div className="wprf-change-time-header">
                <div className="wprf-change-time-header-content">
                    <h4>{ __('Change Time', 'notificationx') }</h4>
                    <span>{ __('This will effect on all selected Items', 'notificationx') }</span>
                </div>
                <div className="wprf-change-time-header-icon">
                    <button onClick={() => setChangeTimeToggle(false)}>
                        <CloseIcon />
                    </button>
                </div>
            </div>
            <div className="wprf-change-time-content">
                <div className="wrf-change-time-from">
                    <label>{ __('From', 'notificationx') }</label>
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
                <div className={ classNames(`wrf-change-time-to ${ applyDisabled ? 'apply-disabled' : '' } `) }>
                    <label>{ __('To', 'notificationx') }</label>
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
                <button className='wprf-change-time-apply-change-btn' disabled={applyDisabled} onClick={ () => handleChangeTime(startDate, endDate) }>{ __('Apply Changes', 'notificationx') }</button>
            </div>
        </div>
    )
}

export default ChangeCustomNotificationTime