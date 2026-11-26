import React, { useState, useEffect,useCallback } from 'react';
import ScannerHistory, { cleanScanHistory } from './ModalContent/ScannerHistory';
import HistoryMoreInfo from './ModalContent/HistoryMoreInfo';
import ReactModal from "react-modal";
import { modalStyle } from '../core/constants';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import { addCookiesAddedClass, cookieCategoryPrefix, formatDateTime } from '../frontend/gdpr/utils/helper';
import nxToast from '../core/ToasterMsg';
import { useNotificationXContext } from '../hooks';

const addCookiesToList = (builderContext: any, fieldName: string, newCookies: any[]) => {
  const existingCookies = builderContext.getFieldValue(fieldName) || [];
  const cookieMap = new Map();
  existingCookies.forEach((cookie: any) => {
    cookieMap.set(cookie.cookies_id, cookie);
  });
  newCookies.forEach((newCookie: any) => {
    cookieMap.set(newCookie.cookies_id, newCookie);
  });
  builderContext.setFieldValue(fieldName, Array.from(cookieMap.values()));
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

/** Wait between the end of one status request and the start of the next. */
export const SCAN_POLL_INTERVAL_MS = 5000;
/** Bad replies in a row (network error, 5xx, not JSON, no status) before the scan is given up. */
export const SCAN_MAX_BAD_REPLIES = 3;
/** Wall-clock limit for one scan, counted from the moment polling starts. */
export const SCAN_TIME_LIMIT_MS = 10 * 60 * 1000;

export type ScanStatusVerdict = 'completed' | 'failed' | 'pending' | 'bad';

/**
 * Sorts one reply from the scan status route.
 *
 * - completed: the scan is done and `body.data` holds the results.
 * - failed:    the scan cannot succeed: a `failed`/`error` status or any 4xx reply.
 * - pending:   any other status text; the scanner API's full status list is not
 *              documented, so an unknown status keeps polling until the time limit.
 * - bad:       no usable reply (network error, 5xx, not JSON, no status text).
 *
 * @param httpStatus HTTP status code, or 0 when the request itself failed.
 * @param body       Parsed JSON body, or null.
 */
export const classifyStatusResponse = (httpStatus: number, body: any): ScanStatusVerdict => {
  if (httpStatus >= 400 && httpStatus < 500) {
    return 'failed';
  }
  if (httpStatus < 200 || httpStatus >= 300) {
    return 'bad';
  }
  const status = body?.data?.status;
  if (typeof status !== 'string' || status === '') {
    return 'bad';
  }
  if (status === 'completed') {
    return 'completed';
  }
  if (status === 'failed' || status === 'error') {
    return 'failed';
  }
  return 'pending';
};

const CookieScanner = () => {
  const [scanMessage, setScanMessage] = useState('');
  const [status, setStatus] = useState('');
  const [results, setResults] = useState([]);
  const [isScanning, setIsScanning] = useState(false);
  const [scanId, setScanId] = useState<string | null>(null);
  const builderContext = useBuilderContext();
  const nxContext = useNotificationXContext();
  const [isReadyToScanModalOpen, setIsReadyToScanModalOpen] = useState(false);
  const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
  const [isDiscoveredCookieModalOpen, setIsDiscoveredCookieModalOpen] = useState(false);
  const [historyData, setHistoryData] = useState(() => cleanScanHistory(builderContext?.values?.scan_history));
  const [activeHistorydata, setActiveHistoryData] = useState(null);
  const [dotCount, setDotCount] = useState(0);

  const nx_id = builderContext?.values?.id;
  // Set your variables for used and total scans
  const [usedScans, setUsedScans] = useState(builderContext?.scan_data?.nx_scan_count || 0);
  const totalScans = 5;
  const scanInfo = builderContext?.scan_data?.scans_used.replace('%1$s', usedScans).replace('%2$s', totalScans);
  const scanDate = builderContext?.values?.last_scan_date;
  
  const handleScan = useCallback(async () => {
    const apiUrl = `${nxContext?.rest?.root + nxContext?.rest?.namespace }/scan`;    
    const currentDomain = window.location.origin;
    try {
      setIsScanning(true);
      setScanMessage('Scanning started...');
      setIsReadyToScanModalOpen(false);
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nxContext?.rest?.nonce,
        },
        body: JSON.stringify({ url: currentDomain }),
      });

      const res = await response.json().catch(() => null);
      const data = res?.data;

      if (response.ok && data?.scan_id) {
        setScanId(data.scan_id);
        setStatus(data.status || 'pending');
      } else {
        nxToast.error(__('Scan failed. Please try again.', 'notificationx'));
        setIsScanning(false);
      }
    } catch (error) {
      setIsScanning(false);
      nxToast.error(__('Scan failed. Please try again.', 'notificationx'));
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
  
  // Polls the status route for the current scan, one request at a time.
  // Runs only when scanId changes; the cleanup cancels the timers and the
  // request in flight (unmount, or scanId cleared when the scan ends).
  useEffect(() => {
    if (!scanId) return;

    const statusUrl = `${nxContext?.rest?.root + nxContext?.rest?.namespace }/scan/status`;
    const controller = new AbortController();
    const startedAt = Date.now();
    let cancelled = false;
    let badReplies = 0;
    let pollTimer: ReturnType<typeof setTimeout> | undefined;
    let limitTimer: ReturnType<typeof setTimeout> | undefined;

    const end = () => {
      cancelled = true;
      clearTimeout(pollTimer);
      clearTimeout(limitTimer);
      controller.abort();
      setIsScanning(false);
      setScanId(null);
    };

    const fail = (message: string) => {
      end();
      setStatus('failed');
      nxToast.error(message);
    };

    const failTimeLimit = () => fail(__('The scan took too long. Please try again.', 'notificationx'));

    const complete = (data: any) => {
      end();
      nxToast.info(__(`Scan complete! Your results are now available.`, 'notificationx'));
      setStatus('completed');
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
      setHistoryData( cleanScanHistory([...currentHistory, data]) );
    };

    const poll = async () => {
      let httpStatus = 0;
      let body = null;
      try {
        const response = await fetch(statusUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': nxContext?.rest?.nonce,
          },
          body: JSON.stringify({ scan_id: scanId, nx_id : nx_id  }),
          signal: controller.signal,
        });
        httpStatus = response.status;
        body = await response.json().catch(() => null);
      } catch (error) {
        // Network error or aborted: httpStatus stays 0, which counts as a bad reply.
      }
      if (cancelled) return;

      const verdict = classifyStatusResponse(httpStatus, body);
      if (verdict === 'completed') {
        complete(body.data);
        return;
      }
      if (verdict === 'failed') {
        fail(__('Scan failed. Please try again.', 'notificationx'));
        return;
      }
      badReplies = verdict === 'bad' ? badReplies + 1 : 0;
      if (badReplies >= SCAN_MAX_BAD_REPLIES) {
        fail(__('Scan failed. Please try again.', 'notificationx'));
        return;
      }
      // Background tabs throttle timers, so the limit is also checked against the clock.
      if (Date.now() - startedAt >= SCAN_TIME_LIMIT_MS) {
        failTimeLimit();
        return;
      }
      pollTimer = setTimeout(poll, SCAN_POLL_INTERVAL_MS);
    };

    limitTimer = setTimeout(failTimeLimit, SCAN_TIME_LIMIT_MS);
    pollTimer = setTimeout(poll, SCAN_POLL_INTERVAL_MS);

    return () => {
      cancelled = true;
      clearTimeout(pollTimer);
      clearTimeout(limitTimer);
      controller.abort();
    };
  }, [scanId]);

