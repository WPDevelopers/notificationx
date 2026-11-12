/**
 * GOLDEN: the template rows GetTemplate() returns today for every existing
 * theme slug. Captured on the unchanged code (84707 step 0) so later changes
 * (the Verified Card themes) can prove existing themes are byte-for-byte the
 * same. Never update these snapshots with -u to make a change pass.
 */
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';

// Representative settings per type, mirroring the PHP theme defaults.
const SALES = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just purchased',
	third_param: 'tag_product_title',
	custom_third_param: 'Anonymous Product',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const SALES_COUNT = {
	first_param: 'tag_sales_count',
	second_param: 'Buyers purchased',
	third_param: 'tag_product_title',
	fourth_param: 'tag_7days',
};
const MAPS = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'from',
	third_param: 'tag_city',
	custom_third_param: 'Location',
	map_fourth_param: 'purchased',
	fourth_param: 'tag_title',
	custom_fourth_param: 'Anonymous Title',
	fifth_param: 'tag_time',
	custom_fifth_param: 'Some time ago',
};
const FREEMIUS = {
	...SALES,
	fifth_param: 'tag_time',
	freemius_fifth_param: 'tag_plan_title',
	custom_freemius_fifth_param: 'Pro Plus',
	freemius_sixth_param: 'Subscription For',
	freemius_seventh_param: 'tag_pricing',
	custom_freemius_seventh_param: '99 USD/Year',
};
const FREEMIUS_MAPS = { ...MAPS, ...FREEMIUS, fourth_param: 'tag_title', fifth_param: 'tag_time' };
const COMMENTS = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'commented on',
	third_param: 'tag_post_title',
	custom_third_param: 'Anonymous Post',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const COMMENTS_TEXT = { ...COMMENTS, third_param: 'tag_post_comment' };
const TOTAL_RATED = {
	first_param: 'tag_rated',
	custom_first_param: 'Someone',
	second_param: 'people rated',
	third_param: 'tag_plugin_name',
	fourth_param: 'tag_rating',
	custom_fourth_param: 'Some time ago',
};
const REVIEWED = { ...TOTAL_RATED, first_param: 'tag_username', second_param: 'just reviewed' };
const REVIEW_SAYING = {
	first_param: 'tag_username',
	custom_first_param: 'Someone',
	second_param: 'saying',
	third_param: 'tag_title',
	custom_third_param: 'Excellent',
	review_fourth_param: 'about',
	fifth_param: 'tag_plugin_name',
	sixth_param: 'tag_custom',
	custom_sixth_param: 'Try it now',
};
const REVIEW_COMMENT = {
	first_param: 'tag_username',
	custom_first_param: 'Someone',
	second_param: 'just reviewed',
	third_param: 'tag_plugin_review',
	fourth_param: 'tag_rating',
	custom_fourth_param: 'Some time ago',
};
const REVIEW_COMMENT_3 = { ...REVIEW_COMMENT, fourth_param: 'tag_time' };
const DL_TODAY = {
	first_param: 'tag_plugin_theme_name',
	second_param: 'has been downloaded',
	third_param: 'tag_today',
	fourth_param: 'tag_today_text',
};
const DL_7DAY = { ...DL_TODAY, third_param: 'tag_last_week', fourth_param: 'tag_last_week_text' };
const DL_ACTIVE = {
	first_param: 'tag_active_installs',
	second_param: 'people are actively using',
	third_param: 'tag_plugin_theme_name',
};
const DL_TOTAL = { ...DL_TODAY, third_param: 'tag_all_time', fourth_param: 'tag_all_time_text' };

const conv = ( slug, tmpl = SALES ) => ( {
	themes: `conversions_${ slug }`,
	source: 'woocommerce',
	type: 'conversions',
	'notification-template': tmpl,
} );
const wooSales = ( slug, tmpl = SALES ) => ( {
	themes: `woocommerce_sales_${ slug }`,
	source: 'woocommerce_sales',
	type: 'woocommerce_sales',
	'notification-template': tmpl,
} );
const freemius = ( slug, tmpl = FREEMIUS ) => ( {
	themes: `conversions_${ slug }`,
	source: 'freemius_conversions',
	type: 'conversions',
	'notification-template': tmpl,
} );
const comments = ( slug, tmpl = COMMENTS ) => ( {
	themes: `comments_${ slug }`,
	source: 'wp_comments',
	type: 'comments',
	'notification-template': tmpl,
} );
const reviews = ( slug, tmpl ) => ( {
	themes: `reviews_${ slug }`,
	source: 'wp_reviews',
	type: 'reviews',
	'notification-template': tmpl,
} );
const stats = ( slug, tmpl ) => ( {
	themes: `download_stats_${ slug }`,
	source: 'wp_stats',
	type: 'download_stats',
	'notification-template': tmpl,
} );

