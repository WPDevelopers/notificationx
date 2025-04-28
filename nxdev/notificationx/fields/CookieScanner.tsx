import React, { useState, useEffect,useCallback } from 'react';
import ScannerHistory from './ModalContent/ScannerHistory';
import HistoryMoreInfo from './ModalContent/HistoryMoreInfo';
import ReactModal from "react-modal";
import { modalStyle } from '../core/constants';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import { addCookiesAddedClass, cookieCategoryPrefix, formatDateTime } from '../frontend/gdpr/utils/helper';
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
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
  const [isDiscoveredCookieModalOpen, setIsDiscoveredCookieModalOpen] = useState(false);
  const [historyData, setHistoryData] = useState(builderContext?.values?.scan_history || []);
  const [activeHistorydata, setActiveHistoryData] = useState(null);
  

  const nx_id = builderContext?.values?.id;
  // Set your variables for used and total scans
  const [usedScans, setUsedScans] = useState(builderContext?.scan_data?.nx_scan_count || 0);
  const totalScans = 5;
  const scanInfo = builderContext?.scan_data?.scans_used.replace('%1$s', usedScans).replace('%2$s', totalScans);
  const scanDate = builderContext?.scan_data?.scan_date;
  
  const handleScan = useCallback(async () => {
    const apiUrl = `${builderContext?.rest?.root + builderContext?.rest?.namespace }/scan`;    
    const currentDomain = window.location.origin;
    try {
      setIsScanning(true);
      setScanMessage('Scanning started...');
      setIsReadyToScanModalOpen(false);
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

	const handleScanNowModalPop = () => {
		setIsReadyToScanModalOpen(true);
	};

	const handleScanStartModalCancel = () => {
		setIsReadyToScanModalOpen(false);
	};

	const handleHistoryBtnClick = () => {
		setIsHistoryModalOpen(true);
	};

	const handleScannedCookieView = (data) => {
    setActiveHistoryData(data);
		setIsDiscoveredCookieModalOpen(true);
	};  
  
  useEffect(() => {
    if (!scanId || status === 'completed') return;

    const checkScanStatus = async () => {
        try {
            const statusUrl = `${builderContext?.rest?.root + builderContext?.rest?.namespace }/scan/status`;
            const response = await fetch(statusUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ scan_id: scanId, nx_id : nx_id  }),
            });
            const res = await response.json();
            const data = res?.data;
            if (data?.status === 'completed') {
                nxToast.info(__(`Scan complete! Your results are now available.`, 'notificationx'));
                setIsScanning(false);
                setStatus('completed');
                clearInterval(interval);
                setResults(data?.cookies);
                // Get current scan history from context
                const currentHistory = builderContext?.values?.scan_history || [];
                builderContext.setFieldValue(
                  "scan_history",
                  [...currentHistory, data]
                );
                builderContext.setFieldValue(
                  "last_scan_date",
                  data?.last_scan_date
                );
                setHistoryData( [...currentHistory, data] );
            }
        } catch (error) {
            nxToast.info(__(`Scan failed! ${error?.data?.message}.`, 'notificationx'));
            // Get current scan history from context
            const currentHistory = builderContext?.values?.scan_history || [];
            builderContext.setFieldValue(
              "scan_history",
              [...currentHistory, error?.data]
            );
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
    advertising  : [],
    uncategorized: [],
  };
  
  cookieList.forEach((cookie) => {
    let category = 'uncategorized';
    // @ts-ignore 
    category = Object.keys(cookieCategoryPrefix).find((key) =>
      cookieCategoryPrefix[key].some((name) => cookie?.cookies_id.includes(name))
    ) || 'uncategorized'; // fallback to uncategorized if find() returns undefined
  
    if (category === 'advertisement') {
      category = 'advertising'; // remap advertisement -> advertising
    }
  
    // Initialize the array if it doesn't exist
    if (!categorizedCookies[category]) {
      categorizedCookies[category] = [];
    }
  
    categorizedCookies[category].push(cookie);
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
            { scanDate ? <p>{ __('Last Successfully Scan','notificationx') }</p> : <p>{ __('No scan history found','notificationx') }</p> }
            { scanDate ? <h2>{ formatDateTime(scanDate) }</h2> : <h2>{ __('Start your first scan now','notificationx') }</h2> }
            <div className='scan-action-btns'>
              <button onClick={handleScanNowModalPop} disabled={isScanning} className='primary'>
                {isScanning ? 'Scanning...' : 'Scan Now'}
              </button>
              <button onClick={ handleHistoryBtnClick } disabled={parseInt(usedScans) > 0 ? false : true } className={parseInt(usedScans) > 0 ? 'primary' : 'secondary' }>
                { __('History','notificationx') }
              </button>
            </div>
          </div>
        </div>
        {!builderContext?.is_pro_active && (
          usedScans >= 5 ? (
            <p>{__("Scan limit exceeded for this website.", 'notificationx')}</p>
          ) : (
            <p className="scan-info">{scanInfo}</p>
          )
        )}
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
            <h2>{ __('Ready to start scanning?','notificationx') }</h2>
            { !builderContext?.is_pro_active &&
              <p className='scan-info'>{scanInfo}</p>
            }
            <p>{ __('Your existing cookie list (cookies discovered in the previous scan) will be replaced with the cookies discovered in this scan. Therefore, make sure you don’t exclude the pages that sets cookies.','notificationx') }</p>
          </div>
          <div className="wprf-modal-preview-footer wpsp-scan-start-footer">
            <button className='wpsp-btn wpsp-btn-scan-secondary' onClick={handleScanStartModalCancel}>{__('Cancel', 'notificationx')}</button>
            <button className='wpsp-btn wpsp-btn-scan-primary' onClick={handleScan}>{__('Scan Now', 'notificationx')}</button>
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
            handleScannedCookieView={handleScannedCookieView} 
            historyData={historyData}
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
          <HistoryMoreInfo setIsDiscoveredCookieModalOpen={setIsDiscoveredCookieModalOpen} data={activeHistorydata} />
        </>
      </ReactModal>
    </div>
  );
};

export default CookieScanner;
