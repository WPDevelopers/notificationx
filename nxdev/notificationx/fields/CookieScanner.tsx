import React, { useState, useEffect,useCallback } from 'react';
import CloseIcon from '../icons/Close';
import ScannerHistory from './ModalContent/ScannerHistory';
import DiscoveredCookies from '../icons/DiscoveredCookies';
import ReactModal from "react-modal";
import { modalStyle } from '../core/constants';
import { __ } from '@wordpress/i18n';
import warning from 'tiny-warning';
import { useBuilderContext } from 'quickbuilder';
import { addCookiesAddedClass, cookieCategoryPrefix } from '../frontend/gdpr/utils/helper';
import nxToast from '../core/ToasterMsg';

const addCookiesToList = (builderContext: any, fieldName: string, newCookies: any[]) => {
  const existingCookies = builderContext.getFieldValue(fieldName) || [];
  const updatedCookies = [...existingCookies, ...newCookies];
  builderContext.setFieldValue(fieldName, updatedCookies);
};

const processCookies = (cookiesData: any) => {
  return Object.values(cookiesData).map((cookie: any) => ({
    enabled   : true,
    discovered: true,
    cookies_id: cookie.name,
    domain    : cookie.domain,
    duration  : cookie.expires == -1
      ? 0 
               :  Math.max(1, Math.floor((cookie.expires - Math.floor(Date.now() / 1000)) / 86400)),
    description: cookie?.description
  }));
};

const CookieScanner = () => {
  const [scanMessage, setScanMessage] = useState('');
  const [status, setStatus] = useState('');
  const [results, setResults] = useState([]);
  const [isScanning, setIsScanning] = useState(false);
  const [scanId, setScanId] = useState<string | null>(null);
  const builderContext = useBuilderContext();
  const [isReadyToScanModalOpen, setIsReadyToScanModalOpen] = useState(false);
  const [isScanProgressModalOpen, setIsScanProgressModalOpen] = useState(false);
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
  const [isDiscoveredCookieModalOpen, setIsDiscoveredCookieModalOpen] = useState(false);
  
	const dummyHistoryData = [
		{
		 	 "scan_date": "03 March 2025 05:06:24",
			"scan_status": "Completed",
			"category": "02",
			"cookies": 23,
			"scripts": "01",
			"more_info": "View"
		},
	];
	const dummyDiscoverCookies = [
		{
			"id": "wordpress_3e130031865ca4e6...",
			"first_found_url": "example.com/whyweneedex",
			"duration": "Past",
			"description": "No Description found"
		}
	]
  
  const handleScan = useCallback(async () => {
    const apiUrl = `${builderContext?.rest?.root + builderContext?.rest?.namespace }/scan`;    
    const currentDomain = window.location.origin;
    try {
      setIsScanning(true);
      setScanMessage('Scanning started...');
      setIsReadyToScanModalOpen(false);
      // setIsScanProgressModalOpen(true);
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: currentDomain }),
      });

      const res = await response.json();
      const data = res?.data;

      if (data?.scan_id) {
        setScanId(data.scan_id);
        setStatus(data.status || 'pending');
      } else {
        nxToast.error(__(`Scan initiation failed. ${data.error}`, 'notificationx'));
        setIsScanning(false);
      }
    } catch (error) {
      setIsScanning(false);
      nxToast.error(__(`Scan failed. Try again.`, 'notificationx'));
    }
  }, []);

	const handleScanCancel = () => {
		setIsScanProgressModalOpen(false);
	};

	const handleScanNowModalPop = () => {
		setIsReadyToScanModalOpen(true);
	};

	const handleScanStartModalCancel = () => {
		setIsReadyToScanModalOpen(false);
	};

	const handleHistoryBtnClick = () => {
		setIsHistoryModalOpen(true);
	};

	const handleScannedCookieView = () => {
		setIsDiscoveredCookieModalOpen(true);
	};

  
  useEffect(() => {
    if (!scanId || status === 'completed') return;

    const checkScanStatus = async () => {
        try {
            const statusUrl = `${builderContext?.rest?.root + builderContext?.rest?.namespace }/scan/status?scan_id=${scanId}`;
            const response = await fetch(statusUrl);
            const res = await response.json();
            const data = res?.data;
            setStatus(data?.status || 'unknown');

            if (data?.status === 'completed') {
                nxToast.info(__(`Scan complete! Your results are now available.`, 'notificationx'));
                setIsScanning(false);
                setScanId(null);
                setResults(data?.result);
                setStatus('completed');
                clearInterval(interval);
            }
        } catch (error) {
            nxToast.info(__(`Scan failed! ${error}.`, 'notificationx'));
            setIsScanning(false);
            clearInterval(interval);
        }
    };

    const interval = setInterval(checkScanStatus, 5000);

    return () => clearInterval(interval);
}, [scanId, status]);



