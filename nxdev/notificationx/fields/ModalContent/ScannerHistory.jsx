import React, { Fragment, useEffect, useState } from 'react'
import ScanHistory from '../../icons/ScanHistory';
import CloseIcon from '../../icons/Close';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import nxToast from '../../core/ToasterMsg';

const ScannerHistory = ({setIsHistoryModalOpen, dummyHistoryData, handleScannedCookieView}) => {
  const builderContext = useBuilderContext();
  const [historyData, setHistoryData] = useState([]);
  useEffect(() => {
    const fetchData = async () => {
      try {
        const statusUrl = `${builderContext?.rest?.root + builderContext?.rest?.namespace }/scan/history?nx_id=17`;
        const response = await fetch(statusUrl);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const res = await response.json();
        const data = res?.data;
        const history_data = JSON.parse(data?.data);
        console.log('his',history_data);
        
        setHistoryData(data);
      } catch (error) {
        nxToast.info(__(`Scan failed! ${error}.`, 'notificationx'));
      }
    };
  
    fetchData();
  }, []);

  console.log('historyData',historyData);
  
  

  return (
    <Fragment>
      <div className="wprf-modal-preview-header">
        <div className='header-details'>
          <div className='icon-wrap'>
            <ScanHistory />
          </div>
          <span>{ __( 'History','notificationx' ) }</span>
        </div>
          <button onClick={() => setIsHistoryModalOpen(false)}>
              <CloseIcon />
          </button>
      </div>
      <div className="wprf-modal-table-wrapper wpsp-better-repeater-fields">
      <table>
        <thead>
          <tr>
            <th>{ __('Scan Date','notificationx') }</th>
            <th>{ __('Scan Status','notificationx') }</th>
            <th>{ __('Category','notificationx') }</th>
            <th>{ __('Cookies','notificationx') }</th>
            <th>{ __('Scripts','notificationx') }</th>
            <th>{ __('More Info','notificationx') }</th>
          </tr>
        </thead>
        <tbody>
          {historyData.length > 0 ? (
            historyData.map((data, index) => (
              <tr key={index}>
                <td>{data?.scan_date}</td>
                <td>
                  <span className={`status-complete}`}>
                    Complete
                  </span>
                </td>
                <td>{data?.category}</td>
                <td>{data?.cookies}</td>
                <td>{data?.scripts}</td>
                <td>
                  <a href="#" onClick={handleScannedCookieView}>
                    More Info
                  </a>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={6} className="no-data">
                No cookies history found.
              </td>
            </tr>
          )}
        </tbody>
      </table>
      </div>
    </Fragment>
  )
}

export default ScannerHistory