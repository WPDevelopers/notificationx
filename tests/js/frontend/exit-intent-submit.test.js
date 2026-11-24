import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from '../helpers';

/**
 * Exit Intent form submits and the "After a form submit" rule.
 *
 * "Today's behaviour" pins what every submit path and the close button store
 * (the session and cookie close key, the REMOVE_NOTIFICATION dispatch). It
 * passed against ExitIntentPopup.tsx before the submit rule existed, and must
 * keep passing for "Same as closing" and whenever the rule can't apply.
 *
 * Paths: Free's theme one form; a theme from the `nx_exit_intent_theme` filter
 * that submits through `submitEntry` and closes through `handleClose`, as Pro's
 * theme seven does; an Elementor design (NotificationX Form widget event,
 * Elementor Pro's jQuery event, a success message appearing).
 */

const SCOPE = 'a1b2c3d4e5';
const SUBMITTED = `notificationx_submitted_${ SCOPE }_12`;
const REST = { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true };
const ENTRY = { rest: REST, storage_scope: SCOPE, state: { is_pro: true } };
const REMOVE = { type: 'REMOVE_NOTIFICATION', payload: 'ei-uuid' };

const closeKey = ( theme ) => `notificationx_exit_intent_12_exit_intent_custom_${ theme }`;

let container;
let dispatch;

const clearCookies = () => {
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
	dispatch = jest.fn();
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
	delete window.jQuery;
} );

const flush = async () => {
	for ( let i = 0; i < 10; i++ ) {
		await act( async () => {
			await Promise.resolve();
		} );
	}
};

const reply = ( body ) => {
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( body ) } ) );
};

const render = ( settings, { entry = ENTRY, rest = REST } = {} ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ entry }>
				<ExitIntentPopup
					nxExitIntent={ { id: 'ei-uuid', config: { nx_id: 12, source: 'exit_intent_custom', type: 'exit_intent', exit_intent_cookie_days: 7, ...settings } } }
					rest={ rest }
					dispatch={ dispatch }
				/>
			</NotificationProvider>,
			container
		);
	} );
};

const submitForm = async ( selector = 'form' ) => {
	act( () => {
		container.querySelector( selector ).dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
	} );
	await flush();
};

const wait = ( ms ) => act( () => {
	jest.advanceTimersByTime( ms );
} );

// Everything a submit or close leaves behind.
const traces = () => ( {
	cookies: document.cookie.split( ';' ).map( ( c ) => c.trim() ).filter( Boolean ).sort(),
	session: { ...sessionStorage },
	local: { ...localStorage },
	dispatched: dispatch.mock.calls.map( ( [ action ] ) => action ),
} );

const closedAsToday = ( theme, days = 7 ) => ( {
	cookies: days > 0 ? [ `${ closeKey( theme ) }=closed` ] : [],
	session: { [ closeKey( theme ) ]: 'closed' },
	local: {},
	dispatched: [ REMOVE ],
} );

const NOTHING = { cookies: [], session: {}, local: {}, dispatched: [] };

// A stand-in for Pro's theme seven (notificationx-pro nxdev/frontend-themes/exit-intent/index.tsx):
// submitEntry, then handleClose after 2.5 s, or at once when submitEntry resolves false.
const installProSeven = () => {
	installHooks().addFilter( 'nx_exit_intent_theme', 'test', ( node, props ) => {
		if ( props.theme !== 'theme-seven' ) {
			return node;
		}
		const onSubmit = ( e ) => {
			e.preventDefault();
			( async () => {
				try {
					if ( ! ( await props.submitEntry( { nx_id: '12', theme: props.settings.themes, email: 'a@b.c' } ) ) ) {
						props.handleClose();
						return;
					}
					setTimeout( () => props.handleClose(), 2500 );
				} catch ( err ) {
					props.handleClose();
				}
			} )();
		};
		return (
			<div className={ props.overlayClass }>
				<button className="nx-exit-intent-close" onClick={ props.handleClose }>×</button>
				<form className="pro-t7-form" onSubmit={ onSubmit } />
			</div>
		);
	} );
};

