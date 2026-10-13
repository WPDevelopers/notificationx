import GetTemplate, { getLiveRotator, getLiveTimeParam, withoutLiveTimeFact } from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import { installHooks } from '../helpers';

const template = {
	first_param: 'tag_title',
	second_param: 'Second',
	third_param: 'tag_offer',
	fourth_param: 'tag_time',
};

const announcement = { themes: 'announcements_theme-1', source: 'announcements', 'notification-template': template };
const cartPeek = { themes: 'woocommerce_cart_peek_conv-theme-fourteen', source: 'woocommerce_cart_peek', 'notification-template': template };
const sales = { themes: 'woocommerce_sales_theme-one', source: 'woocommerce_sales', 'notification-template': template };

describe( 'GetTemplate / nx_frontend_template', () => {
	it( 'keeps the built-in announcements layout when no filter claims it', () => {
		expect( GetTemplate( announcement ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'keeps the built-in Cart Peek layout when no filter claims it', () => {
		expect( GetTemplate( cartPeek ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
		] );
	} );

	it( 'uses the rows an add-on returns, and passes settings and the prepared params', () => {
		const filter = jest.fn( ( value, settings, params ) =>
			settings.source === 'announcements' ? [ `claimed ${ params.first_param }` ] : value
		);
		installHooks().addFilter( 'nx_frontend_template', 'test', filter );

		expect( GetTemplate( announcement ) ).toEqual( [ 'claimed <span>{{title}}</span>' ] );
		expect( filter.mock.calls[ 0 ][ 0 ] ).toBeUndefined();
		expect( filter.mock.calls[ 0 ][ 1 ] ).toBe( announcement );
	} );

	it( 'leaves notifications the add-on passes through untouched', () => {
		installHooks().addFilter( 'nx_frontend_template', 'test', ( value, settings ) =>
			settings.source === 'announcements' ? [ 'claimed' ] : value
		);
		expect( GetTemplate( sales ) ).toEqual( [
			'<span>{{title}}</span> <span>Second</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'ignores a non-array return and renders the built-in layout', () => {
		installHooks().addFilter( 'nx_frontend_template', 'test', () => 'not an array' );
		expect( GetTemplate( cartPeek ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
		] );
	} );
} );

describe( 'GetTemplate / Live Effects (84711)', () => {
	const live = '<span class="nx-live-time">';
	const peekThemes = [ 'theme-one', 'theme-two', 'theme-three', 'conv-theme-twelve', 'conv-theme-fourteen', 'conv-theme-sixteen' ];
	const peek = ( theme, extra = {} ) => ( { themes: `woocommerce_cart_peek_${ theme }`, source: 'woocommerce_cart_peek', type: 'woocommerce_cart_peek', 'notification-template': template, ...extra } );
	const pa = ( theme, first, extra = {} ) => ( {
		themes: `page_analytics_${ theme }`,
		source: 'google',
		type: 'page_analytics',
		'notification-template': { first_param: first, second_param: 'people', third_param: 'tag_title', ga_fourth_param: 'right now', ga_fifth_param: '' },
		...extra,
	} );
	const rotator =
		'<span class="nx-live-rotator">' +
		'<span class="nx-live-fact"><span>{{offer}}</span></span>' +
		'<span class="nx-live-fact" aria-hidden="true"><span>Added to a cart {{time}}</span></span>' +
		'</span>';

	it( 'leaves every template unchanged with the toggle off', () => {
		[ ...peekThemes.map( ( t ) => peek( t ) ), pa( 'pa-theme-one', 'tag_realtime_siteview' ), sales ].forEach( ( s ) => {
			const rows = GetTemplate( { ...s, live_effects: false } );
			expect( rows ).toEqual( GetTemplate( s ) );
			expect( rows.join( '' ) ).not.toMatch( /nx-live-/ );
		} );
	} );

	it( 'marks "right now" for realtime Page Analytics themes one to three', () => {
		[ 'pa-theme-one', 'pa-theme-two', 'pa-theme-three' ].forEach( ( theme ) => {
			const rows = GetTemplate( pa( theme, 'tag_realtime_siteview', { live_effects: true } ) );
			expect( rows.join( '' ) ).toContain( `${ live }right now</span>` );
			expect( rows.join( '' ).split( 'nx-live-time' ) ).toHaveLength( 2 );
		} );
	} );

	it( 'shows no dot when the live time text is empty', () => {
		[ '', '   ' ].forEach( ( text ) => {
			const s = pa( 'pa-theme-three', 'tag_realtime_siteview', { live_effects: true } );
			s[ 'notification-template' ].ga_fourth_param = text;
			expect( GetTemplate( s ).join( '' ) ).not.toContain( 'nx-live-time' );
		} );
	} );

	it( 'shows no dot anywhere else', () => {
		expect( GetTemplate( pa( 'pa-theme-one', 'tag_siteview', { live_effects: true } ) ).join( '' ) ).not.toContain( 'nx-live-time' );
		expect( GetTemplate( pa( 'pa-theme-four', 'tag_realtime_siteview', { live_effects: true } ) ).join( '' ) ).not.toContain( 'nx-live-time' );
		peekThemes.forEach( ( t ) => {
			expect( GetTemplate( peek( t, { live_effects: true } ) ).join( '' ) ).not.toContain( 'nx-live-time' );
		} );
		expect( GetTemplate( { ...sales, live_effects: true } ).join( '' ) ).not.toContain( 'nx-live-time' );
		expect( getLiveTimeParam( sales, 'theme-one' ) ).toBeNull();
	} );

	it( 'rotates the product title and the add-to-cart time in Cart Peek row 2, every theme', () => {
		peekThemes.forEach( ( theme ) => {
			expect( GetTemplate( peek( theme, { live_effects: true } ) ) ).toEqual( [ '<span>{{title}}</span>', rotator ] );
		} );
	} );

	it( 'never shows a Cart Peek fact twice', () => {
		peekThemes.forEach( ( theme ) => {
			const card = GetTemplate( peek( theme, { live_effects: true } ) ).join( '' );
			[ '{{title}}', '{{offer}}', '{{time}}' ].forEach( ( fact ) => expect( card.split( fact ) ).toHaveLength( 2 ) );
		} );
	} );

	it( 'cuts the rotating line back to the product title when there is no time', () => {
		const rows = GetTemplate( peek( 'theme-one', { live_effects: true } ) );
		expect( withoutLiveTimeFact( rows[ 1 ] ) ).toBe( '<span>{{offer}}</span>' );
		expect( withoutLiveTimeFact( rows[ 0 ] ) ).toBe( rows[ 0 ] );
		const off = GetTemplate( peek( 'theme-one' ) );
		off.forEach( ( row ) => expect( withoutLiveTimeFact( row ) ).toBe( row ) );
		expect( withoutLiveTimeFact( undefined ) ).toBeUndefined();
	} );

	it( 'hides every fact after the first from screen readers', () => {
		const html = getLiveRotator( [ 'a', 'b', 'c' ] );
		expect( html.match( /class="nx-live-fact"/g ) ).toHaveLength( 3 );
		expect( html.match( /aria-hidden="true"/g ) ).toHaveLength( 2 );
		expect( html ).toContain( '<span class="nx-live-fact">a</span>' );
	} );
} );
