/**
 * Verified Cards (vc-*) add-on hooks (free x.x.x):
 *  - nx_frontend_vc_slugs        (themes/vc/isVcTheme.ts)
 *  - nx_frontend_vc_card         (themes/vc/VerifiedCard.tsx, with VC_CARD_PARTS and SlimShell)
 *  - nx_frontend_vc_compact_rows (themes/vc/vcCompactRows.ts)
 *
 * The add-on here is a made-up "Sample" theme (`conversions_vc-sample`). None
 * of the hooks may run for a built-in slug, so the free themes render the same
 * with or without an add-on (the golden and vc-card tests cover their markup).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Theme from '../../../nxdev/notificationx/frontend/themes/Theme';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import {
	VC_SLUGS,
	getVcSlugs,
	getVcSlug,
	isVcTheme,
	vcCompactRows,
} from '../../../nxdev/notificationx/frontend/themes/vc';
import { SlimShell, VC_CARD_PARTS } from '../../../nxdev/notificationx/frontend/themes/vc/VerifiedCard';
import { installHooks } from '../helpers';

const NS = 'test/vc-addon';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '5 days' : '5 days ago' ) } ),
	state: { is_pro: false },
};

const SALES = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just purchased',
	third_param: 'tag_product_title',
	custom_third_param: 'Anonymous Product',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};

const base = ( themes, extra = {} ) => {
	const config = {
		nx_id: 42,
		id: 42,
		themes,
		source: 'woocommerce',
		type: 'conversions',
		position: 'bottom_left',
		close_button: true,
		close_button_tab: true,
		close_button_mobile: true,
		link_type: 'product_page',
		affiliate_link: 'https://notificationx.com/?ref=42',
		image_shape: 'rounded',
		'notification-template': SALES,
		...extra,
	};
	config.template = GetTemplate( config );
	return config;
};

const sample = ( extra ) => base( 'conversions_vc-sample', extra );
const receipt = ( extra ) => base( 'conversions_vc-receipt', extra );

const saleEntry = ( extra = {} ) => ( {
	name: 'Jane D.',
	title: 'Blue T-shirt',
	link: 'https://example.com/product/blue-t-shirt',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/product.png', alt: 'Blue T-shirt' },
	...extra,
} );

// The add-on's slots for its own key: [0] name, [1] product, [2] time.
const sampleSlots = ( settings, params ) =>
	settings?.themes === 'conversions_vc-sample'
		? [ `${ params.first_param }`, `${ params.third_param }`, `${ params.fourth_param }` ]
		: undefined;

const addSlug = ( hooks ) =>
	hooks.addFilter( 'nx_frontend_vc_slugs', NS, ( slugs ) => [ ...slugs, 'vc-sample' ] );

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

describe( 'nx_frontend_vc_slugs', () => {
	it( 'returns the built-in slugs without a registry', () => {
		expect( getVcSlugs() ).toEqual( VC_SLUGS );
		expect( isVcTheme( { themes: 'conversions_vc-sample', source: 'woocommerce', type: 'conversions' } ) ).toBe( false );
	} );

	it( 'adds an add-on slug', () => {
		addSlug( installHooks() );
		expect( getVcSlugs() ).toEqual( [ ...VC_SLUGS, 'vc-sample' ] );
		expect( getVcSlug( { themes: 'conversions_vc-sample', source: 'woocommerce', type: 'conversions' } ) ).toBe( 'vc-sample' );
		expect( isVcTheme( { themes: 'conversions_vc-sample', source: 'woocommerce', type: 'conversions' } ) ).toBe( true );
	} );

	it( 'gets a copy of the built-in list, so an add-on cannot change it', () => {
		installHooks().addFilter( 'nx_frontend_vc_slugs', NS, ( slugs ) => {
			slugs.length = 0;
			return slugs;
		} );
		expect( getVcSlugs() ).toEqual( VC_SLUGS );
		expect( VC_SLUGS ).toHaveLength( 10 );
	} );

	it( 'keeps every built-in slug when a filter returns a shorter list', () => {
		installHooks().addFilter( 'nx_frontend_vc_slugs', NS, () => [ 'vc-sample' ] );
		expect( getVcSlugs() ).toEqual( [ ...VC_SLUGS, 'vc-sample' ] );
		expect( isVcTheme( { themes: 'conversions_vc-receipt', source: 'woocommerce', type: 'conversions' } ) ).toBe( true );
	} );

	it( 'drops entries that are not vc-<word> strings, and duplicates', () => {
		installHooks().addFilter( 'nx_frontend_vc_slugs', NS, ( slugs ) => [
			...slugs,
			'sample',
			'vc-Sample',
			'vc-sample-two',
			'vc-',
			5,
			null,
			'vc-receipt',
			'vc-sample',
			'vc-sample',
		] );
		expect( getVcSlugs() ).toEqual( [ ...VC_SLUGS, 'vc-sample' ] );
	} );

	it( 'ignores a filter that returns no array', () => {
		installHooks().addFilter( 'nx_frontend_vc_slugs', NS, () => 'vc-sample' );
		expect( getVcSlugs() ).toEqual( VC_SLUGS );
	} );

	it( 'is not called for a built-in key or a key that is not <prefix>_vc-<word>', () => {
		const filter = jest.fn( ( slugs ) => slugs );
		installHooks().addFilter( 'nx_frontend_vc_slugs', NS, filter );
		expect( isVcTheme( { themes: 'conversions_vc-receipt', source: 'woocommerce', type: 'conversions' } ) ).toBe( true );
		expect( isVcTheme( { themes: 'conversions_theme-one', source: 'woocommerce', type: 'conversions' } ) ).toBe( false );
		expect( isVcTheme( { themes: 'conversions_vc-sample-x', source: 'woocommerce', type: 'conversions' } ) ).toBe( false );
		expect( filter ).not.toHaveBeenCalled();
	} );

	it( 'adds nx-vc to the Notification root of an add-on key', () => {
		jest.useFakeTimers();
		stubMatchMedia( false );
		const hooks = installHooks();
		addSlug( hooks );
		hooks.addFilter( 'nx_frontend_template', NS, ( value, settings, params ) => sampleSlots( settings, params ) ?? value );
		hooks.addFilter( 'nx_frontend_vc_card', NS, ( card, args ) =>
			args.slug === 'vc-sample' ? <div className="nx-vc-card nx-vc-sample">Sample</div> : card
		);
		render( <Notification config={ sample() } data={ saleEntry() } id="nx-vc" dispatch={ jest.fn() } /> );
		const root = container.querySelector( '#notificationx-42' );
		expect( root.classList.contains( 'nx-vc' ) ).toBe( true );
		expect( root.classList.contains( 'themes-vc-sample' ) ).toBe( true );
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		jest.clearAllTimers();
		jest.useRealTimers();
	} );
} );

describe( 'nx_frontend_vc_card', () => {
	let hooks;
	beforeEach( () => {
		hooks = installHooks();
		addSlug( hooks );
		hooks.addFilter( 'nx_frontend_template', NS, ( value, settings, params ) => sampleSlots( settings, params ) ?? value );
	} );

	it( 'renders the add-on card with the substituted slots and the parts', () => {
		stubMatchMedia( false );
		const filter = jest.fn( ( card, args ) =>
			args.slug === 'vc-sample' ? (
				<div className="nx-vc-card nx-vc-sample">
					<args.parts.Html className="nx-vc-name" html={ args.slots[ 0 ] } />
					<args.parts.Html className="nx-vc-body" html={ args.slots[ 1 ] } />
					<args.parts.VcCredit config={ args.config } />
				</div>
			) : card
		);
		hooks.addFilter( 'nx_frontend_vc_card', NS, filter );
		renderTheme( sample() );

		expect( filter ).toHaveBeenCalledTimes( 1 );
		const args = filter.mock.calls[ 0 ][ 1 ];
		expect( args.slug ).toBe( 'vc-sample' );
		expect( args.word ).toBe( 'sample' );
		expect( args.slim ).toBe( false );
		expect( args.slots ).toHaveLength( 3 );
		expect( args.config.themes ).toBe( 'conversions_vc-sample' );
		expect( args.data.name ).toBe( 'Jane D.' );
		expect( args.parts ).toBe( VC_CARD_PARTS );
		expect( Object.isFrozen( args.parts ) ).toBe( true );
		expect( Object.keys( args.parts ).sort() ).toEqual(
			[ 'Close', 'Html', 'SlimShell', 'VcCredit', 'VcImage', 'VcStars', 'formatVcRating', 'hasText', 'joinParts', 'parseVcRating' ].sort()
		);

		expect( container.querySelector( '.nx-vc-card.nx-vc-sample' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-name' ) ).toBe( 'Jane D.' );
		expect( text( '.nx-vc-body' ) ).toBe( 'Blue T-shirt' );
		expect( container.querySelector( '.nx-vc-credit' ) ).not.toBeNull();
	} );

	it( 'passes slim at 480px and below, and SlimShell renders the built-in slim frame', () => {
		stubMatchMedia( true );
		const filter = jest.fn( ( card, args ) => {
			if ( args.slug !== 'vc-sample' ) return card;
			const { SlimShell: Shell, Html, joinParts } = args.parts;
			return (
				<Shell
					props={ args }
					word={ args.word }
					line={ joinParts( [ <Html as="b" html={ args.slots[ 0 ] } />, <Html html={ args.slots[ 1 ] } /> ] ) }
					time={ args.slots[ 2 ] }
				/>
			);
		} );
		hooks.addFilter( 'nx_frontend_vc_card', NS, filter );
		renderTheme( sample() );

		expect( filter.mock.calls[ 0 ][ 1 ].slim ).toBe( true );
		const card = container.querySelector( '.nx-vc-card.nx-vc-sample.nx-vc-slim' );
		expect( card ).not.toBeNull();
		expect( card.querySelector( '.nx-vc-l1 > .nx-vc-msg > .nx-vc-msg-in' ).textContent ).toBe( 'Jane D. Blue T-shirt' );
		expect( text( '.nx-vc-l1 > .nx-vc-slim-time' ) ).toBe( '5 days ago' );
		expect( card.querySelector( '.nx-vc-credit.is-slim' ) ).not.toBeNull();
		expect( card.querySelector( '.notificationx-image img' ) ).not.toBeNull();
	} );

	it( 'falls back to the Receipt layout when no card is returned', () => {
		stubMatchMedia( false );
		hooks.addFilter( 'nx_frontend_vc_card', NS, ( card ) => card );
		renderTheme( sample() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
	} );

	it( 'is never called for a built-in slug', () => {
		stubMatchMedia( false );
		const filter = jest.fn( () => <div className="hijacked" /> );
		hooks.addFilter( 'nx_frontend_vc_card', NS, filter );
		renderTheme( receipt() );
		stubMatchMedia( true );
		renderTheme( receipt() );
		expect( filter ).not.toHaveBeenCalled();
		expect( container.querySelector( '.hijacked' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
	} );

	it( 'a throwing filter falls back to the Receipt layout', () => {
		stubMatchMedia( false );
		const error = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		hooks.addFilter( 'nx_frontend_vc_card', NS, () => {
			throw new Error( 'broken add-on' );
		} );
		renderTheme( sample() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
		expect( error ).toHaveBeenCalled();
		error.mockRestore();
	} );
} );

describe( 'SlimShell', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it( 'renders the same frame as a built-in slim card', () => {
		const props = { config: receipt(), data: saleEntry(), id: 'nx-vc', dispatch: jest.fn(), themeName: 'vc-receipt' };
		render( <SlimShell props={ props } word="receipt" line="Jane D. just purchased" time="<span>5 days ago</span>" /> );
		const shell = container.innerHTML;
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		renderTheme( receipt(), saleEntry( { title: '' } ), {} );
		const card = container.querySelector( '.nx-vc-card.nx-vc-receipt.nx-vc-slim' );
		expect( card ).not.toBeNull();
		// Same structure: image, line 1 (msg + time), credit A, close.
		const outline = ( html ) => {
			const div = document.createElement( 'div' );
			div.innerHTML = html;
			const root = div.querySelector( '.nx-vc-slim' );
			return [ ...root.children ].map( ( c ) => c.className );
		};
		expect( outline( shell ) ).toEqual( outline( container.innerHTML ) );
	} );

	it( 'hides the time and marks no image and no credit', () => {
		const props = {
			config: { ...receipt(), disable_powered_by: true },
			data: saleEntry( { image_data: false } ),
			id: 'nx-vc',
			dispatch: jest.fn(),
			themeName: 'vc-sample',
		};
		render( <SlimShell props={ props } word="sample" line="Jane D." time="<span></span>" /> );
		const card = container.querySelector( '.nx-vc-card.nx-vc-sample.nx-vc-slim' );
		expect( card.classList.contains( 'is-no-img' ) ).toBe( true );
		expect( card.classList.contains( 'is-no-credit' ) ).toBe( true );
		expect( card.querySelector( '.nx-vc-slim-time' ) ).toBeNull();
		expect( card.querySelector( '.nx-vc-credit' ) ).toBeNull();
	} );
} );

describe( 'nx_frontend_vc_compact_rows', () => {
	const slots = [ '<span>Jane D.</span>', '<span>Blue T-shirt</span>', '<span>{{time}}</span>' ];

	it( 'returns the slots unchanged without a filter', () => {
		expect( vcCompactRows( 'vc-sample', slots ) ).toBe( slots );
	} );

	it( 'returns the add-on rows, with the slug and the slots as args', () => {
		const filter = jest.fn( ( rows, slug, s ) =>
			slug === 'vc-sample' ? [ `${ s[ 0 ] } bought`, `${ s[ 1 ] }`, `${ s[ 2 ] }` ] : rows
		);
		installHooks().addFilter( 'nx_frontend_vc_compact_rows', NS, filter );
		expect( vcCompactRows( 'vc-sample', slots ) ).toEqual( [
			'<span>Jane D.</span> bought',
			'<span>Blue T-shirt</span>',
			'<span>{{time}}</span>',
		] );
		expect( filter ).toHaveBeenCalledWith( slots, 'vc-sample', slots );
	} );

	it( 'ignores a filter that returns no array', () => {
		installHooks().addFilter( 'nx_frontend_vc_compact_rows', NS, () => 'rows' );
		expect( vcCompactRows( 'vc-sample', slots ) ).toBe( slots );
	} );

	it( 'is never called for a built-in slug', () => {
		const filter = jest.fn( () => [ 'hijacked' ] );
		installHooks().addFilter( 'nx_frontend_vc_compact_rows', NS, filter );
		expect( vcCompactRows( 'vc-receipt', [ 'a', 'b', 'c', 'd', 'e' ] ) ).toEqual( [ 'a b', 'c e', 'd' ] );
		expect( filter ).not.toHaveBeenCalled();
	} );

	it( 'feeds Pro’s mobile card (is_mobile) for an add-on key', () => {
		stubMatchMedia( true );
		const hooks = installHooks();
		addSlug( hooks );
		hooks.addFilter( 'nx_frontend_template', NS, ( value, settings, params ) => sampleSlots( settings, params ) ?? value );
		const card = jest.fn( () => <div className="nx-vc-card" /> );
		hooks.addFilter( 'nx_frontend_vc_card', NS, card );
		hooks.addFilter( 'nx_frontend_vc_compact_rows', NS, ( rows, slug, s ) =>
			slug === 'vc-sample' ? [ `${ s[ 0 ] } bought`, `${ s[ 1 ] }`, `${ s[ 2 ] }` ] : rows
		);
		renderTheme( sample(), saleEntry(), { is_mobile: true } );
		expect( card ).not.toHaveBeenCalled();
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps.map( ( p ) => p.firstElementChild.textContent ) ).toEqual( [ 'Jane D. bought', 'Blue T-shirt', '5 days ago' ] );
	} );
} );
