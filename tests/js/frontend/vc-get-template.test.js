/**
 * Verified Cards (vc-*): GetTemplate slot arrays, the isVcTheme whitelist and
 * vcCompactRows. Part 1: Receipt and Showcase. Part 2: Thread, Scorecard and
 * Counter.
 */
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import { isVcTheme, getVcSlug, VC_SLUGS } from '../../../nxdev/notificationx/frontend/themes/vc/isVcTheme';
import vcCompactRows from '../../../nxdev/notificationx/frontend/themes/vc/vcCompactRows';

const SALES = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just purchased',
	third_param: 'tag_product_title',
	custom_third_param: 'Anonymous Product',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const SHOWCASE = { ...SALES, second_param: 'Just purchased' };
const FREEMIUS = {
	...SALES,
	freemius_fifth_param: 'tag_plan_title',
	custom_freemius_fifth_param: 'Pro Plus',
	freemius_sixth_param: 'Subscription For',
	freemius_seventh_param: 'tag_pricing',
	custom_freemius_seventh_param: '99 USD/Year',
};

const settings = ( themes, source, type, tmpl ) => ( { themes, source, type, 'notification-template': tmpl } );

const RECEIPT_SLOTS = [
	'<span>{{name}}</span>',
	'<span>just purchased</span>',
	'<span>{{product_title}}</span>',
	'<span>{{time}}</span>',
	'<span></span>',
];
const SHOWCASE_SLOTS = [
	'<span>Just purchased</span>',
	'<span>{{product_title}}</span>',
	'<span>{{name}}</span>',
	'<span>{{city_country}}</span>',
	'<span>{{time}}</span>',
];

const ROWS = [
	[ 'conversions_vc-receipt / woocommerce', settings( 'conversions_vc-receipt', 'woocommerce', 'conversions', SALES ), RECEIPT_SLOTS ],
	[ 'conversions_vc-receipt / edd', settings( 'conversions_vc-receipt', 'edd', 'conversions', SALES ), RECEIPT_SLOTS ],
	[ 'woocommerce_sales_vc-receipt', settings( 'woocommerce_sales_vc-receipt', 'woocommerce_sales', 'woocommerce_sales', SALES ), RECEIPT_SLOTS ],
	[
		'conversions_vc-receipt / freemius_conversions (plan · price)',
		settings( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', FREEMIUS ),
		[ ...RECEIPT_SLOTS.slice( 0, 4 ), '<span>{{plan_title}}</span> <span>{{pricing}}</span>' ],
	],
	[
		'conversions_vc-receipt / freemius_conversions with custom plan and price',
		settings( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', {
			...FREEMIUS,
			freemius_fifth_param: 'tag_custom',
			freemius_seventh_param: 'tag_custom',
		} ),
		[ ...RECEIPT_SLOTS.slice( 0, 4 ), '<span>Pro Plus</span> <span>99 USD/Year</span>' ],
	],
	[ 'conversions_vc-showcase / woocommerce', settings( 'conversions_vc-showcase', 'woocommerce', 'conversions', SHOWCASE ), SHOWCASE_SLOTS ],
	[ 'conversions_vc-showcase / edd (no city, country: the slot stays)', settings( 'conversions_vc-showcase', 'edd', 'conversions', SHOWCASE ), SHOWCASE_SLOTS ],
	[ 'conversions_vc-showcase / freemius_conversions', settings( 'conversions_vc-showcase', 'freemius_conversions', 'conversions', { ...FREEMIUS, second_param: 'Just purchased' } ), SHOWCASE_SLOTS ],
	[ 'woocommerce_sales_vc-showcase', settings( 'woocommerce_sales_vc-showcase', 'woocommerce_sales', 'woocommerce_sales', SHOWCASE ), SHOWCASE_SLOTS ],
];

