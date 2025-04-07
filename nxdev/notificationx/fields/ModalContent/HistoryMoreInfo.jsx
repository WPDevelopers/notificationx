import React from 'react'
import DiscoveredCookies from '../../icons/DiscoveredCookies';
import CloseIcon from '../../icons/Close';
import { __ } from '@wordpress/i18n';

const HistoryMoreInfo = ({setIsDiscoveredCookieModalOpen, data}) => {

	const dummyDiscoverCookies = [
		{
			"id": "wordpress_3e130031865ca4e6...",
			"first_found_url": "example.com/whyweneedex",
			"duration": "Past",
			"description": "No Description found"
		}
	]

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
                        {data?.stats?.scanned_urls.map((data, index) => (
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
