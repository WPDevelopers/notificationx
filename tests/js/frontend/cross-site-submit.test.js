import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import useNotificationX from '../../../nxdev/notificationx/frontend/core/useNotificationX';
import Popup from '../../../nxdev/notificationx/frontend/core/Popup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

/**
 * Cross Domain Notice embeds and the "After a form submit" rule (decision D4:
 * the rule applies there too).
 *
 * An embed is a notificationXArr entry from another site (`cross: true`, that
 * site's REST root). Snippets copied after this change carry the source site's
 * `storage_scope`; older ones don't and fall back to the REST root. Either way
 * a submit is remembered for its own source site only, so two embeds on one
 * page whose notifications share an ID never hide each other.
 */

const SITE_A_NEW = {
	cross: true,
	storage_scope: 'aaaaaaaaaa',
	rest: { root: 'https://site-a.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	gmt_offset: 0,
	popup: [ 12 ],
	state: { is_pro: false },
};
const SITE_B_OLD = {
	cross: true,
	rest: { root: 'https://site-b.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	gmt_offset: 0,
	popup: [ 12 ],
	state: { is_pro: false },
};

const POPUP = {
	nx_id: 12,
	type: 'popup',
	source: 'popup_notification',
	themes: 'popup_notification_theme-five',
	popup_button_text: 'Subscribe',
	delay_before: 0,
	submit_reappearance: 'never',
};

let container;

beforeEach( () => {
	jest.useFakeTimers();
	localStorage.clear();
	sessionStorage.clear();
	window.notificationXArr = [ SITE_A_NEW, SITE_B_OLD ];
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

const submitPopupOf = async ( entry ) => {
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( { success: true } ) } ) );
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ entry }>
				<Popup position="center" nxPopup={ { id: 'p', config: POPUP, data: {} } } dispatch={ () => {} } />
			</NotificationProvider>,
			container
		);
	} );
	act( () => {
		jest.advanceTimersByTime( 0 );
	} );
	act( () => {
		container.querySelector( 'form' ).dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
	} );
	await flush();
	act( () => {
		jest.advanceTimersByTime( 1500 );
	} );
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
};

// What one embed dispatches on a page load.
const dispatchedFor = async ( entry ) => {
	let ctx;
	const Harness = () => {
		ctx = useNotificationX( { config: entry } );
		return null;
	};
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( { settings: {}, popup: { 12: { post: POPUP, content: '' } } } ) } ) );
	act( () => {
		ReactDOM.render( <Harness />, container );
	} );
	await flush();
	act( () => {
		jest.advanceTimersByTime( 1500 );
	} );
	const count = ctx.state.notices.length;
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	return count;
};

it( 'a new snippet remembers the submit under its source site\'s scope, with no close key', async () => {
	await submitPopupOf( SITE_A_NEW );
	expect( Object.keys( localStorage ) ).toEqual( [ 'notificationx_submitted_aaaaaaaaaa_12' ] );
	expect( Object.keys( sessionStorage ) ).toEqual( [] );
} );

it( 'an old snippet (no storage_scope) remembers it under its REST root', async () => {
	await submitPopupOf( SITE_B_OLD );
	expect( Object.keys( localStorage ) ).toEqual( [ 'notificationx_submitted_site_b_example_12' ] );
	expect( Object.keys( sessionStorage ) ).toEqual( [] );
} );

it( 'two embeds with the same notification ID on one page keep apart', async () => {
	expect( await dispatchedFor( SITE_A_NEW ) ).toBe( 1 );
	expect( await dispatchedFor( SITE_B_OLD ) ).toBe( 1 );

	await submitPopupOf( SITE_A_NEW );

	expect( await dispatchedFor( SITE_A_NEW ) ).toBe( 0 );
	expect( await dispatchedFor( SITE_B_OLD ) ).toBe( 1 );

	await submitPopupOf( SITE_B_OLD );
	expect( await dispatchedFor( SITE_B_OLD ) ).toBe( 0 );
} );