const COMMENTS = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'commented on',
	third_param: 'tag_post_comment',
	custom_third_param: 'Anonymous Post',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const SCORECARD = {
	first_param: 'tag_username',
	custom_first_param: 'Someone',
	second_param: 'on',
	third_param: 'tag_plugin_name',
	fourth_param: 'tag_rating',
	fifth_param: 'tag_plugin_review',
	sixth_param: 'tag_title',
};
// A leftover sixth_param (e.g. from switching theme) must be ignored on these sources.
const WOO_SCORECARD = { ...SCORECARD, third_param: 'tag_product_title', sixth_param: 'tag_title' };
const REVIEWX_SCORECARD = { ...SCORECARD, third_param: 'tag_product_post_title' };
const GOOGLE_SCORECARD = { ...SCORECARD, third_param: 'tag_place_name', fifth_param: 'tag_place_review', sixth_param: 'tag_title' };
const COUNTER = {
	first_param: 'tag_plugin_theme_name',
	second_param: 'has been downloaded',
	third_param: 'tag_last_week',
	fourth_param: 'tag_last_week_text',
};

const THREAD_SLOTS = [
	'<span>{{name}}</span>',
	'<span>commented on</span>',
	'<span>{{post_comment}}</span>',
	'<span>{{time}}</span>',
];
// [0] reviewer, [1] rating, [2] title, [3] text, [4] on, [5] subject, [6] time
const scorecardSlots = ( title, text, subject ) => [
	'<span>{{username}}</span>',
	'<span>{{rating}}</span>',
	title,
	text,
	'<span>on</span>',
	subject,
	'<span>{{time}}</span>',
];
const COUNTER_SLOTS = [
	'<span>{{plugin_theme_name}}</span>',
	'<span>has been downloaded</span>',
	'<span>{{last_week}}</span>',
	'<span>{{last_week_text}}</span>',
];

const PART2_ROWS = [
	[ 'comments_vc-thread / wp_comments', settings( 'comments_vc-thread', 'wp_comments', 'comments', COMMENTS ), THREAD_SLOTS ],
	[
		'comments_vc-thread with Post Title',
		settings( 'comments_vc-thread', 'wp_comments', 'comments', { ...COMMENTS, third_param: 'tag_post_title' } ),
		[ THREAD_SLOTS[ 0 ], THREAD_SLOTS[ 1 ], '<span>{{post_title}}</span>', THREAD_SLOTS[ 3 ] ],
	],
	// The review title only where it is a real headline.
	[ 'reviews_vc-scorecard / wp_reviews (title)', settings( 'reviews_vc-scorecard', 'wp_reviews', 'reviews', SCORECARD ), scorecardSlots( '<span>{{title}}</span>', '<span>{{plugin_review}}</span>', '<span>{{plugin_name}}</span>' ) ],
	[ 'reviews_vc-scorecard / zapier_reviews (title)', settings( 'reviews_vc-scorecard', 'zapier_reviews', 'reviews', SCORECARD ), scorecardSlots( '<span>{{title}}</span>', '<span>{{plugin_review}}</span>', '<span>{{plugin_name}}</span>' ) ],
	[ 'reviews_vc-scorecard / bitintegrations_reviews (title)', settings( 'reviews_vc-scorecard', 'bitintegrations_reviews', 'reviews', SCORECARD ), scorecardSlots( '<span>{{title}}</span>', '<span>{{plugin_review}}</span>', '<span>{{plugin_name}}</span>' ) ],
	// Freemius: title is the plugin name; a stale sixth_param is ignored.
	[ 'reviews_vc-scorecard / freemius_reviews (no title)', settings( 'reviews_vc-scorecard', 'freemius_reviews', 'reviews', SCORECARD ), scorecardSlots( '<span></span>', '<span>{{plugin_review}}</span>', '<span>{{plugin_name}}</span>' ) ],
	[ 'woo_reviews_vc-scorecard (no title)', settings( 'woo_reviews_vc-scorecard', 'woo_reviews', 'reviews', WOO_SCORECARD ), scorecardSlots( '<span></span>', '<span>{{plugin_review}}</span>', '<span>{{product_title}}</span>' ) ],
	[
		'woocommerce_sales_reviews_vc-scorecard (no title)',
		settings( 'woocommerce_sales_reviews_vc-scorecard', 'woocommerce_sales_reviews', 'woocommerce_sales', WOO_SCORECARD ),
		scorecardSlots( '<span></span>', '<span>{{plugin_review}}</span>', '<span>{{product_title}}</span>' ),
	],
	// ReviewX: subject = product (post_title), title = the ReviewX headline.
	[ 'reviewx_vc-scorecard (product + headline)', settings( 'reviewx_vc-scorecard', 'reviewx', 'reviews', REVIEWX_SCORECARD ), scorecardSlots( '<span>{{title}}</span>', '<span>{{plugin_review}}</span>', '<span>{{product_post_title}}</span>' ) ],
	[ 'google_reviews_vc-scorecard (place)', settings( 'google_reviews_vc-scorecard', 'google_reviews', 'reviews', GOOGLE_SCORECARD ), scorecardSlots( '<span></span>', '<span>{{place_review}}</span>', '<span>{{place_name}}</span>' ) ],
	[ 'download_stats_vc-counter / wp_stats', settings( 'download_stats_vc-counter', 'wp_stats', 'download_stats', COUNTER ), COUNTER_SLOTS ],
	[ 'download_stats_vc-counter / freemius_stats', settings( 'download_stats_vc-counter', 'freemius_stats', 'download_stats', COUNTER ), COUNTER_SLOTS ],
];

