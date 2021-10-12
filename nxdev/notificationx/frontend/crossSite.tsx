import './index';
import momentLib from 'moment';
import 'moment-timezone/moment-timezone';
import 'moment-timezone/moment-timezone-utils';

(function(notificationX){
    const WP_ZONE = 'WP';
    // Create WP timezone based off dateSettings.
	momentLib.tz.add(
		momentLib.tz.pack( {
			name: WP_ZONE,
			abbrs: [ WP_ZONE ],
			untils: [ null ],
			offsets: [ -notificationX.gmt_offset * 60 || 0 ],
		} )
	);
// @ts-ignore
})(window.notificationX);