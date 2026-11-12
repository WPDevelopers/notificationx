import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import cookie from 'react-cookies';
import Pressbar from '../../../nxdev/notificationx/frontend/core/Pressbar';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

/**
 * A form submit inside a Notification Bar built with Elementor and the
 * "After a form submit" rule. Today a submit there stores nothing and the bar
 * stays; with the rule the submit is remembered, and the bar still stays on
 * screen (it is hidden from the next page load on).
 */

const SCOPE = 'a1b2c3d4e5';
const SUBMITTED = `notificationx_submitted_${ SCOPE }_12`;
const ENTRY = {
	rest: { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	storage_scope: SCOPE,
	assets: { free: '', pro: '', common: '' },
	state: { is_pro: false },
};
const CONTENT = '<div class="elementor"><form class="nx-form" data-nx-id="12"><div class="nx-form-message"></div></form></div>';

let container;
let dispatch;

beforeEach( () => {
	jest.restoreAllMocks();
	jest.useFakeTimers();
	localStorage.clear();
	sessionStorage.clear();
	Object.keys( cookie.loadAll() ).forEach( ( name ) => cookie.remove( name, { path: '/' } ) );
	dispatch = jest.fn();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );

afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	document.querySelectorAll( '[id^="nx-bar-"]' ).forEach( ( el ) => el.remove() );
	jest.useRealTimers();
} );

const flush = async () => {
	for ( let i = 0; i < 5; i++ ) {
		await act( async () => {
			await Promise.resolve();
		} );
	}
};

const render = ( settings, content = CONTENT ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ ENTRY }>
				<Pressbar
					position="top"
					nxBar={ { id: 'bar-uuid', config: { nx_id: 12, source: 'press_bar', type: 'notification_bar', themes: 'press_bar_theme-one', elementor_id: 87, ...settings }, data: content } }
					dispatch={ dispatch }
				/>
			</NotificationProvider>,
			container
		);
	} );
};

const succeed = async () => {
	act( () => {
		const message = document.querySelector( '#nx-bar-12 .nx-form-message' );
		message.classList.add( 'is-success' );
		message.textContent = 'Thanks';
	} );
	await flush();
	act( () => {
		jest.advanceTimersByTime( 3000 );
	} );
};

const traces = () => ( {
	cookies: Object.keys( cookie.loadAll() ),
	session: Object.keys( sessionStorage ).filter( ( k ) => k.indexOf( 'nx-bar-height' ) !== 0 ),
	local: { ...localStorage },
	dispatched: dispatch.mock.calls.length,
} );

describe( "today's behaviour", () => {
	it.each( [ [ 'saved before the setting', {} ], [ 'Same as closing', { submit_reappearance: 'same_as_close' } ] ] )(
		'%s: a submit inside the bar stores nothing and the bar stays',
		async ( label, rule ) => {
			render( { ...rule, bar_reappearance: 'dont_show_welcomebar' } );
			expect( document.querySelector( '#nx-bar-12 .nx-form' ) ).not.toBeNull();
			await succeed();
			expect( traces() ).toEqual( { cookies: [], session: [], local: {}, dispatched: 0 } );
			expect( document.querySelector( '#nx-bar-12' ) ).not.toBeNull();
		}
	);
} );

describe( 'the submit rule', () => {
	it.each( [ [ 'never', {} ], [ 'days', { submit_hide_days: 30 } ] ] )( '"%s": remembers the submit; the bar stays', async ( rule, extra ) => {
		const now = Date.now();
		render( { submit_reappearance: rule, ...extra } );
		await succeed();
		expect( JSON.parse( localStorage.getItem( SUBMITTED ) ) ).toEqual( { v: 1, at: now } );
		expect( traces().cookies ).toEqual( [] );
		expect( traces().dispatched ).toBe( 0 );
		expect( document.querySelector( '#nx-bar-12' ) ).not.toBeNull();
	} );

	it( 'remembers a submit only once', async () => {
		const setItem = jest.spyOn( Storage.prototype, 'setItem' );
		render( { submit_reappearance: 'never' } );
		await succeed();
		act( () => {
			document.querySelector( '#nx-bar-12 .nx-form-message' ).textContent = 'Thanks again';
		} );
		await flush();
		expect( setItem.mock.calls.filter( ( [ key ] ) => key === SUBMITTED ) ).toHaveLength( 1 );
	} );

	it( 'does not remember a NotificationX Form locked by an earlier submit elsewhere', async () => {
		render( { submit_reappearance: 'never' } );
		act( () => {
			// nx-elementor-form.js lockForm(): the form was sent in another notification.
			const message = document.querySelector( '#nx-bar-12 .nx-form-message' );
			message.classList.add( 'is-success', 'is-locked' );
			message.textContent = 'You have already submitted this form.';
		} );
		await flush();
		expect( localStorage.getItem( SUBMITTED ) ).toBeNull();
		expect( document.querySelector( '#nx-bar-12' ) ).not.toBeNull();
	} );

	it( 'does not watch a bar that is not built with Elementor', async () => {
		render( { submit_reappearance: 'never', elementor_id: false }, '<p>Sale</p><div class="nx-form-message"></div>' );
		act( () => {
			const message = document.querySelector( '#nx-bar-12 .nx-form-message' );
			if ( message ) {
				message.classList.add( 'is-success' );
				message.textContent = 'Thanks';
			}
		} );
		await flush();
		expect( localStorage.getItem( SUBMITTED ) ).toBeNull();
	} );
} );