const SLOT_LENGTH = {
	'vc-receipt': 5,
	'vc-showcase': 5,
	'vc-thread': 4,
	'vc-scorecard': 7,
	'vc-counter': 4,
};

describe( 'GetTemplate vc slots', () => {
	let errorSpy;
	beforeEach( () => {
		errorSpy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
	} );
	afterEach( () => {
		errorSpy.mockRestore();
	} );

	it.each( [ ...ROWS, ...PART2_ROWS ] )( '%s', ( label, input, expected ) => {
		const before = JSON.stringify( input );
		const slots = GetTemplate( input );
		expect( slots ).toEqual( expected );
		// Fixed length, every slot a non-empty string (Theme.tsx drops falsy rows).
		expect( slots ).toHaveLength( SLOT_LENGTH[ getVcSlug( input ) ] );
		slots.forEach( ( slot ) => {
			expect( typeof slot ).toBe( 'string' );
			expect( slot.length ).toBeGreaterThan( 0 );
		} );
		expect( errorSpy ).not.toHaveBeenCalled();
		expect( JSON.stringify( input ) ).toBe( before );
	} );

	it( 'escapes custom text in a slot', () => {
		const slots = GetTemplate(
			settings( 'conversions_vc-receipt', 'woocommerce', 'conversions', { ...SALES, second_param: '<img src=x onerror=alert(1)>' } )
		);
		expect( slots[ 1 ] ).toBe( '<span>&lt;img src=x onerror=alert(1)></span>' );
	} );
} );

