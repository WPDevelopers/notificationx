import React, { useState, useEffect } from 'react';
import scan from './images/scan.png';
import scan_warning from './images/scan-warning.png';
import scan_danger from './images/scan-danger.png';
import schedule from './images/coming-soon.png';
import ReactModal from "react-modal";
import { modalStyle } from '../core/constants';
import { __ } from '@wordpress/i18n';
import warning from 'tiny-warning';

const CookieScanner = () => {
  	const [scanStatus, setScanStatus] = useState('');
  	const [isScanning, setIsScanning] = useState(false);
  	const [scanId, setScanId] = useState(null);
  	const [isReadyToScanModalOpen, setIsReadyToScanModalOpen] = useState(false);
  	const [isScanProgressModalOpen, setIsScanProgressModalOpen] = useState(false);

  	const handleScan = async () => {
		setIsReadyToScanModalOpen(false);
		setIsScanProgressModalOpen(true);
    	const currentDomain = window.location.origin;
    	const apiUrl = `${currentDomain}/wp-json/notificationx/v1/scan`;

    	try {
      		setIsScanning(true);
      		setScanStatus('Scanning started...');

      		const response = await fetch(apiUrl, {
				method: 'POST',
        		headers: {
          			'Content-Type': 'application/json',
        		},
        		body: JSON.stringify({ url: currentDomain }),
      		});

			const res = await response.json();
			const data = res?.data;      
			if (data?.scan_id) {
				setScanId(data.scan_id);
			} else {
				setScanStatus('Scan initiation failed.');
				setIsScanning(false);
			}
    	} catch (error) {
			console.error('Error starting scan:', error);
			setScanStatus('Scan failed. Try again.');
			setIsScanning(false);
		}
  	};

  	useEffect(() => {
    	if (!scanId) return;

    	const checkScanStatus = async () => {
			const statusUrl = `${window.location.origin}/wp-json/notificationx/v1/scan/status?scan_id=${scanId}`;

			try {
				const response = await fetch(statusUrl);
				const res = await response.json();
				const data = res?.data;
				if (data?.status === 'completed') {
				setScanStatus('Scan completed.');
				setIsScanning(false);
				setScanId(null);
				} else if (data?.status === 'pending') {
				setScanStatus('Scanning in progress...');
				} else {
				setScanStatus('Unknown scan status.');
				setIsScanning(false);
				setScanId(null);
				}
			} catch (error) {
				console.error('Error checking scan status:', error);
				setScanStatus('Error checking scan status.');
			}
    	};

    	const interval = setInterval(checkScanStatus, 10000);

    	return () => clearInterval(interval);
  	}, [scanId]);

	const handleScanCancel = () => {
		setIsScanProgressModalOpen(false);
	};

	const handleScanNowModalPop = () => {
		setIsReadyToScanModalOpen(true);
	};

	const handleScanStartModalCancel = () => {
		setIsReadyToScanModalOpen(false);
	};

  	return (
		<div id='cookie-scanner' className='cookie-scanner'>
			<div className='scan-controls'>
				<div className='scan-controls-items'>
					<div className='scan-img'>
						<img src={scan} alt={'Scan cookie'} />
					</div>
					<div className='scan-actions'>
						<p>No scan history found</p>
						<h2>Start your first scan now</h2>
						<div className='scan-action-btns'>
							<button onClick={handleScanNowModalPop} disabled={isScanning} className='primary'>
								{isScanning ? 'Scanning...' : 'Scan Now'}
							</button>
							<button onClick={handleScan} disabled={isScanning} className='secondary'>
								History
							</button>
						</div>
					</div>
				</div>
				<p className='scan-info'><span>0 of 5</span> free scans used</p>
			</div>
			<div className='scan-schedule'>
				<img src={schedule} alt={'Comming soon'} />
			</div>
			<ReactModal
				isOpen={isReadyToScanModalOpen}
				onRequestClose={() => setIsReadyToScanModalOpen(false)}
				ariaHideApp={false}
				overlayClassName={`nx-cookies-list-integrations nx-cookies-scan-start`}
				style={modalStyle}
			>
				<>
					<div className="wprf-modal-table-wrapper wpsp-scan-start-body">
						<img src={scan_warning} alt={'Scan cookie warning'} />
						<h2>Ready to start scanning?</h2>
						<p className='scan-info'><span>0 of 5</span> free scans used</p>
						<p>
							Your existing cookie list (cookies discovered in the previous scan) will be replaced with the cookies discovered in this scan. Therefore, make sure you don’t exclude the pages that sets cookies.
						</p>
					</div>
					<div className="wprf-modal-preview-footer wpsp-scan-start-footer">
						<button className='wpsp-btn wpsp-btn-scan-secondary' onClick={handleScanStartModalCancel}>{__('Cancel', 'notificationx')}</button>
						<button className='wpsp-btn wpsp-btn-scan-primary' onClick={handleScan}>{__('Scan Now', 'notificationx')}</button>
					</div>
				</>
			</ReactModal>
			<ReactModal
				isOpen={isScanProgressModalOpen}
				onRequestClose={() => setIsScanProgressModalOpen(false)}
				ariaHideApp={false}
				overlayClassName={`nx-cookies-list-integrations nx-cookies-scan-progress`}
				style={modalStyle}
			>
				<>
					<div className="wprf-modal-table-wrapper wpsp-scan-progress-body">
						<img src={scan_danger} alt={'Scan cookie warning'} />
						<h2>Scan in Progress</h2>
						<p>A scan is already running. Please wait for it to complete before starting a new one.</p>
					</div>
					<div className="wprf-modal-preview-footer wpsp-scan-progress-footer">
						<button className='wpsp-btn wpsp-btn-scan-secondary' onClick={handleScanCancel}>{__('Cancel Scan', 'notificationx')}</button>
						<button className='wpsp-btn wpsp-btn-scan-primary' onClick={handleScan}>{__('Wait', 'notificationx')}</button>
					</div>
				</>
			</ReactModal>
		</div>
  	);
};

export default CookieScanner;
