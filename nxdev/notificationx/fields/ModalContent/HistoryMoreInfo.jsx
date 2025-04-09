import React from 'react'
import DiscoveredCookies from '../../icons/DiscoveredCookies';
import CloseIcon from '../../icons/Close';
import { __ } from '@wordpress/i18n';

const HistoryMoreInfo = ( { setIsDiscoveredCookieModalOpen, data } ) => {
    return (
        <div>
            <div className="wprf-modal-preview-header">
                <div className='header-details'>
                <div className='icon-wrap'>
                    <DiscoveredCookies />
                </div>
                <span>{ __( 'Scanned URL','notificationx' ) }</span>
                </div>
                <button onClick={() => setIsDiscoveredCookieModalOpen(false)}>
                    <CloseIcon />
                </button>
            </div>
            <div className="wprf-modal-table-wrapper wpsp-better-repeater-fields">
                <table>
                    <thead>
                        <tr>
                            <th>{ __('Scanned URL','notificationx') }</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data?.scanned_urls.map((data, index) => (
                            <tr key={index}>
                                <td>{ data }</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

export default HistoryMoreInfo