describe( 'isVcTheme', () => {
	it( 'whitelists the Part 1 and Part 2 slugs', () => {
		expect( VC_SLUGS ).toEqual( [ 'vc-receipt', 'vc-showcase', 'vc-thread', 'vc-scorecard', 'vc-counter' ] );
	} );

	it.each( [
		[ 'conversions_vc-receipt', 'woocommerce', 'conversions', 'vc-receipt' ],
		[ 'conversions_vc-receipt', 'freemius_conversions', 'conversions', 'vc-receipt' ],
		[ 'conversions_vc-receipt', 'custom_notification_conversions', 'conversions', 'vc-receipt' ],
		[ 'woocommerce_sales_vc-receipt', 'woocommerce_sales', 'woocommerce_sales', 'vc-receipt' ],
		[ 'conversions_vc-showcase', 'edd', 'conversions', 'vc-showcase' ],
		[ 'woocommerce_sales_vc-showcase', 'woocommerce_sales', 'woocommerce_sales', 'vc-showcase' ],
		[ 'comments_vc-thread', 'wp_comments', 'comments', 'vc-thread' ],
		[ 'reviews_vc-scorecard', 'wp_reviews', 'reviews', 'vc-scorecard' ],
		[ 'reviews_vc-scorecard', 'freemius_reviews', 'reviews', 'vc-scorecard' ],
		[ 'woo_reviews_vc-scorecard', 'woo_reviews', 'reviews', 'vc-scorecard' ],
		[ 'woocommerce_sales_reviews_vc-scorecard', 'woocommerce_sales_reviews', 'woocommerce_sales', 'vc-scorecard' ],
		[ 'reviewx_vc-scorecard', 'reviewx', 'reviews', 'vc-scorecard' ],
		[ 'google_reviews_vc-scorecard', 'google_reviews', 'reviews', 'vc-scorecard' ],
		[ 'download_stats_vc-counter', 'wp_stats', 'download_stats', 'vc-counter' ],
		[ 'download_stats_vc-counter', 'freemius_stats', 'download_stats', 'vc-counter' ],
	] )( '%s (%s) is a vc theme', ( themes, source, type, slug ) => {
		expect( isVcTheme( { themes, source, type } ) ).toBe( true );
		expect( getVcSlug( { themes, source, type } ) ).toBe( slug );
	} );

	it.each( [
		[ 'conversions_theme-one', 'woocommerce', 'conversions' ],
		[ 'conversions_conv-theme-twelve', 'woocommerce', 'conversions' ],
		[ 'woocommerce_sales_conv-theme-fifteen', 'woocommerce_sales', 'woocommerce_sales' ],
		[ 'reviews_review-comment', 'wp_reviews', 'reviews' ],
		[ 'comments_theme-six-free', 'wp_comments', 'comments' ],
		[ 'download_stats_7day-download', 'wp_stats', 'download_stats' ],
		// A vc key whose slug is not whitelisted: generic path, no nx-vc.
		[ 'comments_vc-postcard', 'wp_comments', 'comments' ],
		[ 'reviews_vc-ratings', 'wp_reviews', 'reviews' ],
		// Not anchored keys.
		[ 'vc-receipt', 'woocommerce', 'conversions' ],
		[ 'conversions_vc-receipt-2', 'woocommerce', 'conversions' ],
		[ 'conversions_xvc-receipt', 'woocommerce', 'conversions' ],
		[ 'Conversions_vc-receipt', 'woocommerce', 'conversions' ],
	] )( '%s (%s) is not a vc theme', ( themes, source, type ) => {
		expect( isVcTheme( { themes, source, type } ) ).toBe( false );
	} );

	it( 'is false for missing settings or theme', () => {
		expect( isVcTheme( undefined ) ).toBe( false );
		expect( isVcTheme( {} ) ).toBe( false );
		expect( isVcTheme( { themes: 42 } ) ).toBe( false );
	} );
} );

describe( 'vcCompactRows', () => {
	it( 'Receipt: name + action, product + plan, time', () => {
		expect( vcCompactRows( 'vc-receipt', [ 'N', 'A', 'P', 'T', 'F' ] ) ).toEqual( [ 'N A', 'P F', 'T' ] );
	} );

	it( 'Showcase: name + action, product, time', () => {
		expect( vcCompactRows( 'vc-showcase', [ 'A', 'P', 'N', 'L', 'T' ] ) ).toEqual( [ 'N A', 'P', 'T' ] );
	} );

	it( 'Thread: name + action, comment, time', () => {
		expect( vcCompactRows( 'vc-thread', [ 'N', 'A', 'C', 'T' ] ) ).toEqual( [ 'N A', 'C', 'T' ] );
	} );

	it( 'Scorecard: reviewer on subject, rating, time', () => {
		expect( vcCompactRows( 'vc-scorecard', [ 'R', 'rating::4', 'H', 'X', 'on', 'S', 'T' ] ) ).toEqual( [ 'R on S', 'rating::4', 'T' ] );
	} );

	it( 'Counter: name, lead + count, call to action', () => {
		expect( vcCompactRows( 'vc-counter', [ 'N', 'L', 'C', 'A' ] ) ).toEqual( [ 'N', 'L C', 'A' ] );
	} );

	it( 'returns at most three non-empty rows for the real slot arrays', () => {
		[
			[ 'vc-receipt', RECEIPT_SLOTS ],
			[ 'vc-showcase', SHOWCASE_SLOTS ],
			[ 'vc-thread', THREAD_SLOTS ],
			[ 'vc-scorecard', scorecardSlots( '<span></span>', '<span>x</span>', '<span>y</span>' ) ],
			[ 'vc-counter', COUNTER_SLOTS ],
		].forEach( ( [ slug, slots ] ) => {
			const rows = vcCompactRows( slug, slots );
			expect( rows.length ).toBeLessThanOrEqual( 3 );
			rows.forEach( ( row ) => expect( row.length ).toBeGreaterThan( 0 ) );
		} );
	} );

	it( 'returns the slots unchanged for an unknown slug', () => {
		const slots = [ 'a', 'b' ];
		expect( vcCompactRows( 'theme-one', slots ) ).toBe( slots );
	} );
} );
