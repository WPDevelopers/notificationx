import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Popup from '../../../nxdev/notificationx/frontend/core/Popup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import cookie from 'react-cookies';

/**
 * Popup form submit and the "After a form submit" rule.
 *
 * "Today's behaviour" pins exactly what a submit and a close store and dispatch
 * (cookies, sessionStorage, localStorage, window.notificationXClosed, the
 * REMOVE_NOTIFICATION dispatches). It passed against Popup.tsx before the
 * submit rule existed, and must keep passing for "Same as closing", for a
 * notification saved before the setting, and whenever the rule can't apply.
 *
 * react-cookies keeps cookies in memory under NODE_ENV=test instead of writing
 * document.cookie, so cookies are read from both, and each cookie.save() call
 * is recorded with its path and lifetime in days.
 */

const DAY = 24 * 60 * 60 * 1000;
const SCOPE = 'a1b2c3d4e5';
const SUBMITTED = `notificationx_submitted_${ SCOPE }_12`;
const ENTRY = {
	rest: { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	storage_scope: SCOPE,
	state: { is_pro: false },
};

const baseSettings = {
	nx_id: 12,
	type: 'popup',
	source: 'popup_notification',
	themes: 'popup_notification_theme-five',
	popup_button_text: 'Subscribe',
	cache_duration_for_dont_show: 10,
	delay_before: 0,
};

let container;
let dispatch;

const clearCookies = () => {
	Object.keys( cookie.loadAll() ).forEach( ( name ) => cookie.remove( name, { path: '/' } ) );
	document.cookie.split( ';' ).forEach( ( c ) => {
		const name = c.split( '=' )[ 0 ].trim();
		if ( name ) {
			document.cookie = `${ name }=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
		}
	} );
};

beforeEach( () => {
	jest.restoreAllMocks();
	jest.useFakeTimers();
	localStorage.clear();
	sessionStorage.clear();
	clearCookies();
	delete window.notificationXClosed;
	window.notificationXArr = [ {} ];
	dispatch = jest.fn();
	jest.spyOn( cookie, 'save' );
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );

afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	jest.useRealTimers();
	delete global.fetch;
} );

const flush = async () => {
	for ( let i = 0; i < 10; i++ ) {
		await act( async () => {
			await Promise.resolve();
		} );
	}
};

const render = ( settings, entry = ENTRY ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ entry }>
				<Popup position="center" nxPopup={ { id: 'popup-uuid', config: { ...baseSettings, ...settings }, data: {} } } dispatch={ dispatch } />
			</NotificationProvider>,
			container
		);
	} );
	act( () => {
		jest.advanceTimersByTime( 0 );
	} );
	if ( settings.convert_to_exit_intent ) {
		act( () => {
			document.documentElement.dispatchEvent( new MouseEvent( 'mouseleave', { clientY: 0 } ) );
		} );
	}
	expect( container.querySelector( 'form' ) ).not.toBeNull();
};

const reply = ( body ) => {
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( body ) } ) );
};

const submit = async () => {
	act( () => {
		container.querySelector( 'form' ).dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
	} );
	await flush();
};

// Everything a submit or close leaves behind.
const traces = () => ( {
	cookies: [
		...document.cookie.split( ';' ).map( ( c ) => c.trim() ).filter( Boolean ),
		...Object.entries( cookie.loadAll() ).map( ( [ name, value ] ) => `${ name }=${ value }` ),
	].sort(),
	saves: cookie.save.mock.calls.map( ( [ name, value, opt ] ) => [ name, value, opt?.path, opt?.expires ? Math.round( ( opt.expires - Date.now() ) / DAY ) : null ] ),
	session: { ...sessionStorage },
	local: { ...localStorage },
	closed: window.notificationXClosed,
	dispatched: dispatch.mock.calls.map( ( [ action ] ) => action ),
} );

const REMOVE = { type: 'REMOVE_NOTIFICATION', payload: 'popup-uuid' };

// What a successful submit leaves behind today, per close rule.
const TODAY = {
	show_notification_next_visit: { cookies: [], saves: [], session: { notificationx_12: 'closed' }, local: {}, closed: undefined, dispatched: [ REMOVE, REMOVE ] },
	dont_show_notification: { cookies: [ 'notificationx_12=true' ], saves: [ [ 'notificationx_12', true, '/', 10 ] ], session: {}, local: {}, closed: undefined, dispatched: [ REMOVE, REMOVE ] },
	show_notification_every_page: { cookies: [], saves: [], session: {}, local: {}, closed: { 12: true }, dispatched: [ REMOVE, REMOVE ] },
};

// Settings for which a submit must behave exactly as today.
const AS_TODAY = [
	[ 'saved before the setting', {} ],
	[ 'Same as closing', { submit_reappearance: 'same_as_close' } ],
	[ '"days" without a usable number', { submit_reappearance: 'days', submit_hide_days: 0 } ],
	[ 'an unknown value', { submit_reappearance: 'forever' } ],
];

describe( "today's behaviour", () => {
	describe.each( AS_TODAY )( '%s', ( label, rule ) => {
		it.each( Object.keys( TODAY ) )( 'a submit with the %s close rule stores what it stores today', async ( reappearance ) => {
			reply( { success: true } );
			render( { ...rule, notification_reappearance: reappearance } );
			await submit();
			expect( global.fetch ).toHaveBeenCalledTimes( 1 );
			// Nothing is stored before the success message has shown.
			expect( traces() ).toEqual( { cookies: [], saves: [], session: {}, local: {}, closed: undefined, dispatched: [] } );
			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );
			expect( traces() ).toEqual( TODAY[ reappearance ] );
			act( () => {
				jest.advanceTimersByTime( 300 );
			} );
			expect( container.querySelector( 'form' ) ).toBeNull();
		} );

		it( 'a submit with no close rule set stores the "next visit" key', async () => {
			reply( { success: true } );
			render( { ...rule } );
			await submit();
			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );
			expect( traces() ).toEqual( TODAY.show_notification_next_visit );
		} );

		it( 'a submit in "Convert to Exit Intent" mode also sets the exit intent cookie', async () => {
			reply( { success: true } );
			render( { ...rule, convert_to_exit_intent: true, exit_intent_cookie_duration: 7 } );
			await submit();
			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );
			expect( traces() ).toEqual( { ...TODAY.show_notification_next_visit, cookies: [ 'nx_exit_intent_12=1' ] } );
		} );
	} );

	it.each( [ [ 'a refused', { success: false } ], [ 'an empty', {} ] ] )( '%s reply stores nothing and leaves the popup open', async ( label, body ) => {
		reply( body );
		render( { submit_reappearance: 'never' } );
		await submit();
		act( () => {
			jest.advanceTimersByTime( 2000 );
		} );
		expect( traces() ).toEqual( { cookies: [], saves: [], session: {}, local: {}, closed: undefined, dispatched: [] } );
		expect( container.querySelector( 'form' ) ).not.toBeNull();
	} );

	it( 'a submit whose request fails stores nothing', async () => {
		global.fetch = jest.fn( () => Promise.reject( new Error( 'offline' ) ) );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		render( { submit_reappearance: 'never' } );
		await submit();
		act( () => {
			jest.advanceTimersByTime( 2000 );
		} );
		expect( traces() ).toEqual( { cookies: [], saves: [], session: {}, local: {}, closed: undefined, dispatched: [] } );
	} );

	it.each( [ 'same_as_close', 'days', 'never' ] )( 'the close button keeps the close rule with submit rule %s', ( rule ) => {
		render( { submit_reappearance: rule, submit_hide_days: 30, notification_reappearance: 'dont_show_notification' } );
		act( () => {
			container.querySelector( '.nx-popup-close' ).click();
		} );
		expect( traces() ).toEqual( { cookies: [ 'notificationx_12=true' ], saves: [ [ 'notificationx_12', true, '/', 10 ] ], session: {}, local: {}, closed: undefined, dispatched: [ REMOVE ] } );
	} );
} );

describe( 'the submit rule', () => {
	it.each( [ [ 'never', {} ], [ 'days', { submit_hide_days: 30 } ] ] )( '"%s" remembers the submit and writes no close key', async ( rule, extra ) => {
		const now = Date.now();
		reply( { success: true } );
		render( { submit_reappearance: rule, ...extra, notification_reappearance: 'dont_show_notification' } );
		await submit();
		// Remembered as soon as the server accepted it.
		expect( JSON.parse( localStorage.getItem( SUBMITTED ) ) ).toEqual( { v: 1, at: now } );
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( traces() ).toEqual( {
			cookies: [],
			saves: [],
			session: {},
			local: { [ SUBMITTED ]: JSON.stringify( { v: 1, at: now } ) },
			closed: undefined,
			dispatched: [ REMOVE, REMOVE ],
		} );
		act( () => {
			jest.advanceTimersByTime( 300 );
		} );
		expect( container.querySelector( 'form' ) ).toBeNull();
	} );

	it( 'replaces the exit intent cookie in "Convert to Exit Intent" mode', async () => {
		reply( { success: true } );
		render( { submit_reappearance: 'never', convert_to_exit_intent: true, exit_intent_cookie_duration: 7 } );
		await submit();
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( traces().cookies ).toEqual( [] );
		expect( traces().session ).toEqual( {} );
		expect( localStorage.getItem( SUBMITTED ) ).not.toBeNull();
	} );

	it( 'falls back to the close keys when the submit cannot be stored', async () => {
		const setItem = Storage.prototype.setItem;
		jest.spyOn( Storage.prototype, 'setItem' ).mockImplementation( function ( key, value ) {
			if ( this === window.localStorage ) {
				throw new DOMException( 'full', 'QuotaExceededError' );
			}
			return setItem.call( this, key, value );
		} );
		reply( { success: true } );
		render( { submit_reappearance: 'never' } );
		await submit();
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( traces() ).toEqual( TODAY.show_notification_next_visit );
	} );

	it( 'keeps the close keys in the builder preview', async () => {
		reply( { success: true } );
		render( { submit_reappearance: 'never' }, { ...ENTRY, nxPreview: true } );
		await submit();
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( traces() ).toEqual( TODAY.show_notification_next_visit );
	} );
} );
