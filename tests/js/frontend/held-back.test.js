import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import useNotificationX from '../../../nxdev/notificationx/frontend/core/useNotificationX';
import { isHeldBack } from '../../../nxdev/notificationx/frontend/core/heldBack';
import { installHooks } from '../helpers';

/**
 * A notice an add-on holds back (Pro: the live visitor count, until it is
 * known) is not added to the list for that cycle. Before, each hidden cycle
 * added an undrawn copy that nothing removed, and all of them showed at once
 * when the count arrived. Drives the hook's real loop (`notice` request
 * mocked, fake timers).
 */

const ENTRY = {
	rest: { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	gmt_offset: 0,
	active: [ 28, 15 ],
};

// Each cycle: display_for + delay_between = 15 s, the first after delay_before 8 s.
const TIMING = { delay_before: 8, display_for: 7, delay_between: 8, loop: true };

const live = {
	post: { nx_id: 28, type: 'page_analytics', source: 'live_visitors', themes: 'page_analytics_pa-theme-three', position: 'bottom_left', ...TIMING },
	entries: [ { nx_id: 28, timestamp: 1795078453, nx_awaiting_data: true } ],
};
const sales = {
	post: { nx_id: 15, type: 'conversions', source: 'woocommerce', themes: 'conversions_theme-one', position: 'bottom_left', ...TIMING },
	entries: [
		{ nx_id: 15, timestamp: 1795078000, name: 'Ann' },
		{ nx_id: 15, timestamp: 1795077000, name: 'Bo' },
	],
};

let ctx;
let container;
let hooks;
let count;
const Harness = ( { config } ) => {
	ctx = useNotificationX( { config } );
	return null;
};

beforeEach( () => {
	jest.useFakeTimers();
	sessionStorage.clear();
	localStorage.clear();
	hooks = installHooks();
	count = null;
	// What Pro's live-visitors script does: null until the count is known.
	hooks.addFilter( 'nx_frontend_entry', 'test/live', ( data, { settings } ) => {
		if ( settings?.source !== 'live_visitors' ) {
			return data;
		}
		return null === count ? null : { ...data, views: count, nx_awaiting_data: false };
	} );
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );

afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	delete window.wp;
	delete global.fetch;
	jest.useRealTimers();
} );

const flush = async () => {
	for ( let i = 0; i < 10; i++ ) {
		await act( async () => {
			await Promise.resolve();
		} );
	}
};

const start = async ( active ) => {
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( { settings: {}, active } ) } ) );
	act( () => {
		ReactDOM.render( <Harness config={ ENTRY } />, container );
	} );
	await flush();
};

const advance = ( seconds ) => act( () => {
	jest.advanceTimersByTime( seconds * 1000 );
} );

const noticesOf = ( id ) => ctx.state.notices.filter( ( n ) => Number( n.config?.nx_id ) === id );

describe( 'a held-back live notice', () => {
	test( 'hidden cycles add nothing, and it shows exactly once when the count arrives', async () => {
		await start( { 28: live } );
		// About a minute with the count unknown: four cycles (8, 23, 38, 53 s).
		advance( 60 );
		expect( noticesOf( 28 ) ).toHaveLength( 0 );

		count = 2;
		// The next cycle (68 s) shows it, once.
		advance( 9 );
		expect( noticesOf( 28 ) ).toHaveLength( 1 );
		expect( noticesOf( 28 )[ 0 ].data.nx_awaiting_data ).toBe( true );
		expect( isHeldBack( noticesOf( 28 )[ 0 ].data, noticesOf( 28 )[ 0 ].config ) ).toBe( false );
	} );

	test( 'the loop keeps running while it is held back', async () => {
		await start( { 28: live } );
		advance( 60 );
		count = 3;
		advance( 15 );
		count = null;
		advance( 15 );
		// One cycle while known, none while held back again.
		expect( noticesOf( 28 ) ).toHaveLength( 1 );
	} );
} );

describe( 'other notifications', () => {
	test( 'a sales notification loops as before, next to a held-back live one', async () => {
		await start( { 28: live, 15: sales } );
		advance( 8 );
		expect( noticesOf( 15 ) ).toHaveLength( 1 );
		expect( noticesOf( 15 )[ 0 ].data.name ).toBe( 'Ann' );
		advance( 15 );
		expect( noticesOf( 15 ) ).toHaveLength( 2 );
		expect( noticesOf( 15 )[ 1 ].data.name ).toBe( 'Bo' );
		expect( noticesOf( 28 ) ).toHaveLength( 0 );
	} );

	test( 'without any add-on filter nothing changes for plain entries', async () => {
		delete window.wp;
		await start( { 15: sales } );
		advance( 8 + 15 + 15 );
		expect( noticesOf( 15 ) ).toHaveLength( 3 );
	} );
} );

describe( 'isHeldBack', () => {
	test( 'held back while a filter returns null or the entry is still awaiting data', () => {
		expect( isHeldBack( { nx_awaiting_data: true }, { source: 'live_visitors' } ) ).toBe( true );
		count = 4;
		expect( isHeldBack( { nx_awaiting_data: true }, { source: 'live_visitors' } ) ).toBe( false );
		delete window.wp;
		expect( isHeldBack( { nx_awaiting_data: true }, {} ) ).toBe( true );
		expect( isHeldBack( { name: 'Ann' }, {} ) ).toBe( false );
		// Content of a bar, popup or GDPR is never held back.
		expect( isHeldBack( null, {} ) ).toBe( false );
		expect( isHeldBack( 'text', {} ) ).toBe( false );
		expect( isHeldBack( '', {} ) ).toBe( false );
		installHooks().addFilter( 'nx_frontend_entry', 'test/all', () => null );
		expect( isHeldBack( '<p>bar</p>', { type: 'notification_bar' } ) ).toBe( false );
		expect( isHeldBack( null, { type: 'popup' } ) ).toBe( false );
	} );
} );
