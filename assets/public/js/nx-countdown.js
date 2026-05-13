/**
 * NotificationX Countdown Timer — Elementor Widget
 *
 * Hooks into elementorFrontend so the timer runs in both the live
 * editor preview and on the public-facing frontend.
 */

/* global elementorFrontend, localStorage */
(function ($) {
    'use strict';

    // Track active intervals so re-renders in the editor don't leak timers
    var activeIntervals = {};

    var NXCountdown = function NXCountdown($scope) {
        var $wrapper   = $scope.find('.nx-countdown-wrapper').eq(0);
        if (!$wrapper.length) return;

        var widgetId     = $wrapper.data('countdown-id') || '';
        var countdownType= $wrapper.data('countdown-type') || 'due_date';
        var expireType   = $wrapper.data('expire-type')   || 'none';
        var expireTitle  = $wrapper.data('expiry-title')  || '';
        var expireText   = $wrapper.data('expiry-text')   || '';
        var redirectUrl  = $wrapper.data('redirect-url')  || '';
        var evergreenTime= parseInt($wrapper.data('evergreen-time') || 0, 10); // seconds
        var recurring    = $wrapper.data('evergreen-recurring');
        var recurringStop= $wrapper.data('evergreen-recurring-stop') || '';

        var $list        = $scope.find('#nx-countdown-' + widgetId);
        var $daysEl      = $list.find('[data-days]');
        var $hoursEl     = $list.find('[data-hours]');
        var $minutesEl   = $list.find('[data-minutes]');
        var $secondsEl   = $list.find('[data-seconds]');

        // Clear any previous interval for this widget (editor re-renders)
        if (activeIntervals[widgetId]) {
            clearInterval(activeIntervals[widgetId]);
            delete activeIntervals[widgetId];
        }

        var targetTime;
        var intervalId;

        // ── Determine target timestamp ────────────────────────────────────
        if (countdownType === 'evergreen') {
            var storageKeyTime     = 'nx_countdown_evergreen_time_'     + widgetId;
            var storageKeyInterval = 'nx_countdown_evergreen_interval_' + widgetId;
            var storedTime         = localStorage.getItem(storageKeyTime);
            var storedInterval     = localStorage.getItem(storageKeyInterval);
            var HOUR_MS            = 60 * 60 * 1000;

            // Reset if the evergreen duration changed or no stored value
            if (storedTime === null || storedInterval === null || parseInt(storedInterval, 10) !== evergreenTime) {
                storedTime = Date.now() + evergreenTime * 1000;
                localStorage.setItem(storageKeyInterval, String(evergreenTime));
                localStorage.setItem(storageKeyTime, String(storedTime));
            }

            storedTime = parseInt(storedTime, 10);

            if (recurring !== undefined && recurring !== false) {
                var recurringAfterMs = parseFloat(recurring) * HOUR_MS;
                // If enough time has passed, restart
                if (storedTime + recurringAfterMs < Date.now()) {
                    storedTime = Date.now() + evergreenTime * 1000;
                    localStorage.setItem(storageKeyTime, String(storedTime));
                }
                // Cap at the recurring stop time
                if (recurringStop) {
                    var stopTs = new Date(recurringStop).getTime();
                    if (!isNaN(stopTs) && stopTs < storedTime) {
                        storedTime = stopTs;
                    }
                }
            }

            targetTime = storedTime;

        } else {
            // due_date — date string is stored on the <ul> element
            var dateStr = $list.data('date') || '';
            targetTime  = dateStr ? new Date(dateStr).getTime() : 0;
        }

        // ── Expiry handler ────────────────────────────────────────────────
        function handleExpiry() {
            clearInterval(intervalId);
            delete activeIntervals[widgetId];

            if (expireType === 'text') {
                $list.html(
                    '<div class="nx-countdown-finish-message">' +
                        '<h4 class="nx-expiry-title">' + expireTitle + '</h4>' +
                        '<div class="nx-expiry-text">' + expireText + '</div>' +
                    '</div>'
                );
            } else if (expireType === 'url') {
                if (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode()) {
                    $list.html('<p>Page will redirect to the given URL on the frontend.</p>');
                } else {
                    window.location.href = redirectUrl;
                }
            }
            // 'none' — do nothing
        }

        // ── Pad helper ────────────────────────────────────────────────────
        function pad(n) {
            return String(n).padStart(2, '0');
        }

        // ── Tick ──────────────────────────────────────────────────────────
        function tick() {
            var now  = Date.now();
            var diff = targetTime - now;

            if (diff <= 0) {
                // Show zeros then handle expiry
                if ($daysEl.length)    $daysEl.text('00');
                if ($hoursEl.length)   $hoursEl.text('00');
                if ($minutesEl.length) $minutesEl.text('00');
                if ($secondsEl.length) $secondsEl.text('00');
                handleExpiry();
                return;
            }

            var days    = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);

            if ($daysEl.length)    $daysEl.text(pad(days));
            if ($hoursEl.length)   $hoursEl.text(pad(hours));
            if ($minutesEl.length) $minutesEl.text(pad(minutes));
            if ($secondsEl.length) $secondsEl.text(pad(seconds));
        }

        if (!targetTime) return;

        tick(); // immediate first render
        intervalId = setInterval(tick, 1000);
        activeIntervals[widgetId] = intervalId;
    };

    // Register with Elementor — fires in BOTH editor preview and frontend
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/nx-countdown-timer.default',
            NXCountdown
        );
    });

}(jQuery));
