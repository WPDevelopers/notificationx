import { useState, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const pad = (n) => String(n).padStart(2, "0");

const ZERO = { days: 0, hours: 0, minutes: 0, seconds: 0 };

/**
 * Text content of an HTML string. The value is a block attribute any
 * Contributor controls and was never kses'd, so it must not reach the editor
 * DOM as markup. DOMParser documents are inert: no scripts, no resource loads.
 * The frontend renders the kses'd HTML server-side.
 */
const htmlToText = (html) =>
  html ? new DOMParser().parseFromString(html, "text/html").body.textContent : "";

/**
 * Live ticking countdown for the editor preview. Mirrors the frontend logic
 * (assets/public/js/nx-countdown.js) but runs in React — it never touches the
 * vanilla frontend script (which would fight React over the DOM).
 */
function useCountdown(attributes) {
  const {
    countdownType,
    dueTime,
    evergreenHours,
    evergreenMinutes,
  } = attributes;

  const [remaining, setRemaining] = useState(ZERO);
  const [expired, setExpired] = useState(false);

  useEffect(() => {
    let target;
    if (countdownType === "evergreen") {
      const seconds =
        (parseInt(evergreenHours || 0, 10) * 3600) +
        (parseInt(evergreenMinutes || 0, 10) * 60);
      target = Date.now() + seconds * 1000;
    } else {
      target = dueTime ? new Date(dueTime).getTime() : 0;
    }

    const tick = () => {
      if (!target || isNaN(target)) {
        setRemaining(ZERO);
        setExpired(false);
        return;
      }
      const diff = target - Date.now();
      if (diff <= 0) {
        setRemaining(ZERO);
        setExpired(true);
        return;
      }
      setExpired(false);
      setRemaining({
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000),
      });
    };

    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [countdownType, dueTime, evergreenHours, evergreenMinutes]);

  return { remaining, expired };
}

export default function CountdownPreview({ blockId, attributes }) {
  const {
    layout,
    labelView,
    showSeparator,
    separatorStyle,
    showDays,
    daysLabel,
    showHours,
    hoursLabel,
    showMinutes,
    minutesLabel,
    showSeconds,
    secondsLabel,
    expireType,
    expiryTitle,
    expiryText,
  } = attributes;

  const { remaining, expired } = useCountdown(attributes);

  const listClasses = [
    "nx-countdown-container",
    `nx-countdown-layout-${layout || "grid"}`,
    labelView || "nx-countdown-label-block",
    showSeparator
      ? `nx-countdown-show-separator nx-countdown-separator-${separatorStyle}`
      : "",
  ]
    .filter(Boolean)
    .join(" ");

  const items = [
    { show: showDays, unit: "days", label: daysLabel, value: remaining.days },
    { show: showHours, unit: "hours", label: hoursLabel, value: remaining.hours },
    { show: showMinutes, unit: "minutes", label: minutesLabel, value: remaining.minutes },
    { show: showSeconds, unit: "seconds", label: secondsLabel, value: remaining.seconds },
  ];

  return (
    <div className={`${blockId} nx-countdown-wrapper`}>
      <ul id={`nx-countdown-${blockId}`} className={listClasses}>
        {expired && expireType === "text" ? (
          <div className="nx-countdown-finish-message">
            <h4 className="nx-expiry-title">{expiryTitle}</h4>
            <div className="nx-expiry-text">{htmlToText(expiryText)}</div>
          </div>
        ) : expired && expireType === "url" ? (
          <p>{__("Page will redirect to the given URL on the frontend.", "notificationx")}</p>
        ) : (
          items.map(
            (item) =>
              item.show && (
                <li className="nx-countdown-item" key={item.unit}>
                  <div className={`nx-countdown-${item.unit}`}>
                    <span className="nx-countdown-digits">{pad(item.value)}</span>
                    {item.label ? (
                      <span className="nx-countdown-label">{item.label}</span>
                    ) : null}
                  </div>
                </li>
              )
          )
        )}
      </ul>
    </div>
  );
}