const ELEMENTOR = {
	themes: 'exit_intent_custom_theme-one',
	mode: 'elementor',
	elementor_html: '<div class="elementor"><form class="nx-form" data-nx-id="12"><div class="nx-form-message"></div></form></div>',
};

const signals = {
	'the NotificationX Form widget event': () => document.dispatchEvent( new CustomEvent( 'nx:exit-intent-close', { detail: { nxId: '12' } } ) ),
	'a success message appearing': () => {
		const message = container.querySelector( '.nx-form-message' );
		message.classList.add( 'is-success' );
		message.textContent = 'Thanks';
	},
};

// Settings under which every submit must behave exactly as today.
const AS_TODAY = [
	[ 'saved before the setting', {} ],
	[ 'Same as closing', { submit_reappearance: 'same_as_close' } ],
];

describe( "today's behaviour", () => {
	describe.each( AS_TODAY )( '%s', ( label, rule ) => {
		it( 'theme one: a submit closes after 2.5 s with the close keys', async () => {
			reply( { success: true } );
			render( { ...rule, themes: 'exit_intent_custom_theme-one' } );
			await submitForm( '.nx-exit-intent-form' );
			expect( global.fetch ).toHaveBeenCalledTimes( 1 );
			expect( traces() ).toEqual( NOTHING );
			wait( 2500 );
			expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
		} );

		it( 'theme one: with "Do Not Show Again For" 0, only the session key', async () => {
			reply( { success: true } );
			render( { ...rule, themes: 'exit_intent_custom_theme-one', exit_intent_cookie_days: 0 } );
			await submitForm( '.nx-exit-intent-form' );
			wait( 2500 );
			expect( traces() ).toEqual( closedAsToday( 'theme-one', 0 ) );
		} );

		it( 'a filter theme (Pro theme seven): a submit closes with the close keys', async () => {
			installProSeven();
			reply( { success: true } );
			render( { ...rule, themes: 'exit_intent_custom_theme-seven' } );
			await submitForm( '.pro-t7-form' );
			expect( traces() ).toEqual( NOTHING );
			wait( 2500 );
			expect( traces() ).toEqual( closedAsToday( 'theme-seven' ) );
		} );

		it.each( Object.keys( signals ) )( 'Elementor: %s closes after 2 s with the close keys', async ( signal ) => {
			render( { ...rule, ...ELEMENTOR } );
			act( () => {
				signals[ signal ]();
			} );
			// A MutationObserver reports in a microtask.
			await flush();
			expect( traces() ).toEqual( NOTHING );
			wait( 2000 );
			expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
		} );
	} );

	it.each( [ [ 'a refused', { success: false } ], [ 'an empty', {} ] ] )( '%s reply still closes with the close keys (no submit remembered)', async ( label, body ) => {
		reply( body );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' } );
		await submitForm( '.nx-exit-intent-form' );
		wait( 2500 );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );

	it( 'a failed request still closes with the close keys', async () => {
		global.fetch = jest.fn( () => Promise.reject( new Error( 'offline' ) ) );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' } );
		await submitForm( '.nx-exit-intent-form' );
		wait( 2500 );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );

	it( 'a page without a REST URL closes at once with the close keys', async () => {
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' }, { rest: undefined } );
		await submitForm( '.nx-exit-intent-form' );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );

	it.each( [ 'same_as_close', 'days', 'never' ] )( 'the close button keeps the close rule with submit rule %s', ( rule ) => {
		render( { submit_reappearance: rule, submit_hide_days: 30, themes: 'exit_intent_custom_theme-one' } );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );

	it( 'submitEntry resolves true for a refused submit, as Pro theme seven expects', async () => {
		let submitEntry;
		installHooks().addFilter( 'nx_exit_intent_theme', 'test', ( node, props ) => {
			submitEntry = props.submitEntry;
			return <div />;
		} );
		reply( { success: false } );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-seven' } );
		let result;
		await act( async () => {
			result = await submitEntry( { nx_id: '12' } );
		} );
		expect( result ).toBe( true );
		expect( localStorage.length ).toBe( 0 );
	} );
} );

describe( 'the submit rule', () => {
	it.each( [ [ 'never', {} ], [ 'days', { submit_hide_days: 30 } ] ] )( 'theme one, "%s": remembers the submit and writes no close key', async ( rule, extra ) => {
		const now = Date.now();
		reply( { success: true } );
		render( { submit_reappearance: rule, ...extra, themes: 'exit_intent_custom_theme-one' } );
		await submitForm( '.nx-exit-intent-form' );
		expect( JSON.parse( localStorage.getItem( SUBMITTED ) ) ).toEqual( { v: 1, at: now } );
		wait( 2500 );
		expect( traces() ).toEqual( { cookies: [], session: {}, local: { [ SUBMITTED ]: JSON.stringify( { v: 1, at: now } ) }, dispatched: [ REMOVE ] } );
		expect( container.querySelector( '.nx-exit-intent-form' ) ).toBeNull();
	} );

	it( 'theme one: the close button during the thank-you message writes no close key', async () => {
		reply( { success: true } );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' } );
		await submitForm( '.nx-exit-intent-form' );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( traces().session ).toEqual( {} );
		expect( traces().cookies ).toEqual( [] );
		expect( localStorage.getItem( SUBMITTED ) ).not.toBeNull();
	} );

	it( 'a filter theme (Pro theme seven) gets the rule without any change on its side', async () => {
		installProSeven();
		reply( { success: true } );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-seven' } );
		await submitForm( '.pro-t7-form' );
		wait( 2500 );
		expect( traces().cookies ).toEqual( [] );
		expect( traces().session ).toEqual( {} );
		expect( traces().dispatched ).toEqual( [ REMOVE ] );
		expect( localStorage.getItem( SUBMITTED ) ).not.toBeNull();
	} );

	it.each( Object.keys( signals ) )( 'Elementor: %s remembers the submit and writes no close key', async ( signal ) => {
		render( { submit_reappearance: 'never', ...ELEMENTOR } );
		act( () => {
			signals[ signal ]();
		} );
		await flush();
		expect( localStorage.getItem( SUBMITTED ) ).not.toBeNull();
		wait( 2000 );
		expect( traces().cookies ).toEqual( [] );
		expect( traces().session ).toEqual( {} );
		expect( traces().dispatched ).toEqual( [ REMOVE ] );
	} );

	it( "Elementor: Elementor Pro's jQuery submit_success remembers the submit", () => {
		const handlers = {};
		window.jQuery = jest.fn( () => ( {
			on: ( name, fn ) => {
				handlers[ name ] = fn;
			},
			off: jest.fn(),
		} ) );
		render( { submit_reappearance: 'never', ...ELEMENTOR } );
		act( () => {
			handlers.submit_success();
		} );
		expect( localStorage.getItem( SUBMITTED ) ).not.toBeNull();
		wait( 2000 );
		expect( traces().session ).toEqual( {} );
	} );

	it( 'Elementor: a NotificationX Form locked by an earlier submit closes as today and is not remembered', async () => {
		render( { submit_reappearance: 'never', ...ELEMENTOR } );
		act( () => {
			// nx-elementor-form.js lockForm(): "You have already submitted this form."
			const message = container.querySelector( '.nx-form-message' );
			message.classList.add( 'is-success', 'is-locked' );
			message.textContent = 'You have already submitted this form.';
		} );
		await flush();
		wait( 2000 );
		expect( localStorage.getItem( SUBMITTED ) ).toBeNull();
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
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
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' } );
		await submitForm( '.nx-exit-intent-form' );
		wait( 2500 );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );

	it( 'keeps the close keys in the builder preview', async () => {
		reply( { success: true } );
		render( { submit_reappearance: 'never', themes: 'exit_intent_custom_theme-one' }, { entry: { ...ENTRY, nxPreview: true } } );
		await submitForm( '.nx-exit-intent-form' );
		wait( 2500 );
		expect( traces() ).toEqual( closedAsToday( 'theme-one' ) );
	} );
} );
