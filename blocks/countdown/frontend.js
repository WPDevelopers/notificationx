/**
 * NotificationX Countdown Timer — Gutenberg block (frontend).
 *
 * Vanilla port of assets/public/js/nx-countdown.js (which is Elementor-only).
 * Initializes every block-rendered `.nx-countdown-wrapper` on the page and runs
 * the identical due-date / evergreen / expiry logic, without jQuery or Elementor.
 */
(function () {
  "use strict";

  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function initCountdown(wrapper) {
    var widgetId = wrapper.getAttribute("data-countdown-id") || "";
    var countdownType = wrapper.getAttribute("data-countdown-type") || "due_date";
    var expireType = wrapper.getAttribute("data-expire-type") || "none";
    // Server-rendered (kses'd) expiry message; cloned as DOM, never re-parsed.
    var expiryTemplate = wrapper.querySelector("template.nx-countdown-expiry");
    var redirectUrl = wrapper.getAttribute("data-redirect-url") || "";
    var evergreenTime = parseInt(wrapper.getAttribute("data-evergreen-time") || 0, 10);
    var recurring = wrapper.getAttribute("data-evergreen-recurring");
    var recurringStop = wrapper.getAttribute("data-evergreen-recurring-stop") || "";

    var list = wrapper.querySelector(".nx-countdown-container");
    if (!list) return;

    var daysEl = list.querySelector("[data-days]");
    var hoursEl = list.querySelector("[data-hours]");
    var minutesEl = list.querySelector("[data-minutes]");
    var secondsEl = list.querySelector("[data-seconds]");

    var targetTime;

    if (countdownType === "evergreen") {
      var storageKeyTime = "nx_countdown_evergreen_time_" + widgetId;
      var storageKeyInterval = "nx_countdown_evergreen_interval_" + widgetId;
      var storedTime = localStorage.getItem(storageKeyTime);
      var storedInterval = localStorage.getItem(storageKeyInterval);
      var HOUR_MS = 60 * 60 * 1000;

      if (
        storedTime === null ||
        storedInterval === null ||
        parseInt(storedInterval, 10) !== evergreenTime
      ) {
        storedTime = Date.now() + evergreenTime * 1000;
        localStorage.setItem(storageKeyInterval, String(evergreenTime));
        localStorage.setItem(storageKeyTime, String(storedTime));
      }

      storedTime = parseInt(storedTime, 10);

      if (recurring !== null && recurring !== "" && recurring !== "false") {
        var recurringAfterMs = parseFloat(recurring) * HOUR_MS;
        if (storedTime + recurringAfterMs < Date.now()) {
          storedTime = Date.now() + evergreenTime * 1000;
          localStorage.setItem(storageKeyTime, String(storedTime));
        }
        if (recurringStop) {
          var stopTs = new Date(recurringStop).getTime();
          if (!isNaN(stopTs) && stopTs < storedTime) {
            storedTime = stopTs;
          }
        }
      }

      targetTime = storedTime;
    } else {
      var dateStr = list.getAttribute("data-date") || "";
      targetTime = dateStr ? new Date(dateStr).getTime() : 0;
    }

    if (!targetTime || isNaN(targetTime)) return;

    var intervalId;

    function handleExpiry() {
      clearInterval(intervalId);
      if (expireType === "text") {
        list.textContent = "";
        if (expiryTemplate && expiryTemplate.content) {
          list.appendChild(document.importNode(expiryTemplate.content, true));
        }
      } else if (expireType === "url" && redirectUrl) {
        window.location.href = redirectUrl;
      }
    }

    function tick() {
      var diff = targetTime - Date.now();

      if (diff <= 0) {
        if (daysEl) daysEl.textContent = "00";
        if (hoursEl) hoursEl.textContent = "00";
        if (minutesEl) minutesEl.textContent = "00";
        if (secondsEl) secondsEl.textContent = "00";
        handleExpiry();
        return;
      }

      var days = Math.floor(diff / (1000 * 60 * 60 * 24));
      var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((diff % (1000 * 60)) / 1000);

      if (daysEl) daysEl.textContent = pad(days);
      if (hoursEl) hoursEl.textContent = pad(hours);
      if (minutesEl) minutesEl.textContent = pad(minutes);
      if (secondsEl) secondsEl.textContent = pad(seconds);
    }

    tick();
    intervalId = setInterval(tick, 1000);
  }

  function initAll() {
    var wrappers = document.querySelectorAll(".nx-countdown-wrapper");
    for (var i = 0; i < wrappers.length; i++) {
      var wrapper = wrappers[i];
      // Leave Elementor-rendered timers to the Elementor script.
      if (wrapper.closest && wrapper.closest(".elementor-widget")) continue;
      if (wrapper.getAttribute("data-nx-countdown-init")) continue;
      wrapper.setAttribute("data-nx-countdown-init", "1");
      initCountdown(wrapper);
    }
  }

  // Run once on initial page load.
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }

  // NotificationX injects press bars into the DOM after page load and calls
  // window.ebRunCountDown() once a bar is mounted. Expose initAll under that
  // hook so block countdowns inside a dynamically-injected bar — e.g. on the
  // preview screen, where the bar's assets aren't re-loaded — get initialized.
  // Chain any pre-existing hook (e.g. Essential Blocks) instead of clobbering
  // it; initAll() is idempotent via the data-nx-countdown-init guard.
  var prevRunCountDown =
    typeof window.ebRunCountDown === "function" ? window.ebRunCountDown : null;
  window.ebRunCountDown = function () {
    if (prevRunCountDown) {
      try {
        prevRunCountDown();
      } catch (e) {}
    }
    initAll();
  };

  // Fallback: watch for late-injected countdowns directly, in case a bar is
  // mounted without the hook firing. Guarded to a single observer per page and
  // scoped so it only does work when a countdown wrapper actually appears.
  if (
    !window.__nxCountdownObserverAttached &&
    typeof MutationObserver !== "undefined"
  ) {
    window.__nxCountdownObserverAttached = true;

    var hasCountdown = function (node) {
      if (!node || node.nodeType !== 1) return false;
      if (node.classList && node.classList.contains("nx-countdown-wrapper")) {
        return true;
      }
      return (
        typeof node.querySelector === "function" &&
        !!node.querySelector(".nx-countdown-wrapper")
      );
    };

    var pending = false;
    var observer = new MutationObserver(function (mutations) {
      if (pending) return;
      for (var i = 0; i < mutations.length; i++) {
        var added = mutations[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          if (hasCountdown(added[j])) {
            pending = true;
            setTimeout(function () {
              pending = false;
              initAll();
            }, 50);
            return;
          }
        }
      }
    });

    var startObserving = function () {
      if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
      }
    };
    if (document.body) {
      startObserving();
    } else {
      document.addEventListener("DOMContentLoaded", startObserving);
    }
  }
})();
