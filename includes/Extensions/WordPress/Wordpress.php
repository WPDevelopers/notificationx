<?php

namespace NotificationX\Extensions\WordPress;

/**
 * Common functionality for WordPres.
 */
trait WordPress
{
	public $module_priority = 2;

    public function doc(){
        return '<p>Make sure that you have a <a target="_blank" href="https://wordpress.org/">wordpress.org</a> account to use its campaign on blog comments, reviews and download stats data. For further assistance, check out our step by step documentation on <a target="_blank" href="https://notificationx.com/docs/wordpress-comment-popup-alert/">comments popup</a>, <a target="_blank" href="https://notificationx.com/docs/wordpress-plugin-review-notificationx/">plugin reviews</a> & <a target="_blank" href="https://notificationx.com/docs/wordpress-plugin-download-stats/">downloads stats</a>.</p>
		<p>🎦 Watch video tutorial on <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">blog comments</a>, <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">reviews</a> & <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">downloads stats</a> to learn quickly</p>
		<p><strong>Recommended Blogs:</strong></p>
		<p>🔥 Proven Hacks To <a target="_blank" href="https://notificationx.com/blog/hacks-to-get-more-comments-wordpress/">Get More Comments on Your WordPress Blog</a> Posts</p>
		<p>🚀 How To Increase <a target="_blank" href="https://wpdeveloper.net/wordpress-plugin-download/">WordPress Plugin Download Rates & Increase Sales</a> in 2020</p>';
    }
}
