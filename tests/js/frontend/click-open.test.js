/**
 * Click-open popups (nxdev/notificationx/frontend/core/clickOpen.ts and the
 * Popup component): "When to Open" = click or both.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import {
	clickSelectors,
	findTrigger,
	listenForClicks,
	opensAutomatically,
	opensOnClick,
	rememberOpener,
} from '../../../nxdev/notificationx/frontend/core/clickOpen';
import Popup from '../../../nxdev/notificationx/frontend/core/Popup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

const popup = ( extra = {} ) => ( { nx_id: 7, source: 'popup_notification', themes: 'popup_notification_theme-one', ...extra } );

describe( 'settings', () => {
	test( 'auto (and saved before the setting) opens as today; click and both listen', () => {
		expect( opensAutomatically( popup() ) ).toBe( true );
		expect( opensOnClick( popup() ) ).toBe( false );
		expect( opensAutomatically( popup( { popup_open_on: 'auto' } ) ) ).toBe( true );
		expect( opensAutomatically( popup( { popup_open_on: 'click' } ) ) ).toBe( false );
		expect( opensOnClick( popup( { popup_open_on: 'click' } ) ) ).toBe( true );
		expect( opensAutomatically( popup( { popup_open_on: 'both' } ) ) ).toBe( true );
		expect( opensOnClick( popup( { popup_open_on: 'both' } ) ) ).toBe( true );
		expect( opensOnClick( popup( { popup_open_on: 'weird' } ) ) ).toBe( false );
	} );

	test( 'the builder preview always shows it and never listens', () => {
		expect( opensAutomatically( popup( { popup_open_on: 'click', previewType: 'desktop' } ) ) ).toBe( true );
		expect( opensOnClick( popup( { popup_open_on: 'click', previewType: 'desktop' } ) ) ).toBe( false );
	} );

	test( 'targets: classes and IDs only, plus the #nx-popup-<id> link', () => {
		expect( clickSelectors( popup( { popup_click_selector: '.offer, #buy, bare, .offer, a[href], .x:hover' } ) ) ).toEqual( [
			'.offer',
			'#buy',
			'.bare',
			'a[href$="#nx-popup-7"]',
		] );
		expect( clickSelectors( popup() ) ).toEqual( [ 'a[href$="#nx-popup-7"]' ] );
	} );
} );

describe( 'listening', () => {
	let stop;
	afterEach( () => {
		stop && stop();
		document.body.innerHTML = '';
	} );

	test( 'a click on a target added later opens it, from the target or inside it', () => {
		const open = jest.fn();
		stop = listenForClicks( [ popup( { popup_open_on: 'click', popup_click_selector: '.offer' } ) ], open );
		document.body.innerHTML = '<p><a class="offer" href="/sale"><span id="inner">Sale</span></a></p>';
		const event = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		document.getElementById( 'inner' ).dispatchEvent( event );
		expect( open ).toHaveBeenCalledTimes( 1 );
		expect( open.mock.calls[ 0 ][ 1 ] ).toBe( document.querySelector( '.offer' ) );
		expect( event.defaultPrevented ).toBe( true );
	} );

	test( 'a link to #nx-popup-<id> opens it; other clicks do nothing', () => {
		const open = jest.fn();
		stop = listenForClicks( [ popup( { popup_open_on: 'both' } ), popup( { nx_id: 8 } ) ], open );
		document.body.innerHTML = '<a id="l7" href="#nx-popup-7">7</a><a id="l8" href="#nx-popup-8">8</a><a id="x" href="#top">x</a>';
		document.getElementById( 'x' ).click();
		document.getElementById( 'l8' ).click();
		expect( open ).not.toHaveBeenCalled();
		document.getElementById( 'l7' ).click();
		expect( open ).toHaveBeenCalledTimes( 1 );
		expect( open.mock.calls[ 0 ][ 0 ].nx_id ).toBe( 7 );
	} );

	test( 'Enter and Space on a non-button target open it; buttons rely on their own click', () => {
		const open = jest.fn();
		stop = listenForClicks( [ popup( { popup_open_on: 'click', popup_click_selector: '.offer' } ) ], open );
		document.body.innerHTML = '<div class="offer" tabindex="0" id="d">Open</div><button class="offer" id="b">Open</button>';
		const key = ( id, k ) => document.getElementById( id ).dispatchEvent( new KeyboardEvent( 'keydown', { key: k, bubbles: true, cancelable: true } ) );
		key( 'd', 'Enter' );
		key( 'd', ' ' );
		key( 'd', 'a' );
		expect( open ).toHaveBeenCalledTimes( 2 );
		key( 'b', 'Enter' );
		expect( open ).toHaveBeenCalledTimes( 2 );
	} );

	test( 'clicks inside a NotificationX popup never count; stopping removes the listeners', () => {
		const open = jest.fn();
		stop = listenForClicks( [ popup( { popup_open_on: 'click', popup_click_selector: '.offer' } ) ], open );
		document.body.innerHTML = '<div class="nx-popup-overlay"><a class="offer" id="in">x</a></div><a class="offer" id="out">y</a>';
		document.getElementById( 'in' ).click();
		expect( open ).not.toHaveBeenCalled();
		stop();
		stop = null;
		document.getElementById( 'out' ).click();
		expect( open ).not.toHaveBeenCalled();
	} );

	test( 'popups that open automatically get no listener', () => {
		const add = jest.spyOn( document, 'addEventListener' );
		stop = listenForClicks( [ popup(), popup( { popup_open_on: 'auto' } ) ], jest.fn() );
		expect( add ).not.toHaveBeenCalled();
		add.mockRestore();
		expect( findTrigger( null, [ '.x' ] ) ).toBeNull();
	} );
} );

describe( 'the popup', () => {
	let container;
	const context = { rest: { root: '/', namespace: 'notificationx/v1' }, state: { is_pro: false } };
	beforeEach( () => {
		jest.useFakeTimers();
		window.matchMedia = jest.fn( () => ( { matches: false, addEventListener() {}, removeEventListener() {} } ) );
		window.notificationXArr = [ {} ];
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		sessionStorage.clear();
	} );
	afterEach( () => {
		ReactDOM.unmountComponentAtNode( container );
		document.body.innerHTML = '';
		jest.useRealTimers();
		delete window.matchMedia;
	} );

	const render = ( config, dispatch = jest.fn() ) => {
		act( () => {
			ReactDOM.render(
				<NotificationProvider value={ context }>
					<Popup position="center" nxPopup={ { id: 'p1', config, data: {} } } dispatch={ dispatch } />
				</NotificationProvider>,
				container
			);
		} );
		return dispatch;
	};

	test( 'click-opened: shows at once even if closed before; Esc closes and focus goes back', () => {
		sessionStorage.setItem( 'notificationx_popup_7', 'closed' );
		const opener = document.createElement( 'button' );
		document.body.appendChild( opener );
		rememberOpener( 7, opener );
		const dispatch = render( popup( { nx_click_open: true, delay_before: 30, popup_title: 'Sale' } ) );
		const dialog = container.querySelector( '#nx-popup-7' );
		expect( dialog ).not.toBeNull();
		expect( dialog.getAttribute( 'role' ) ).toBe( 'dialog' );
		expect( document.activeElement ).toBe( dialog );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
		} );
		expect( document.activeElement ).toBe( opener );
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 'p1' } );
	} );

	test( 'an automatic popup is unchanged: waits for its delay, stays closed for the session, no dialog role, Esc does nothing', () => {
		render( popup( { delay_before: 2 } ) );
		expect( container.querySelector( '#nx-popup-7' ) ).toBeNull();
		act( () => {
			jest.advanceTimersByTime( 2000 );
		} );
		const el = container.querySelector( '#nx-popup-7' );
		expect( el ).not.toBeNull();
		expect( el.hasAttribute( 'role' ) ).toBe( false );
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
			jest.advanceTimersByTime( 400 );
		} );
		expect( container.querySelector( '#nx-popup-7' ) ).not.toBeNull();

		ReactDOM.unmountComponentAtNode( container );
		sessionStorage.setItem( 'notificationx_popup_7', 'closed' );
		const dispatch = render( popup() );
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 'p1' } );
	} );
} );
