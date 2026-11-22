/**
 * Exit Intent "Do Not Show Again" (toggle) and "Do Not Show Again For" (days).
 *
 * Closing always writes the session key. With the toggle on it also writes a
 * cookie for the days, and only then does an existing cookie keep the popup
 * hidden. Payloads without the toggle (campaigns saved before it; the server
 * now sends it, see test-exit-intent-dismiss-toggle.php) behave exactly as
 * before: the days decide.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import useNotificationX from '../../../nxdev/notificationx/frontend/core/useNotificationX';
import { exitIntentCookieDays, isExitIntentCookieOn } from '../../../nxdev/notificationx/frontend/core/exitIntentDismiss';

const KEY = 'notificationx_exit_intent_12_exit_intent_custom_theme-one';
const rest = { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true };

const clearCookies = () =>
	document.cookie.split( ';' ).forEach( ( c ) => {
		document.cookie = c.split( '=' )[ 0 ].trim() + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
	} );

let container;
beforeEach( () => {
	sessionStorage.clear();
	localStorage.clear();
	clearCookies();
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

describe( 'exitIntentCookieDays', () => {
	it.each( [
		[ 'on with days', { exit_intent_cookie_enabled: true, exit_intent_cookie_days: 7 }, 7 ],
		[ 'on with days as a string', { exit_intent_cookie_enabled: true, exit_intent_cookie_days: '30' }, 30 ],
		[ 'on with 0 days', { exit_intent_cookie_enabled: true, exit_intent_cookie_days: 0 }, 0 ],
		[ 'off with days', { exit_intent_cookie_enabled: false, exit_intent_cookie_days: 7 }, 0 ],
		[ 'off as "false"', { exit_intent_cookie_enabled: 'false', exit_intent_cookie_days: 7 }, 0 ],
		[ 'on as "1"', { exit_intent_cookie_enabled: '1', exit_intent_cookie_days: 7 }, 7 ],
		[ 'no toggle, days (before the toggle)', { exit_intent_cookie_days: 7 }, 7 ],
		[ 'no toggle, 0 days', { exit_intent_cookie_days: 0 }, 0 ],
		[ 'no toggle, empty days', { exit_intent_cookie_days: '' }, 0 ],
		[ 'nothing', undefined, 0 ],
	] )( '%s', ( name, settings, days ) => {
		expect( exitIntentCookieDays( settings ) ).toBe( days );
	} );

	it( 'the cookie counts unless the toggle is off', () => {
		expect( isExitIntentCookieOn( {} ) ).toBe( true );
		expect( isExitIntentCookieOn( { exit_intent_cookie_enabled: null } ) ).toBe( true );
		expect( isExitIntentCookieOn( { exit_intent_cookie_enabled: true } ) ).toBe( true );
		expect( isExitIntentCookieOn( { exit_intent_cookie_enabled: false } ) ).toBe( false );
		expect( isExitIntentCookieOn( { exit_intent_cookie_enabled: '' } ) ).toBe( false );
	} );
} );

describe( 'closing the popup', () => {
	const close = ( settings ) => {
		const nxExitIntent = { id: 12, config: { nx_id: 12, source: 'exit_intent_custom', themes: 'exit_intent_custom_theme-one', ...settings } };
		act( () => {
			ReactDOM.render(
				<NotificationProvider value={ { rest, state: { is_pro: false } } }>
					<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ rest } dispatch={ jest.fn() } />
				</NotificationProvider>,
				container
			);
		} );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( sessionStorage.getItem( KEY ) ).toBe( 'closed' );
		return document.cookie.includes( `${ KEY }=closed` );
	};

	it( 'with the toggle on, writes the cookie for the days', () => {
		expect( close( { exit_intent_cookie_enabled: true, exit_intent_cookie_days: 7 } ) ).toBe( true );
	} );

	it( 'with the toggle off, writes only the session key', () => {
		expect( close( { exit_intent_cookie_enabled: false, exit_intent_cookie_days: 7 } ) ).toBe( false );
	} );

	it( 'a payload from before the toggle writes the cookie as before', () => {
		expect( close( { exit_intent_cookie_days: 7 } ) ).toBe( true );
	} );

	it( 'a payload from before the toggle with 0 days writes no cookie, as before', () => {
		expect( close( { exit_intent_cookie_days: 0 } ) ).toBe( false );
	} );
} );

describe( 'a dismissal cookie from an earlier visit', () => {
	let ctx;
	const Harness = ( { config } ) => {
		ctx = useNotificationX( { config } );
		return null;
	};
	const flush = async () => {
		for ( let i = 0; i < 10; i++ ) {
			await act( async () => {
				await Promise.resolve();
			} );
		}
	};
	const shown = async ( settings ) => {
		jest.useFakeTimers();
		const post = { nx_id: 12, type: 'exit_intent', source: 'exit_intent_custom', themes: 'exit_intent_custom_theme-one', exit_intent_mobile_disable: false, ...settings };
		const response = { settings: {}, exit_intent: { 12: { post, content: '' } } };
		global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( response ) } ) );
		act( () => {
			ReactDOM.render( <Harness config={ { rest, gmt_offset: 0, exit_intent: [ 12 ] } } />, container );
		} );
		await flush();
		act( () => {
			document.dispatchEvent( new MouseEvent( 'mouseleave', { clientY: 0 } ) );
		} );
		act( () => {
			jest.advanceTimersByTime( 1500 );
		} );
		return ctx.state.notices.length === 1;
	};

	beforeEach( () => {
		document.cookie = `${ KEY }=closed; path=/`;
	} );

	it( 'keeps the popup hidden with the toggle on', async () => {
		expect( await shown( { exit_intent_cookie_enabled: true, exit_intent_cookie_days: 7 } ) ).toBe( false );
	} );

	it( 'keeps it hidden for a payload from before the toggle', async () => {
		expect( await shown( { exit_intent_cookie_days: 7 } ) ).toBe( false );
	} );

	it( 'is ignored with the toggle off: the popup shows in a new session', async () => {
		expect( await shown( { exit_intent_cookie_enabled: false, exit_intent_cookie_days: 7 } ) ).toBe( true );
	} );

	it( 'the session key still hides it with the toggle off', async () => {
		sessionStorage.setItem( KEY, 'closed' );
		expect( await shown( { exit_intent_cookie_enabled: false } ) ).toBe( false );
	} );
} );
