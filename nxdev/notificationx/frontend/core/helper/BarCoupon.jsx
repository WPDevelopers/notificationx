import React, { useState } from 'react';

const BarCoupon = ({ settings }) => {
  const [hovered, setHovered] = useState(false);
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    navigator.clipboard.writeText(settings?.coupon_code || '');
    setCopied(true);
    setTimeout(() => setCopied(false), 2000); // Hide "Copied!" after 2s
  };

  return (
    <div
      className="nx-bar-coupon-wrapper"
      style={{ position: 'relative', display: 'inline-block', marginLeft: '10px' }}
    >
      {/* Coupon Button */}
      <div
        className="nx-bar-coupon"
        style={{
          border: `1px solid ${settings?.coupon_border_color || "#ccc"}`,
          backgroundColor: settings?.coupon_bg_color || "#f9f9f9",
          color: settings?.coupon_text_color || "#000",
          padding: '6px 12px',
          borderRadius: '4px',
          display: 'inline-flex',
          alignItems: 'center',
          gap: '8px',
          cursor: 'pointer',
          fontSize: '14px',
          userSelect: 'none',
        }}
        onMouseEnter={() => setHovered(true)}
        onMouseLeave={() => setHovered(false)}
        onClick={handleCopy}
      >
        <strong>{settings?.coupon_text}</strong>
      </div>

      {/* Tooltip (on hover OR after copied) */}
      {(hovered || copied) && (
        <div
          className="coupon-tooltip"
          style={{
            position: 'absolute',
            top: '-28px',
            left: '50%',
            transform: 'translateX(-50%)',
            backgroundColor: '#333',
            color: '#fff',
            padding: '4px 8px',
            borderRadius: '4px',
            fontSize: '12px',
            whiteSpace: 'nowrap',
            opacity: 1,
            transition: 'opacity 0.3s ease',
            pointerEvents: 'none',
            zIndex: 9999,
          }}
        >
          {copied
            ? settings?.coupon_copied_text || "Copied!"
            : settings?.coupon_tooltip || "Click to copy"}
        </div>
      )}
    </div>
  );
};

export default BarCoupon;
