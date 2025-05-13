import React, { useState, useEffect, useRef } from 'react';
import { DateRange } from 'react-date-range';
import {
  addDays,
  subDays,
  startOfWeek,
  endOfWeek,
  startOfMonth,
  endOfMonth,
  startOfYear,
  endOfYear,
  isValid,
  parseISO
} from 'date-fns';
import { enUS } from 'date-fns/locale';

import moment from 'moment';
import { withLabel } from 'quickbuilder';

// Import the required styles for react-date-range
import 'react-date-range/dist/styles.css'; // main style file
import 'react-date-range/dist/theme/default.css'; // theme css file

/**
 * DateRange component for NotificationX
 * This component provides a date range picker interface using react-date-range
 */
const DateRangePicker = (props) => {
  const {
    name,
    value,
    onChange,
    className = '',
    showPresets = true,
    minDate,
    maxDate,
    disabled = false,
    placeholder = 'Select date range',
    locale = {
      format: 'MMM DD, YYYY',
      separator: ' - ',
    },
    months = 2,
    direction = 'horizontal',
  } = props;

  // Reference to the component for click outside detection
  const dateRangeRef = useRef(null);

  // State to control dropdown visibility
  const [isOpen, setIsOpen] = useState(false);

  // Parse min and max dates if provided
  const parsedMinDate = minDate ? parseISO(minDate) : undefined;
  const parsedMaxDate = maxDate ? parseISO(maxDate) : undefined;

  // Initialize state with default range or from props
  const [state, setState] = useState([
    {
      startDate: getInitialStartDate(),
      endDate: getInitialEndDate(),
      key: 'selection'
    }
  ]);

  // Helper function to get initial start date
  function getInitialStartDate() {
    if (value?.startDate) {
      const date = new Date(value.startDate);
      return isValid(date) ? date : new Date();
    }
    return new Date();
  }

  // Helper function to get initial end date
  function getInitialEndDate() {
    if (value?.endDate) {
      const date = new Date(value.endDate);
      return isValid(date) ? date : addDays(new Date(), 7);
    }
    return addDays(new Date(), 7);
  }

  // Initialize component with value from props
  useEffect(() => {
    if (value && value.startDate && value.endDate) {
      const startDate = new Date(value.startDate);
      const endDate = new Date(value.endDate);

      if (isValid(startDate) && isValid(endDate)) {
        setState([
          {
            startDate,
            endDate,
            key: 'selection'
          }
        ]);
      }
    }
  }, [value]);

  // Handle date range change
  const handleRangeChange = (item) => {
    const newState = [item.selection];
    setState(newState);

    // Format dates for the parent component
    const formattedRange = {
      startDate: moment(newState[0].startDate).format(),
      endDate: moment(newState[0].endDate).format()
    };

    // Pass the value back to the parent component
    onChange({
      target: {
        type: 'daterange',
        name,
        value: formattedRange
      }
    });
  };

  // Format dates for display
  const formatDate = (date) => {
    return moment(date).format(locale.format);
  };

  // Define predefined ranges
  const definedRanges = [
    {
      label: 'Today',
      range: () => ({
        startDate: new Date(),
        endDate: new Date(),
      }),
    },
    {
      label: 'Yesterday',
      range: () => ({
        startDate: subDays(new Date(), 1),
        endDate: subDays(new Date(), 1),
      }),
    },
    {
      label: 'Last 7 Days',
      range: () => ({
        startDate: subDays(new Date(), 6),
        endDate: new Date(),
      }),
    },
    {
      label: 'Last 30 Days',
      range: () => ({
        startDate: subDays(new Date(), 29),
        endDate: new Date(),
      }),
    },
    {
      label: 'This Week',
      range: () => ({
        startDate: startOfWeek(new Date()),
        endDate: endOfWeek(new Date()),
      }),
    },
    {
      label: 'This Month',
      range: () => ({
        startDate: startOfMonth(new Date()),
        endDate: endOfMonth(new Date()),
      }),
    },
    {
      label: 'This Year',
      range: () => ({
        startDate: startOfYear(new Date()),
        endDate: endOfYear(new Date()),
      }),
    },
    {
      label: 'Last Month',
      range: () => {
        const start = startOfMonth(subDays(new Date(), 30));
        return {
          startDate: start,
          endDate: endOfMonth(start),
        };
      },
    },
  ];

  // Toggle the date picker dropdown
  const toggleDatePicker = () => {
    if (!disabled) {
      setIsOpen(!isOpen);
    }
  };

  // Handle applying the date range and closing the picker
  const handleApply = () => {
    setIsOpen(false);
  };

  // Handle canceling and closing the picker
  const handleCancel = () => {
    setIsOpen(false);
  };

  // Clear the date range
  const handleClear = () => {
    const today = new Date();
    const newState = [{
      startDate: today,
      endDate: addDays(today, 7),
      key: 'selection'
    }];

    setState(newState);

    onChange({
      target: {
        type: 'daterange',
        name,
        value: {
          startDate: moment(today).format(),
          endDate: moment(addDays(today, 7)).format()
        }
      }
    });
  };

  // Close the date picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dateRangeRef.current && !dateRangeRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  return (
    <div
      ref={dateRangeRef}
      className={`wprf-control wprf-control-daterange ${className} ${disabled ? 'disabled' : ''}`}
    >
      {/* Date Range Summary/Trigger */}
      <div
        className="wprf-daterange-summary"
        onClick={toggleDatePicker}
      >
        <div className="wprf-daterange-summary-content">
          <span className="wprf-daterange-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 2V5" stroke="#666" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
              <path d="M16 2V5" stroke="#666" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
              <path d="M3.5 9.09H20.5" stroke="#666" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
              <path d="M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="#666" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
          </span>
          <span className="wprf-daterange-text">
            {state[0] && state[0].startDate && state[0].endDate ? (
                `${formatDate(state[0].startDate)}${locale.separator}${formatDate(state[0].endDate)}`
            ) : (
                placeholder
            )}
          </span>
        </div>
        <div className="wprf-daterange-arrow">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.92 8.95L13.4 15.47C12.63 16.24 11.37 16.24 10.6 15.47L4.08 8.95" stroke="#666" strokeWidth="1.5" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
        </div>
      </div>

      {/* Date Range Picker Dropdown */}
      {isOpen && (
        <div className={`wprf-daterange-dropdown ${showPresets ? 'with-presets' : ''}`}>
            <DateRange
                editableDateInputs={true}
                onChange={handleRangeChange}
                moveRangeOnFirstSelection={false}
                ranges={state}
                minDate={parsedMinDate}
                maxDate={parsedMaxDate}
                months={months}
                direction={direction}
                locale={enUS}
            />

            <div className="wprf-daterange-actions">
                <button
                type="button"
                className="wprf-daterange-clear"
                onClick={handleClear}
                >
                Clear
                </button>
                <div className="wprf-daterange-buttons">
                <button
                    type="button"
                    className="wprf-daterange-cancel"
                    onClick={handleCancel}
                >
                    Cancel
                </button>
                <button
                    type="button"
                    className="wprf-daterange-apply"
                    onClick={handleApply}
                >
                    Apply
                </button>
                </div>
            </div>
        </div>
      )}
    </div>
  );
};

export default withLabel(DateRangePicker);
