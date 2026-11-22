/**
 * Exit Intent popups designed with the block editor ("Build With Gutenberg"):
 * dialog semantics, focus, Escape, backdrop, #nx-close links, the close keys,
 * and waiting for the design's block assets.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';

jest.mock( '../../../nxdev/notificationx/frontend/core/LoadAssets', () => ( { loadAssets: jest.fn() } ) );

// eslint-disable-next-line import/first
import { loadAssets } from '../../../nxdev/notificationx/frontend/core/LoadAssets';
// eslint-disable-next-line import/first
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
// eslint-disable-next-line import/first
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
// eslint-disable-next-line import/first
import { ASSETS_TIMEOUT } from '../../../nxdev/notificationx/frontend/core/ExitIntentGutenberg';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	state: { is_pro: false },
};

const HTML = '<div class="wp-block-group"><h2 class="wp-block-heading">Wait! Here’s 10% off</h2><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link" href="https://shop.example/cart">Use my 10% off</a></div></div><p><a href="#nx-close">No thanks</a></p></div>';

let container;
let dispatch;
let opener;
beforeEach( () => {
	sessionStorage.clear();
	document.cookie.split( ';' ).forEach( ( c ) => {
		document.cookie = c.split( '=' )[ 0 ].trim() + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
	} );
	document.body.className = '';
	loadAssets.mockReset();
	dispatch = jest.fn();
	opener = document.createElement( 'button' );
	opener.textContent = 'page button';
	document.body.appendChild( opener );
	opener.focus();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
	opener.remove();
	jest.useRealTimers();
} );

const render = ( extra = {} ) => {
	const nxExitIntent = {
		id: 36,
		config: {
			nx_id: 36,
			title: 'Coupon popup',
			source: 'exit_intent_custom',
			themes: null,
			mode: 'gutenberg',
			gutenberg_id: 9,
			gutenberg_html: HTML,
			gutenberg_layout: { width: '520px', radius: '16px' },
			exit_intent_cookie_days: 7,
			...extra,
		},
	};
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ frontendContext }>
				<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ frontendContext.rest } dispatch={ dispatch } />
			</NotificationProvider>,
			container
		);
	} );
	return container;
};
const dialog = () => container.querySelector( '.nx-exit-intent-gutenberg' );
const isOpen = () => !! container.querySelector( '.nx-exit-intent-overlay' );
const closedWithKey = () => {
	expect( sessionStorage.getItem( 'notificationx_exit_intent_36_' ) ).toBe( 'closed' );
	expect( document.cookie ).toContain( 'notificationx_exit_intent_36_=closed' );
	expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 36 } );
	expect( isOpen() ).toBe( false );
};

describe( 'Gutenberg Exit Intent popup', () => {
	it( 'renders the design as a labelled modal dialog', () => {
		render();
		const d = dialog();
		expect( d.getAttribute( 'role' ) ).toBe( 'dialog' );
		expect( d.getAttribute( 'aria-modal' ) ).toBe( 'true' );
		expect( d.getAttribute( 'aria-label' ) ).toBe( 'Coupon popup' );
		expect( d.style.getPropertyValue( '--nx-exit-width' ) ).toBe( '520px' );
		expect( d.style.getPropertyValue( '--nx-exit-radius' ) ).toBe( '16px' );
		expect( d.querySelector( '.nx-exit-intent-gutenberg-body h2' ).textContent ).toBe( 'Wait! Here’s 10% off' );
		expect( container.querySelector( '.nx-exit-intent-elementor' ) ).toBeNull();
		const close = d.querySelector( 'button.nx-exit-intent-close' );
		expect( close.getAttribute( 'type' ) ).toBe( 'button' );
		expect( close.getAttribute( 'aria-label' ) ).toBe( 'Close' );
	} );

	it( 'moves focus to the close button and gives it back on close', () => {
		render();
		expect( document.activeElement ).toBe( dialog().querySelector( '.nx-exit-intent-close' ) );
		act( () => {
			dialog().querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( document.activeElement ).toBe( opener );
	} );

	it( 'the × closes with the same key as other Exit Intent popups', () => {
		render();
		act( () => {
			dialog().querySelector( '.nx-exit-intent-close' ).click();
		} );
		closedWithKey();
	} );

	it( 'Escape closes it', () => {
		render();
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );
		} );
		closedWithKey();
	} );

	it( 'a backdrop click closes it; a click inside the design does not', () => {
		render();
		act( () => {
			dialog().querySelector( 'h2' ).click();
		} );
		expect( isOpen() ).toBe( true );
		act( () => {
			container.querySelector( '.nx-exit-intent-overlay' ).click();
		} );
		closedWithKey();
	} );

	it( 'a link to #nx-close closes it without navigating', () => {
		render();
		const link = dialog().querySelector( 'a[href="#nx-close"]' );
		const event = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		act( () => {
			link.dispatchEvent( event );
		} );
		expect( event.defaultPrevented ).toBe( true );
		closedWithKey();
	} );

	it( 'other links in the design are left alone', () => {
		render();
		const link = dialog().querySelector( 'a[href="https://shop.example/cart"]' );
		// Record whether the popup prevented the click, then stop jsdom's (unimplemented) navigation.
		let preventedByPopup = null;
		const guard = ( e ) => {
			preventedByPopup = e.defaultPrevented;
			e.preventDefault();
		};
		document.addEventListener( 'click', guard );
		act( () => {
			link.dispatchEvent( new MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
		} );
		document.removeEventListener( 'click', guard );
		expect( preventedByPopup ).toBe( false );
		expect( isOpen() ).toBe( true );
	} );

	it( 'Tab stays inside the dialog', () => {
		render();
		const items = dialog().querySelectorAll( 'button, a[href]' );
		const last = items[ items.length - 1 ];
		last.focus();
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Tab', bubbles: true } ) );
		} );
		expect( document.activeElement ).toBe( items[ 0 ] );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Tab', shiftKey: true, bubbles: true } ) );
		} );
		expect( document.activeElement ).toBe( last );
	} );

	it( 'without a close button, focus goes to the dialog and Escape still closes', () => {
		render( { show_close_button: false } );
		expect( dialog().querySelector( '.nx-exit-intent-close' ) ).toBeNull();
		expect( document.activeElement ).toBe( dialog() );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );
		} );
		closedWithKey();
	} );

	it( 'an empty design falls through to the built-in theme', () => {
		render( { gutenberg_html: '' } );
		expect( dialog() ).toBeNull();
		expect( container.querySelector( '.nx-exit-intent-theme-one' ) ).not.toBeNull();
	} );
} );

describe( 'Block assets', () => {
	it( 'does not fetch anything when the page already has them', () => {
		document.body.classList.add( 'nx-block-assets-9' );
		render( { gutenberg_url: 'https://example.com/?nx_exit_intent_eb=d' } );
		expect( loadAssets ).not.toHaveBeenCalled();
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( '' );
	} );

	it( 'stays hidden until the design\'s assets are loaded', async () => {
		let done;
		loadAssets.mockReturnValue( new Promise( ( r ) => ( done = r ) ) );
		render( { gutenberg_url: 'https://example.com/?nx_exit_intent_eb=d' } );
		expect( loadAssets ).toHaveBeenCalledWith( 'https://example.com/?nx_exit_intent_eb=d' );
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( 'hidden' );
		expect( dialog().getAttribute( 'aria-busy' ) ).toBe( 'true' );
		await act( async () => {
			done();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( '' );
		expect( dialog().getAttribute( 'aria-busy' ) ).toBeNull();
	} );

	it( 'shows anyway after the time limit', () => {
		jest.useFakeTimers();
		loadAssets.mockReturnValue( new Promise( () => {} ) );
		render( { gutenberg_url: 'https://example.com/?nx_exit_intent_eb=d' } );
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( 'hidden' );
		act( () => {
			jest.advanceTimersByTime( ASSETS_TIMEOUT );
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( '' );
	} );

	it( 'a failed fetch still shows the popup', async () => {
		loadAssets.mockReturnValue( Promise.reject( new Error( 'offline' ) ) );
		render( { gutenberg_url: 'https://example.com/?nx_exit_intent_eb=d' } );
		await act( async () => {} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ).style.visibility ).toBe( '' );
	} );
} );