useEffect(() => {
  if (status !== 'completed') return;
  const cookieList = processCookies(results);
  // Initialize categorized lists
  const categorizedCookies = {
    necessary    : [],
    functional   : [],
    analytics    : [],
    performance  : [],
    advertisement: [],
    uncategorized: [],
  };
  
  // Categorize cookies efficiently
  cookieList.forEach((cookie) => {
    const category = Object.keys(cookieCategoryPrefix).find((key) =>
      cookieCategoryPrefix[key].some((name) => cookie?.cookies_id.includes(name))
    );
    categorizedCookies[category || 'uncategorized'].push(cookie);
  });

  // Store cookies in respective lists
  Object.entries(categorizedCookies).forEach(([key, list]) => {
    addCookiesToList(builderContext, `${key}_cookie_lists`, list);
  });

  // Filter based on the length of each array
  const findsOn = Object.entries(categorizedCookies)
      .filter(([key, value]) => value.length > 0)
      .map(([key, value]) => key);
    if( findsOn.length > 0 ) {
      addCookiesAddedClass(findsOn);
    }
}, [status, results]);

  return (
    <div id='cookie-scanner' className='cookie-scanner'>
      <div className='scan-controls'>
        <div className='scan-controls-items'>
          <div className='scan-img'>
            <img src={`${builderContext.assets.admin}images/cookie-notice/scan.png`} alt={'Scan cookie'} />
          </div>
          <div className='scan-actions'>
            <p>No scan history found</p>
            <h2>Start your first scan now</h2>
            <div className='scan-action-btns'>
              <button onClick={handleScanNowModalPop} disabled={isScanning} className='primary'>
                {isScanning ? 'Scanning...' : 'Scan Now'}
              </button>
              <button onClick={handleHistoryBtnClick} disabled={isScanning} className='secondary'>
                History
              </button>
            </div>
          </div>
        </div>
        <p className='scan-info'><span>0 of 5</span> free scans used</p>
      </div>
      <div className='scan-schedule'>
        <img src={`${builderContext.assets.admin}images/cookie-notice/coming-soon.png`} alt={'Coming soon'} />
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
            <img src={`${builderContext.assets.admin}images/cookie-notice/scan-warning.png`} alt={'Scan cookie warning'} />
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
            <img src={`${builderContext.assets.admin}images/cookie-notice/scan-danger.png`} alt={'Scan cookie warning'} />
            <h2>Scan in Progress</h2>
            <p>A scan is already running. Please wait for it to complete before starting a new one.</p>
          </div>
          <div className="wprf-modal-preview-footer wpsp-scan-progress-footer">
            <button className='wpsp-btn wpsp-btn-scan-secondary' onClick={handleScanCancel}>{__('Cancel Scan', 'notificationx')}</button>
            <button className='wpsp-btn wpsp-btn-scan-primary' onClick={handleScan}>{__('Wait', 'notificationx')}</button>
          </div>
        </>
      </ReactModal>
      <ReactModal
        isOpen={isHistoryModalOpen}
        onRequestClose={() => setIsHistoryModalOpen(false)}
        ariaHideApp={false}
        overlayClassName={`nx-cookies-list-integrations nx-cookies-history`}
        style={modalStyle}
      >
        <>
          <ScannerHistory 
            setIsHistoryModalOpen={setIsHistoryModalOpen} 
            dummyHistoryData={dummyHistoryData} 
            handleScannedCookieView={handleScannedCookieView} 
          />
        </>
      </ReactModal>
      <ReactModal
        isOpen={isDiscoveredCookieModalOpen}
        onRequestClose={() => setIsDiscoveredCookieModalOpen(false)}
        ariaHideApp={false}
        overlayClassName={`nx-cookies-list-integrations nx-cookies-discovered`}
        style={modalStyle}
      >
        <>
          <div className="wprf-modal-preview-header">
            <div className='header-details'>
              <div className='icon-wrap'>
                <DiscoveredCookies />
              </div>
              <span>{ __( 'Discovered cookies','notificationx' ) }</span>
            </div>
              <button onClick={() => setIsDiscoveredCookieModalOpen(false)}>
                  <CloseIcon />
              </button>
          </div>
          <div className="wprf-modal-table-wrapper wpsp-better-repeater-fields">
            <table>
                  <thead>
                <tr>
                  <th>ID</th>
                  <th>First found URL</th>
                  <th>Duration</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                {dummyDiscoverCookies.map((data, index) => (
                  <tr key={index}>
                    <td>{data.id}</td>
                    <td>{data.first_found_url}</td>
                    <td>{data.duration}</td>
                    <td>{data.description}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      </ReactModal>
    </div>
  );
};

export default CookieScanner;