useEffect(() => {
  const interval = setInterval(() => {
    setDotCount(prev => (prev + 1) % 4); // cycles 0 to 3
  }, 500); // update every 500ms

  return () => clearInterval(interval);
}, []);


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
  
  const can_scan = builderContext?.is_pro_active || usedScans < 5;
  
  return (
    <div id='cookie-scanner' className='cookie-scanner'>
      <div className='scan-controls'>
        <div className='scan-controls-items'>
          <div className='scan-img'>
            { isScanning ? <img src={`https://notificationx.com/wp-content/uploads/2025/05/Cookie-Scan.gif`} alt={'Scan cookie'} />
            : <img src={`${nxContext.assets.admin}images/cookie-notice/scan.png`} alt={'Scan cookie'} /> }
          </div>
          <div className='scan-actions'>
            { scanDate ? <p>{ __('Last Successfully Scan','notificationx') }</p> : <p>{ __('No scan history found','notificationx') }</p> }
            { scanDate ? <h2>{ formatDateTime(scanDate) }</h2> : <h2>{ __('Start your first scan now','notificationx') }</h2> }
            <div className='scan-action-btns'>
              <button onClick={handleScanNowModalPop} disabled={ isScanning || !can_scan ? true : false} className='primary'>
                <span style={{ display: 'inline-block', width: '60px', textAlign:  isScanning ? 'left' : 'center', opacity : !can_scan ? '0.5' : '1' }}>
                  { !isScanning ? (
                    __('Scan Now','notificationx')
                  ) : (
                    <>
                      { __('Scanning','notificationx') }{'.'.repeat(dotCount)}
                      <span style={{ visibility: 'hidden' }}>...</span>
                    </>
                  )}
                </span>
              </button>
              <button onClick={ handleHistoryBtnClick } disabled={parseInt(usedScans) > 0 ? false : true } className={parseInt(usedScans) > 0 ? 'primary' : 'secondary' }>
                { __('History','notificationx') }
              </button>
            </div>
          </div>
        </div>
        { !builderContext?.is_pro_active && (
          !can_scan ? (
            <p>{__("Scan limit exceeded for this website.", 'notificationx')}</p>
          ) : (
            <p className="scan-info">{scanInfo}</p>
          )
        ) }
      </div>
      <div className='scan-schedule'>
        <img src={`${nxContext.assets.admin}images/cookie-notice/coming-soon.png`} alt={'Coming soon'} />
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
            <img src={`${nxContext.assets.admin}images/cookie-notice/scan-warning.png`} alt={'Scan cookie warning'} />
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
