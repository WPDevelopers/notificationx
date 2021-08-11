<?php

/**
 * Freemius Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Freemius;

/**
 * Common functionality for Freemius.
 */
trait Freemius
{

    public $module_priority = 12;

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://dashboard.freemius.com/login/">created & signed in to Freemius account</a> to use its campaign & product sales data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/freemius-sales-notification/">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="https://youtu.be/0uANsOSFmtw">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/freemius/">Integration with Freemius</a></p>';
    }
}
