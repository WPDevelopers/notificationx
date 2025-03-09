import React, { useState, useEffect } from 'react';

const CookieScanner = () => {
  const [scanStatus, setScanStatus] = useState('');
  const [isScanning, setIsScanning] = useState(false);
  const [scanId, setScanId] = useState(null);

  const handleScan = async () => {
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

  return (
    <div>
      <button onClick={handleScan} disabled={isScanning}>
        {isScanning ? 'Scanning...' : 'Scan Now'}
      </button>
      <p>{scanStatus}</p>
    </div>
  );
};

export default CookieScanner;