// [ label, settings ] — every existing slug the plan lists, plus the
// GetTemplate.ts second_param split list (both prefixes) and a non-listed
// key with the same stripped slug (no split).
const ROWS = [
	// Sales (conversions_), theme-one..five.
	[ 'conversions_theme-one', conv( 'theme-one' ) ],
	[ 'conversions_theme-two', conv( 'theme-two' ) ],
	[ 'conversions_theme-three', conv( 'theme-three' ) ],
	[ 'conversions_theme-four', conv( 'theme-four' ) ],
	[ 'conversions_theme-five', conv( 'theme-five' ) ],
	// conv-theme-six..sixteen.
	[ 'conversions_conv-theme-six', conv( 'conv-theme-six', MAPS ) ],
	[ 'conversions_conv-theme-seven', conv( 'conv-theme-seven' ) ],
	[ 'conversions_conv-theme-eight', conv( 'conv-theme-eight' ) ],
	[ 'conversions_conv-theme-nine', conv( 'conv-theme-nine' ) ],
	[ 'conversions_conv-theme-ten', conv( 'conv-theme-ten' ) ],
	[ 'conversions_conv-theme-eleven', conv( 'conv-theme-eleven' ) ],
	[ 'conversions_conv-theme-twelve', conv( 'conv-theme-twelve' ) ],
	[ 'conversions_conv-theme-thirteen', conv( 'conv-theme-thirteen', { ...SALES, second_param: 'Bought' } ) ],
	[ 'conversions_conv-theme-fourteen', conv( 'conv-theme-fourteen', SALES_COUNT ) ],
	[ 'conversions_conv-theme-fifteen', conv( 'conv-theme-fifteen' ) ],
	[ 'conversions_conv-theme-sixteen', conv( 'conv-theme-sixteen', SALES_COUNT ) ],
	// WooCommerce Sales prefix (split list + a few more).
	[ 'woocommerce_sales_theme-one', wooSales( 'theme-one' ) ],
	[ 'woocommerce_sales_conv-theme-seven', wooSales( 'conv-theme-seven' ) ],
	[ 'woocommerce_sales_conv-theme-eight', wooSales( 'conv-theme-eight' ) ],
	[ 'woocommerce_sales_conv-theme-nine', wooSales( 'conv-theme-nine' ) ],
	[ 'woocommerce_sales_conv-theme-twelve', wooSales( 'conv-theme-twelve' ) ],
	[ 'woocommerce_sales_conv-theme-fifteen', wooSales( 'conv-theme-fifteen' ) ],
	// Split list: a single-word second_param and an empty one.
	[ 'conversions_conv-theme-seven (single-word second_param)', conv( 'conv-theme-seven', { ...SALES, second_param: 'bought' } ) ],
	[ 'conversions_conv-theme-eight (empty second_param)', conv( 'conv-theme-eight', { ...SALES, second_param: '' } ) ],
	// Same stripped slug, key NOT in the split list.
	[ 'elearning_conv-theme-seven (not in split list)', {
		themes: 'elearning_conv-theme-seven',
		source: 'tutor',
		type: 'elearning',
		'notification-template': { ...SALES, second_param: 'recently enrolled' },
	} ],
	// Freemius branch.
	[ 'freemius conversions_theme-one', freemius( 'theme-one' ) ],
	[ 'freemius conversions_theme-two', freemius( 'theme-two' ) ],
	[ 'freemius conversions_theme-three', freemius( 'theme-three' ) ],
	[ 'freemius conversions_theme-four', freemius( 'theme-four' ) ],
	[ 'freemius conversions_theme-five', freemius( 'theme-five' ) ],
	[ 'freemius conversions_conv-theme-ten', freemius( 'conv-theme-ten' ) ],
	[ 'freemius conversions_conv-theme-eleven', freemius( 'conv-theme-eleven' ) ],
	[ 'freemius conversions_conv-theme-six', freemius( 'conv-theme-six', FREEMIUS_MAPS ) ],
	[ 'freemius conversions_conv-theme-seven', freemius( 'conv-theme-seven' ) ],
	[ 'freemius conversions_conv-theme-eight', freemius( 'conv-theme-eight' ) ],
	[ 'freemius conversions_conv-theme-nine', freemius( 'conv-theme-nine' ) ],
	[ 'freemius conversions_conv-theme-twelve (falls through)', freemius( 'conv-theme-twelve' ) ],
	[ 'freemius custom plan/price', freemius( 'theme-one', {
		...FREEMIUS,
		freemius_fifth_param: 'tag_custom',
		freemius_seventh_param: 'tag_custom',
	} ) ],
	// Comments.
	[ 'comments_theme-one', comments( 'theme-one' ) ],
	[ 'comments_theme-six-free', comments( 'theme-six-free' ) ],
	[ 'comments_theme-seven-free', comments( 'theme-seven-free', COMMENTS_TEXT ) ],
	[ 'comments_theme-eight-free', comments( 'theme-eight-free', COMMENTS_TEXT ) ],
	// Reviews.
	[ 'reviews_total-rated', reviews( 'total-rated', TOTAL_RATED ) ],
	[ 'reviews_reviewed', reviews( 'reviewed', REVIEWED ) ],
	[ 'reviews_review_saying', reviews( 'review_saying', REVIEW_SAYING ) ],
	[ 'reviews_review-comment', reviews( 'review-comment', REVIEW_COMMENT ) ],
	[ 'reviews_review-comment-2', reviews( 'review-comment-2', REVIEW_COMMENT ) ],
	[ 'reviews_review-comment-3', reviews( 'review-comment-3', REVIEW_COMMENT_3 ) ],
	[ 'woocommerce reviews_review-comment', {
		themes: 'reviews_review-comment',
		source: 'woo_reviews',
		type: 'reviews',
		'notification-template': { ...REVIEW_COMMENT, third_param: 'tag_plugin_review' },
	} ],
	// Download stats.
	[ 'download_stats_today-download', stats( 'today-download', DL_TODAY ) ],
	[ 'download_stats_7day-download', stats( '7day-download', DL_7DAY ) ],
	[ 'download_stats_actively_using', stats( 'actively_using', DL_ACTIVE ) ],
	[ 'download_stats_total-download', stats( 'total-download', DL_TOTAL ) ],
	// Maps.
	[ 'conversions_maps_theme', conv( 'maps_theme', MAPS ) ],
	[ 'comments_maps_theme', comments( 'maps_theme', { ...MAPS, map_fourth_param: 'commented on' } ) ],
	[ 'google_reviews_maps_theme', {
		themes: 'google_reviews_maps_theme',
		source: 'google_reviews',
		type: 'reviews',
		'notification-template': REVIEWED,
	} ],
	// Custom tag and escaping.
	[ 'conversions_theme-one (tag_custom + HTML in label)', conv( 'theme-one', {
		...SALES,
		first_param: 'tag_custom',
		custom_first_param: 'Someone',
		second_param: 'just <b>bought</b> & "loved"',
	} ) ],
];

