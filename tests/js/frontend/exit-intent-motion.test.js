/**
 * Exit Intent Show / Hide animations (NotificationX Pro): the card takes the
 * classes from the `nx_frontend_animation` filter, the backdrop fades with it,
 * and every way of closing (×, Escape, backdrop, a #nx-close link, the end
 * date) waits for the exit animation before the popup goes. Reduced motion
 * closes at once. Pro's own themes and Free without Pro never animate.
 *
 * The "None" default is covered by exit-intent-motion-none.test.js.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import { installHooks } from '../helpers';

jest.mock( '../../../nxdev/notificationx/frontend/core/LoadAssets', () => ( { loadAssets: jest.fn( () => new Promise( () => {} ) ) } ) );

// eslint-disable-next-line import/first
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
// eslint-disable-next-line import/first
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
// eslint-disable-next-line import/first
import { EXIT_INTENT_EXIT_MS, exitIntentLeaving, exitIntentMotion, exitIntentShown } from '../../../nxdev/notificationx/frontend/core/exitIntentMotion';

const rest = { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false };
const KEY = 'notificationx_exit_intent_36_';

// The same decisions as Pro's filter (nxdev/frontend-themes/features/animation.ts)
// when free passes no defaultClass and no animationStyle.
const proFilter = ( value, { settings, animation, animationStyle, is_pro } ) => {
	if ( value !== undefined ) return value;
	const show = settings?.animation_notification_show;
	const hide = settings?.animation_notification_hide;
	if ( ! ( is_pro && ( show !== 'default' || hide !== 'default' ) ) ) return value;
	const style = {};
	let effect;
	if ( hide !== 'default' && show === 'default' ) {
		if ( animation ) effect = hide;
		else style.animation = animationStyle;
	} else if ( show !== 'default' && hide === 'default' ) {
		if ( ! animation ) effect = show;
		else style.animation = animationStyle;
	} else {
		effect = animation ? hide : show;
	}
	if ( typeof effect === 'string' && effect.startsWith( 'nx-anim-' ) ) return { classNames: [ effect ], style };
	return { classNames: [ 'animate__animated', effect, 'animate__faster', '' ], style };
};

const ELEMENTOR_HTML = '<div class="elementor"><a class="elementor-button" href="#nx-close">No thanks</a></div>';
const GUTENBERG_HTML = '<div class="wp-block-group"><h2>Wait</h2><p><a href="#nx-close">No thanks</a></p><div class="wp-block-notificationx-countdown">11:59</div></div>';

const CARDS = {
	'theme one': [ { themes: 'exit_intent_custom_theme-one' }, '.nx-exit-intent-popup' ],
	'theme three': [ { themes: 'exit_intent_custom_theme-three' }, '.nx-exit-intent-popup' ],
	'theme four': [ { themes: 'exit_intent_custom_theme-four' }, '.nx-exit-intent-popup' ],
	elementor: [ { themes: null, mode: 'elementor', elementor_id: 210, elementor_html: ELEMENTOR_HTML }, '.nx-exit-intent-elementor' ],
	gutenberg: [ { themes: null, mode: 'gutenberg', title: 'Coupon', gutenberg_id: 9, gutenberg_html: GUTENBERG_HTML }, '.nx-exit-intent-gutenberg' ],
};
const ZOOM = { exit_intent_animation_show: 'animate__zoomIn', exit_intent_animation_hide: 'animate__zoomOut' };

let container;
let dispatch;
let reduce;
beforeEach( () => {
	jest.useFakeTimers();
	sessionStorage.clear();
	document.body.className = '';
	reduce = false;
	window.matchMedia = jest.fn( ( q ) => ( { matches: reduce && q.includes( 'reduce' ), media: q, addListener() {}, removeListener() {} } ) );
	dispatch = jest.fn();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	installHooks().addFilter( 'nx_frontend_animation', 'test', proFilter );
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	// A test that ends during an exit leaves the popup "leaving".
	exitIntentLeaving.done( 36 );
	exitIntentShown.forget( 36 );
	jest.useRealTimers();
	delete window.matchMedia;
} );

const render = ( config, isPro = true ) => {
	const nxExitIntent = { id: 36, config: { nx_id: 36, source: 'exit_intent_custom', exit_intent_cookie_enabled: true, exit_intent_cookie_days: 7, ...config } };
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ { rest, state: { is_pro: isPro } } }>
				<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ rest } dispatch={ dispatch } />
			</NotificationProvider>,
			container
		);
	} );
};
const overlay = () => container.querySelector( '.nx-exit-intent-overlay' );
const removed = () => dispatch.mock.calls.filter( ( [ a ] ) => a?.type === 'REMOVE_NOTIFICATION' ).length;
const wait = ( ms ) => act( () => {
	jest.advanceTimersByTime( ms );
} );

describe.each( Object.entries( CARDS ) )( '%s', ( name, [ design, card ] ) => {
	it( 'enters with the Show animation, the backdrop fading in', () => {
		render( { ...design, ...ZOOM } );
		expect( container.querySelector( card ).className ).toContain( 'animate__animated animate__zoomIn animate__faster' );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( true );
	} );

	it( 'the × plays the Hide animation, then removes the popup once', () => {
		render( { ...design, ...ZOOM } );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		// Closed for this visit at once; still on screen while it leaves.
		expect( sessionStorage.getItem( KEY + ( design.themes || '' ) ) ).toBe( 'closed' );
		expect( container.querySelector( card ).className ).toContain( 'animate__zoomOut' );
		expect( container.querySelector( card ).className ).not.toContain( 'animate__zoomIn' );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( false );
		expect( removed() ).toBe( 0 );
		// A second click while it leaves does nothing more.
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		wait( EXIT_INTENT_EXIT_MS - 1 );
		expect( overlay() ).not.toBeNull();
		wait( 1 );
		expect( overlay() ).toBeNull();
		expect( removed() ).toBe( 1 );
	} );

	it( 'the backdrop click plays the Hide animation too', () => {
		render( { ...design, ...ZOOM } );
		act( () => {
			overlay().dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
		} );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
	} );

	it( 'with reduced motion it closes at once', () => {
		reduce = true;
		render( { ...design, ...ZOOM } );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( overlay() ).toBeNull();
		expect( removed() ).toBe( 1 );
	} );

	it( 'Show only: no exit animation, it closes at once', () => {
		render( { ...design, exit_intent_animation_show: 'nx-anim-pop', exit_intent_animation_hide: 'default' } );
		expect( container.querySelector( card ).className ).toContain( 'nx-anim-pop' );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( overlay() ).toBeNull();
	} );

	it( 'Hide only: no entrance, the exit still plays', () => {
		render( { ...design, exit_intent_animation_show: 'default', exit_intent_animation_hide: 'nx-anim-pop-out' } );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( false );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( container.querySelector( card ).className ).toContain( 'nx-anim-pop-out' );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
	} );

	it( 'without Pro nothing animates', () => {
		render( { ...design, ...ZOOM }, false );
		expect( container.querySelector( card ).className ).not.toContain( 'animate__' );
		expect( overlay().className ).not.toContain( 'nx-exit-intent-anim' );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( overlay() ).toBeNull();
	} );
} );

describe( 'a popup re-mounted while open', () => {
	// NotificationContainer re-mounts the popup when another notification's
	// position group appears or goes (seen on Storefy with sales notifications).
	it.each( Object.entries( CARDS ) )( '%s does not play its entrance again, and still leaves with the exit', ( name, [ design, card ] ) => {
		render( { ...design, ...ZOOM } );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( true );
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		render( { ...design, ...ZOOM } );
		expect( overlay().className ).not.toContain( 'nx-exit-intent-anim' );
		expect( container.querySelector( card ).className ).not.toContain( 'zoomIn' );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		expect( container.querySelector( card ).className ).toContain( 'animate__zoomOut' );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
		// The next popup of this campaign enters normally again.
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		render( { ...design, ...ZOOM } );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( true );
	} );
} );

describe( 'a popup re-mounted while it leaves', () => {
	// NotificationContainer renders the position groups by index, so another
	// notification arriving can re-mount the Exit Intent popup.
	it.each( Object.entries( CARDS ) )( '%s finishes leaving instead of coming back', ( name, [ design ] ) => {
		render( { ...design, ...ZOOM } );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		wait( 200 );
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		render( { ...design, ...ZOOM } );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( false );
		wait( EXIT_INTENT_EXIT_MS - 200 );
		expect( overlay() ).toBeNull();
		expect( removed() ).toBe( 1 );
		// The next popup of this campaign (a new page view) shows normally.
		dispatch.mockClear();
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		render( { ...design, ...ZOOM } );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( true );
	} );
} );

describe( 'Gutenberg and Elementor designs', () => {
	it( 'a #nx-close link plays the exit animation (Gutenberg)', () => {
		render( { ...CARDS.gutenberg[ 0 ], ...ZOOM } );
		act( () => {
			container.querySelector( 'a[href="#nx-close"]' ).click();
		} );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
		expect( removed() ).toBe( 1 );
	} );

	it( 'Escape plays the exit animation (Gutenberg)', () => {
		render( { ...CARDS.gutenberg[ 0 ], ...ZOOM } );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );
		} );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );
		} );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
		expect( removed() ).toBe( 1 );
	} );

	it( 'the design (countdown included) stays in place while the popup leaves', () => {
		render( { ...CARDS.gutenberg[ 0 ], ...ZOOM } );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( container.querySelector( '.wp-block-notificationx-countdown' ) ).not.toBeNull();
	} );

	it( 'a Gutenberg design waiting for its assets starts animating only once shown', () => {
		render( { ...CARDS.gutenberg[ 0 ], ...ZOOM, gutenberg_url: 'https://example.com/design/' } );
		expect( container.querySelector( '.nx-exit-intent-gutenberg' ).className ).not.toContain( 'animate__' );
		expect( overlay().className ).not.toContain( 'nx-exit-intent-anim-in' );
		wait( 8000 );
		expect( container.querySelector( '.nx-exit-intent-gutenberg' ).className ).toContain( 'animate__zoomIn' );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-in' ) ).toBe( true );
	} );
} );

describe( 'end date', () => {
	it( 'the popup leaves with the exit animation when the end time arrives, without close keys', () => {
		render( { themes: 'exit_intent_custom_theme-one', ...ZOOM, exit_intent_hide_after_end: true, exit_intent_end_ts: Date.now() + 1000 } );
		wait( 1000 );
		expect( overlay().classList.contains( 'nx-exit-intent-anim-out' ) ).toBe( true );
		wait( EXIT_INTENT_EXIT_MS );
		expect( overlay() ).toBeNull();
		expect( sessionStorage.length ).toBe( 0 );
	} );
} );

describe( 'Pro themes', () => {
	it( 'get no animation and the same overlay class and close as before', () => {
		const seen = [];
		installHooks();
		window.wp.hooks.addFilter( 'nx_frontend_animation', 'test', proFilter );
		window.wp.hooks.addFilter( 'nx_exit_intent_theme', 'test', ( node, props ) => {
			seen.push( props.overlayClass );
			return <div className={ props.overlayClass }><button className="nx-exit-intent-close" onClick={ props.handleClose }>×</button></div>;
		} );
		render( { themes: 'exit_intent_custom_theme-five', ...ZOOM } );
		expect( seen[ 0 ] ).toBe( 'nx-exit-intent-overlay nx-exit-intent-position-center' );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( overlay() ).toBeNull();
	} );
} );

describe( 'exitIntentLeaving', () => {
	it( 'counts down, and forgets an exit that ended over a second ago', () => {
		exitIntentLeaving.start( 7, 500 );
		expect( exitIntentLeaving.remaining( 7 ) ).toBe( 500 );
		wait( 400 );
		expect( exitIntentLeaving.remaining( 7 ) ).toBe( 100 );
		wait( 600 );
		expect( exitIntentLeaving.remaining( 7 ) ).toBe( 0 );
		wait( 1000 );
		expect( exitIntentLeaving.remaining( 7 ) ).toBeNull();
		expect( exitIntentLeaving.remaining( 8 ) ).toBeNull();
	} );
} );

describe( 'exitIntentMotion', () => {
	it( 'is null for "None", missing values and without a filter answer', () => {
		expect( exitIntentMotion( {}, true, false ) ).toBeNull();
		expect( exitIntentMotion( { exit_intent_animation_show: 'default', exit_intent_animation_hide: 'default' }, true, false ) ).toBeNull();
		expect( exitIntentMotion( ZOOM, false, false ) ).toBeNull();
	} );

	it( 'drops empty classes and style values', () => {
		expect( exitIntentMotion( { exit_intent_animation_show: 'default', exit_intent_animation_hide: 'animate__fadeOut' }, true, false ) ).toEqual( {
			cardClass: 'animate__animated animate__faster',
			cardStyle: {},
			animatesIn: false,
			animatesOut: true,
		} );
	} );
} );
