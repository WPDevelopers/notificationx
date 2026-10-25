/**
 * Verified Cards (vc-*): rendered markup of Receipt and Showcase through the
 * real Theme, the credit, the slim (≤480px) layout, the Pro mobile guard,
 * Advanced Template and the Notification root class.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Theme from '../../../nxdev/notificationx/frontend/themes/Theme';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '5 days' : '5 days ago' ) } ),
	state: { is_pro: false },
};

const AFFILIATE = 'https://notificationx.com/?utm_source=example&ref=42';

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
	freemius_sixth_param: 'Subscription For',
	freemius_seventh_param: 'tag_pricing',
};

const base = ( themes, source, type, tmpl, extra = {} ) => {
	const config = {
		nx_id: 42,
		id: 42,
		themes,
		source,
		type,
		position: 'bottom_left',
		close_button: true,
		close_button_tab: true,
		close_button_mobile: true,
		link_type: 'product_page',
		affiliate_link: AFFILIATE,
		image_shape: 'rounded',
		size: 500,
		'notification-template': tmpl,
		...extra,
	};
	config.template = config.template_adv ? config.template : GetTemplate( config );
	return config;
};

const receipt = ( extra ) => base( 'conversions_vc-receipt', 'woocommerce', 'conversions', SALES, extra );
const showcase = ( extra ) => base( 'woocommerce_sales_vc-showcase', 'woocommerce_sales', 'woocommerce_sales', SHOWCASE, extra );

const saleEntry = ( extra = {} ) => ( {
	name: 'Jane D.',
	title: 'Blue T-shirt',
	city: 'Dhaka',
	country: 'Bangladesh',
	link: 'https://example.com/product/blue-t-shirt',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/product.png', alt: 'Blue T-shirt' },
	...extra,
} );

let container;
let originalMatchMedia;
const stubMatchMedia = ( slim ) => {
	window.matchMedia = jest.fn( ( query ) => ( {
		matches: query === '(max-width: 480px)' ? slim : false,
		media: query,
		onchange: null,
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		addListener: jest.fn(),
		removeListener: jest.fn(),
		dispatchEvent: jest.fn(),
	} ) );
};

beforeEach( () => {
	originalMatchMedia = window.matchMedia;
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	if ( originalMatchMedia ) {
		window.matchMedia = originalMatchMedia;
	} else {
		delete window.matchMedia;
	}
} );

const render = ( element ) => {
	act( () => {
		ReactDOM.render( <NotificationProvider value={ frontendContext }>{ element }</NotificationProvider>, container );
	} );
	return container;
};

const renderTheme = ( config, data = saleEntry(), extra = {} ) =>
	render( <Theme config={ config } data={ data } id="nx-vc" dispatch={ jest.fn() } { ...extra } /> );

const text = ( selector ) => container.querySelector( selector )?.textContent;

describe( 'Verified Card: desktop / tablet', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it( 'Receipt renders the vc card, not the generic content', () => {
		renderTheme( receipt() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-receipt' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( true );
		expect( card.classList.contains( 'nx-vc-slim' ) ).toBe( false );
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-inner' ) ).toBeNull();

		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-name' ) ).toBe( 'Jane D.' );
		expect( text( '.nx-vc-panel .nx-vc-label' ) ).toBe( 'just purchased' );
		expect( text( '.nx-vc-panel .nx-vc-body .nx-vc-em-a' ) ).toBe( 'Blue T-shirt' );
		// The Freemius plan row is hidden for every other source.
		expect( container.querySelector( '.nx-vc-plan' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel .notificationx-image img' ).getAttribute( 'src' ) ).toBe(
			'https://example.com/product.png'
		);
		expect( container.querySelector( '.nx-vc-card > .notificationx-close' ) ).not.toBeNull();
		// Only one layout is mounted.
		expect( container.querySelector( '.nx-vc-l1' ) ).toBeNull();
	} );

	it( 'Receipt on freemius_conversions shows plan and price', () => {
		renderTheme(
			base( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', FREEMIUS ),
			saleEntry( { plan_title: 'Pro Plus', pricing: '99 USD/Year' } )
		);
		const plan = container.querySelector( '.nx-vc-panel .nx-vc-plan' );
		expect( plan ).not.toBeNull();
		expect( [ ...plan.querySelectorAll( ':scope > span' ) ].map( ( s ) => s.textContent ) ).toEqual( [ 'Pro Plus', '99 USD/Year' ] );
	} );

	it( 'hides empty slots', () => {
		renderTheme( receipt(), saleEntry( { name: '' } ) );
		expect( container.querySelector( '.nx-vc-name' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).not.toBeNull();

		// No fourth param: no time and no reserved space for it.

		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		renderTheme( receipt( { 'notification-template': { ...SALES, fourth_param: '' } } ) );
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-card' ).classList.contains( 'has-time' ) ).toBe( false );
	} );

	it( 'renders no image when the entry has none', () => {
		renderTheme( receipt(), saleEntry( { image_data: false } ) );
		expect( container.querySelector( '.notificationx-image' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
	} );

	it( 'Showcase renders chip, title, buyer, location and image', () => {
		renderTheme( showcase() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-showcase' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-chip' ) ).toBe( 'Just purchased' );
		expect( text( '.nx-vc-sc-title' ) ).toBe( 'Blue T-shirt' );
		expect( text( '.nx-vc-sc-buyer b' ) ).toBe( 'Jane D.' );
		expect( container.querySelector( '.nx-vc-sc-buyer .nx-vc-sep' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-loc .nx-vc-pin' ) ).not.toBeNull();
		expect( text( '.nx-vc-loc' ).trim() ).toBe( 'Dhaka, Bangladesh' );
		// The image sits after the text column (right side).
		const main = container.querySelector( '.nx-vc-sc-main' );
		expect( main.lastElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
	} );

	it( 'Showcase without city and country shows no pin or separator', () => {
		renderTheme( showcase(), saleEntry( { city: '', country: '' } ) );
		expect( text( '.nx-vc-sc-buyer b' ) ).toBe( 'Jane D.' );
		expect( container.querySelector( '.nx-vc-loc' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-sep' ) ).toBeNull();
		expect( text( '.nx-vc-sc-buyer' ) ).toBe( 'Jane D.' );
	} );

	it( 'Showcase with only a location shows no leading separator', () => {
		renderTheme( showcase(), saleEntry( { name: '' } ) );
		expect( container.querySelector( '.nx-vc-sc-buyer b' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-sep' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc' ) ).not.toBeNull();
	} );

	it( 'escapes entry values', () => {
		renderTheme( receipt(), saleEntry( { name: '<img src=x onerror=alert(1)>' } ) );
		expect( container.querySelector( '.nx-vc-name img' ) ).toBeNull();
		expect( text( '.nx-vc-name' ) ).toBe( '<img src=x onerror=alert(1)>' );
	} );

	it( 'escapes the Showcase city and country', () => {
		renderTheme(
			showcase(),
			saleEntry( { city: '<img src=x onerror=alert(1)>', country: '<b>X</b>' } )
		);
		expect( container.querySelector( '.nx-vc-loc img' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc b' ) ).toBeNull();
		expect( text( '.nx-vc-loc' ).trim() ).toBe( '<img src=x onerror=alert(1)>, <b>X</b>' );
	} );

	it( 'skips the generic content filters (documented)', () => {
		const before = jest.fn( ( v ) => v );
		window.wp = { hooks: require( '@wordpress/hooks' ).createHooks() };
		try {
			window.wp.hooks.addFilter( 'nx_theme_before_content', 'test', before );
			renderTheme( receipt() );
			expect( before ).not.toHaveBeenCalled();
		} finally {
			delete window.wp;
		}
	} );
} );

describe( 'Verified Card: credit', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it.each( [
		[ 'Receipt', receipt ],
		[ 'Showcase', showcase ],
	] )( '%s: ✓ Verified by [bell] NotificationX', ( label, make ) => {
		renderTheme( make() );
		const credit = container.querySelector( '.nx-vc-card .nx-vc-credit' );
		expect( credit ).not.toBeNull();
		expect( credit.tagName ).toBe( 'SMALL' );
		expect( credit.classList.contains( 'nx-branding' ) ).toBe( true );
		expect( credit.classList.contains( 'nx-branding-verified' ) ).toBe( true );
		expect( credit.firstElementChild.classList.contains( 'nx-verified-check' ) ).toBe( true );
		expect( credit.querySelector( '.nx-byline' ).textContent ).toBe( 'Verified by' );

		const link = credit.querySelector( 'a.nx-powered-by' );
		expect( link.getAttribute( 'href' ) ).toBe( AFFILIATE );
		expect( link.getAttribute( 'rel' ) ).toBe( 'nofollow' );
		expect( link.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( link.getAttribute( 'aria-label' ) ).toBe( 'NotificationX' );
		const svgs = link.querySelectorAll( 'svg' );
		expect( svgs ).toHaveLength( 2 );
		expect( svgs[ 0 ].getAttribute( 'class' ) ).toBe( 'nx-vc-bell' );
		expect( svgs[ 0 ].getAttribute( 'viewBox' ) ).toBe( '5.17 7.2 33.23 33.6' );
		expect( svgs[ 1 ].getAttribute( 'class' ) ).toBe( 'nx-vc-word' );
		expect( svgs[ 1 ].getAttribute( 'viewBox' ) ).toBe( '49.99 16.49 124.85 15.05' );
		// Not the old byline.
		expect( container.querySelector( '.nx-brand-logo' ) ).toBeNull();
	} );

	it( 'disable_powered_by removes the credit', () => {
		renderTheme( receipt( { disable_powered_by: true } ) );
		expect( container.querySelector( '.nx-vc-card' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
		expect( container.querySelector( '.nx-branding' ) ).toBeNull();
	} );
} );

describe( 'Verified Card: slim (≤480px)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it( 'Receipt line 1: <b>name</b> action product, time at the right, credit A', () => {
		renderTheme( receipt() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-receipt.nx-vc-slim' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();

		const inner = container.querySelector( '.nx-vc-l1 > .nx-vc-msg > .nx-vc-msg-in' );
		expect( inner ).not.toBeNull();
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Jane D.' );
		expect( inner.querySelector( '.nx-vc-em-a' ).textContent ).toBe( 'Blue T-shirt' );
		expect( inner.textContent ).toBe( 'Jane D. just purchased Blue T-shirt' );
		expect( text( '.nx-vc-l1 > .nx-vc-slim-time' ) ).toBe( '5 days ago' );

		const credit = container.querySelector( '.nx-vc-credit.is-slim' );
		expect( credit ).not.toBeNull();
		expect( credit.querySelector( '.nx-byline' ).textContent ).toBe( 'Verified by' );
		expect( credit.querySelector( 'a.nx-powered-by' ).getAttribute( 'href' ) ).toBe( AFFILIATE );
		expect( card.querySelector( ':scope > .notificationx-image' ) ).not.toBeNull();
		expect( card.querySelector( ':scope > .notificationx-close' ) ).not.toBeNull();
	} );

	it( 'Receipt slim drops the Freemius plan', () => {
		renderTheme(
			base( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', FREEMIUS ),
			saleEntry( { plan_title: 'Pro Plus', pricing: '99 USD/Year' } )
		);
		expect( container.querySelector( '.nx-vc-msg-in' ).textContent ).toBe( 'Jane D. just purchased Blue T-shirt' );
		expect( container.querySelector( '.nx-vc-plan' ) ).toBeNull();
	} );

	it( 'Showcase line 1: <b>product</b> · action, time at the right', () => {
		renderTheme( showcase() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-showcase.nx-vc-slim' ) ).not.toBeNull();
		const inner = container.querySelector( '.nx-vc-msg-in' );
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Blue T-shirt' );
		expect( inner.querySelector( '.nx-vc-dim' ).textContent ).toBe( '·' );
		expect( inner.querySelector( '.nx-vc-em-a' ).textContent ).toBe( 'Just purchased' );
		expect( inner.textContent ).toBe( 'Blue T-shirt · Just purchased' );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		// The buyer, location and chip are dropped on phones.
		expect( container.querySelector( '.nx-vc-chip' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc' ) ).toBeNull();
	} );

	it( 'flags no image and no credit for the grid', () => {
		renderTheme( receipt( { disable_powered_by: true } ), saleEntry( { image_data: false } ) );
		const card = container.querySelector( '.nx-vc-slim' );
		expect( card.classList.contains( 'is-no-img' ) ).toBe( true );
		expect( card.classList.contains( 'is-no-credit' ) ).toBe( true );
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
	} );

	it( 'falls back to the desktop card without matchMedia', () => {
		delete window.matchMedia;
		renderTheme( receipt() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-slim' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).not.toBeNull();
	} );
} );

describe( 'Verified Card: guards', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it.each( [
		[ 'Receipt', receipt, [ 'Jane D. just purchased', 'Blue T-shirt ', '5 days ago' ] ],
		[ 'Showcase', showcase, [ 'Jane D. Just purchased', 'Blue T-shirt', '5 days ago' ] ],
	] )( 'Pro mobile card (is_mobile) renders %s through the generic path with compact rows', ( label, make, rows ) => {
		renderTheme( make(), saleEntry(), { is_mobile: true } );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-inner' ) ).not.toBeNull();
		const content = container.querySelector( '.notificationx-content' );
		expect( content ).not.toBeNull();
		const ps = [ ...content.querySelectorAll( 'p' ) ];
		expect( ps ).toHaveLength( 3 );
		expect( ps.map( ( p ) => p.firstElementChild.textContent ) ).toEqual( rows );
		// The old "by NotificationX" credit in the last row.
		expect( ps[ 2 ].querySelector( '.nx-branding .nx-byline' ).textContent ).toBe( 'by' );
		expect( ps[ 2 ].querySelector( '.nx-branding-verified' ) ).toBeNull();
	} );

	it( 'Advanced Template on a vc key renders the generic card', () => {
		renderTheme( receipt( { template_adv: true, template: [ '{{name}} bought {{title}}', '{{time}}' ] } ) );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		const content = container.querySelector( '.notificationx-content' );
		expect( content ).not.toBeNull();
		expect( content.querySelector( '.nx-first-row' ).textContent ).toBe( 'Jane D. bought Blue T-shirt' );
	} );

	it( 'a Part 2 vc key (not whitelisted) takes the generic path', () => {
		const spy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		renderTheme(
			base( 'comments_vc-thread', 'wp_comments', 'comments', {
				first_param: 'tag_name',
				second_param: 'commented on',
				third_param: 'tag_post_title',
				fourth_param: 'tag_time',
			} ),
			{ name: 'Alex', post_title: 'Hello', updated_at: '2026-10-01 00:00:00', image_data: false }
		);
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).not.toBeNull();
		spy.mockRestore();
	} );
} );

describe( 'Notification root class', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		stubMatchMedia( false );
	} );
	afterEach( () => {
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		jest.clearAllTimers();
		jest.useRealTimers();
	} );

	const renderRoot = ( config ) => {
		render( <Notification config={ config } data={ saleEntry() } id="nx-vc" dispatch={ jest.fn() } /> );
		return container.querySelector( '#notificationx-42' );
	};

	it.each( [
		[ 'conversions_vc-receipt', () => receipt() ],
		[ 'woocommerce_sales_vc-showcase', () => showcase() ],
	] )( '%s gets nx-vc and renders the vc card inside', ( label, make ) => {
		const root = renderRoot( make() );
		expect( root.classList.contains( 'nx-vc' ) ).toBe( true );
		expect( root.querySelector( '.nx-vc-card' ) ).not.toBeNull();
		// The Analytics overlay is still rendered after the card.
		expect( root.lastElementChild.classList.contains( 'notificationx-link-wrapper' ) ).toBe( true );
		expect( root.lastElementChild.querySelector( 'a.notificationx-link' ) ).not.toBeNull();
	} );

	it( 'a vc key with Advanced Template gets no nx-vc', () => {
		const root = renderRoot( receipt( { template_adv: true, template: [ '{{name}}' ] } ) );
		expect( root.classList.contains( 'nx-vc' ) ).toBe( false );
		expect( root.classList.contains( 'themes-vc-receipt' ) ).toBe( true );
	} );

	it( 'existing themes get no nx-vc (className unchanged)', () => {
		const root = renderRoot( base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES, { image_shape: 'circle' } ) );
		expect( root.className ).toBe(
			'notification-item nx-notification source-woocommerce position-bottom_left type-conversions themes-theme-one themes-conversions_theme-one notificationx-42 has-close-btn'
		);
	} );
} );