describe( 'GOLDEN GetTemplate rows (existing themes)', () => {
	let errorSpy;
	beforeEach( () => {
		errorSpy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
	} );
	afterEach( () => {
		errorSpy.mockRestore();
	} );

	it.each( ROWS )( '%s', ( label, settings ) => {
		const before = JSON.stringify( settings );
		expect( GetTemplate( settings ) ).toMatchSnapshot();
		expect( errorSpy ).not.toHaveBeenCalled();
		// GetTemplate never mutates the settings it gets.
		expect( JSON.stringify( settings ) ).toBe( before );
	} );

	it( 'default case: unknown slug logs and returns the generic rows', () => {
		const settings = conv( 'unknown-theme' );
		expect( GetTemplate( settings ) ).toEqual( [
			'<span>{{name}}</span> <span>just purchased</span>',
			'<span>{{product_title}}</span>',
			'<span>{{time}}</span>',
		] );
		expect( errorSpy ).toHaveBeenCalledTimes( 1 );
		expect( errorSpy.mock.calls[ 0 ][ 0 ] ).toBe( 'Please select a theme' );
	} );

	it( 'default case: unknown slug with no template gives empty spans', () => {
		expect( GetTemplate( { themes: 'conversions_nope', source: 'woocommerce', type: 'conversions' } ) ).toEqual( [
			'<span></span> <span></span>',
			'<span></span>',
			'<span></span>',
		] );
		expect( errorSpy ).toHaveBeenCalledTimes( 1 );
	} );

	// A few explicit values so the golden file is readable without the snapshot.
	it( 'explicit: conversions_theme-one', () => {
		expect( GetTemplate( conv( 'theme-one' ) ) ).toEqual( [
			'<span>{{name}}</span> <span>just purchased</span>',
			'<span>{{product_title}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'explicit: conversions_conv-theme-twelve', () => {
		expect( GetTemplate( conv( 'conv-theme-twelve' ) ) ).toEqual( [
			'<span>{{name}}</span> <span>{{time}}</span>',
			'<span>just purchased</span> <span>{{product_title}}</span>',
		] );
	} );

	it( 'explicit: conversions_conv-theme-seven splits second_param', () => {
		expect( GetTemplate( conv( 'conv-theme-seven' ) ) ).toEqual( [
			'<span>{{name}}</span> <span><span>just</span><span> purchased</span></span>',
			'<span>{{product_title}}</span> <span>{{time}}</span>',
		] );
	} );

	it( 'explicit: woocommerce_sales_conv-theme-fifteen', () => {
		expect( GetTemplate( wooSales( 'conv-theme-fifteen' ) ) ).toEqual( [
			'<span class="nx-loc">{{city_country}}</span> <span>{{time}}</span>',
			'<span>{{name}}</span> <span>just purchased</span> <span>{{product_title}}</span>',
		] );
	} );
} );
