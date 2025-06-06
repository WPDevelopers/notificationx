import React, { Fragment, useEffect, useState } from 'react'
import ScanHistory from '../../icons/ScanHistory';
import CloseIcon from '../../icons/Close';
import { __ } from '@wordpress/i18n';
import { formatDateTime } from '../../frontend/gdpr/utils/helper';
const ScannerHistory = ({setIsHistoryModalOpen, handleScannedCookieView, historyData}) => {    
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
              <th>{ __('More Info','notificationx') }</th>
            </tr>
          </thead>
          <tbody>
            {historyData.length > 0 ? (
              historyData.map((data, index) => (
                <tr key={index}>
                  
                  <td>{ data.last_scan_date ?
                      formatDateTime(data.last_scan_date)
                      : formatDateTime("") 
                    }</td>
                  <td>
                    <span className="status-complete">{ data?.status }</span>
                  </td>
                  <td>{data?.category_count || 0}</td>
                  <td>{ data?.stats?.total_cookies || 0 }</td>
                  <td>
                    <a href="#" onClick={() => handleScannedCookieView(data?.stats)}>
                      { __("More Info",'notificationx') }
                    </a>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="6">{ __('No scan history available', 'notificationx') }</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </Fragment>
  )
}

export default ScannerHistory