/**
 * Verified Cards (vc-*): GetTemplate slot arrays, the isVcTheme whitelist and
 * vcCompactRows. Part 1: Receipt and Showcase.
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

describe( 'GetTemplate vc slots', () => {
	let errorSpy;
	beforeEach( () => {
		errorSpy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
	} );
	afterEach( () => {
		errorSpy.mockRestore();
	} );

	it.each( ROWS )( '%s', ( label, input, expected ) => {
		const before = JSON.stringify( input );
		const slots = GetTemplate( input );
		expect( slots ).toEqual( expected );
		// Fixed length, every slot a non-empty string (Theme.tsx drops falsy rows).
		expect( slots ).toHaveLength( 5 );
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
	it( 'whitelists only the Part 1 slugs', () => {
		expect( VC_SLUGS ).toEqual( [ 'vc-receipt', 'vc-showcase' ] );
	} );

	it.each( [
		[ 'conversions_vc-receipt', 'woocommerce', 'conversions', 'vc-receipt' ],
		[ 'conversions_vc-receipt', 'freemius_conversions', 'conversions', 'vc-receipt' ],
		[ 'conversions_vc-receipt', 'custom_notification_conversions', 'conversions', 'vc-receipt' ],
		[ 'woocommerce_sales_vc-receipt', 'woocommerce_sales', 'woocommerce_sales', 'vc-receipt' ],
		[ 'conversions_vc-showcase', 'edd', 'conversions', 'vc-showcase' ],
		[ 'woocommerce_sales_vc-showcase', 'woocommerce_sales', 'woocommerce_sales', 'vc-showcase' ],
	] )( '%s (%s) is a vc theme', ( themes, source, type, slug ) => {
		expect( isVcTheme( { themes, source, type } ) ).toBe( true );
		expect( getVcSlug( { themes, source, type } ) ).toBe( slug );
	} );

	it.each( [
		[ 'conversions_theme-one', 'woocommerce', 'conversions' ],
		[ 'conversions_conv-theme-twelve', 'woocommerce', 'conversions' ],
		[ 'woocommerce_sales_conv-theme-fifteen', 'woocommerce_sales', 'woocommerce_sales' ],
		[ 'reviews_review-comment', 'wp_reviews', 'reviews' ],
		// Part 2 keys are not whitelisted yet: generic path, no nx-vc.
		[ 'comments_vc-thread', 'wp_comments', 'comments' ],
		[ 'reviews_vc-scorecard', 'wp_reviews', 'reviews' ],
		[ 'download_stats_vc-counter', 'wp_stats', 'download_stats' ],
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

	it( 'returns at most three non-empty rows for the real slot arrays', () => {
		[
			[ 'vc-receipt', RECEIPT_SLOTS ],
			[ 'vc-showcase', SHOWCASE_SLOTS ],
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
