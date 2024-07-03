import React, { useState } from 'react'
import { Date as DateControl } from "quickbuilder";
import CloseIcon from '../../icons/Close';

const ChangeCustomNotificationTime = ( { handleChangeTime, setChangeTimeToggle } ) => {
    const [ startDate, setStartDate ] = useState(null);
    const [ endDate, setEndDate ]     = useState(null);
    
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
                    <DateControl
                        name="startDate"
                        type="date"
                        value={startDate}
                        onChange={(value) => setStartDate(value?.target?.value)}
                        // format={settings.formats.date}
                    />
                </div>
                <div className="wrf-change-time-to">
                    <label>To</label>
                    <DateControl
                        name="startDate"
                        type="date"
                        value={endDate}
                        onChange={(value) => setEndDate(value?.target?.value)}
                        // format={settings.formats.date}
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