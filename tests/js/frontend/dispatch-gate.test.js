import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import useNotificationX from '../../../nxdev/notificationx/frontend/core/useNotificationX';

/**
 * The "After a form submit" gate in useNotificationX's dispatchNotification:
 * a popup, bar or Exit Intent whose submit still counts is not dispatched.
 * Drives the hook's real flow: the `notice` request (fetch mocked), the
 * per-type effects and their timers.
 */

const DAY = 24 * 60 * 60 * 1000;
const SCOPE = 'a1b2c3d4e5';
const KEY = `notificationx_submitted_${ SCOPE }_12`;

const ENTRY = {
	rest: { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
	storage_scope: SCOPE,
	gmt_offset: 0,
	popup: [ 12 ],
	pressbar: [ 12 ],
	exit_intent: [ 12 ],
};

const POSTS = {
	popup: { nx_id: 12, type: 'popup', source: 'popup_notification', themes: 'popup_notification_theme-five' },
	pressbar: { nx_id: 12, type: 'notification_bar', source: 'press_bar', themes: 'press_bar_theme-one', elementor_id: 87, initial_delay: 0 },
	exit_intent: { nx_id: 12, type: 'exit_intent', source: 'exit_intent_custom', themes: 'exit_intent_custom_theme-one', exit_intent_mobile_disable: false },
};

let ctx;
let container;
const Harness = ( { config } ) => {
	ctx = useNotificationX( { config } );
	return null;
};

beforeEach( () => {
	jest.useFakeTimers();
	localStorage.clear();
	sessionStorage.clear();
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

/**
 * Renders the hook for one notification of this kind and returns what it
 * dispatched once its timers (and, for Exit Intent, the exit) have run.
 */
const dispatched = async ( kind, settings = {}, entry = ENTRY ) => {
	const response = { settings: {}, [ kind ]: { 12: { post: { ...POSTS[ kind ], ...settings }, content: '' } } };
	global.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( response ) } ) );
	act( () => {
		ReactDOM.render( <Harness config={ entry } />, container );
	} );
	await flush();
	if ( 'exit_intent' === kind ) {
		act( () => {
			document.dispatchEvent( new MouseEvent( 'mouseleave', { clientY: 0 } ) );
		} );
	}
	act( () => {
		jest.advanceTimersByTime( 1500 );
	} );
	return ctx.state.notices;
};

const remember = ( at = Date.now(), key = KEY ) => localStorage.setItem( key, JSON.stringify( { v: 1, at } ) );

describe.each( [ 'popup', 'pressbar', 'exit_intent' ] )( '%s', ( kind ) => {
	it( 'is dispatched when nothing was submitted', async () => {
		expect( await dispatched( kind, { submit_reappearance: 'never' } ) ).toHaveLength( 1 );
	} );

	it( 'is not dispatched while a "never" submit counts', async () => {
		remember();
		expect( await dispatched( kind, { submit_reappearance: 'never' } ) ).toHaveLength( 0 );
	} );

	it( 'is not dispatched within the days of a "days" rule', async () => {
		remember( Date.now() - 2 * DAY );
		expect( await dispatched( kind, { submit_reappearance: 'days', submit_hide_days: 3 } ) ).toHaveLength( 0 );
	} );

	it( 'is dispatched again once the days have passed', async () => {
		remember( Date.now() - 4 * DAY );
		expect( await dispatched( kind, { submit_reappearance: 'days', submit_hide_days: 3 } ) ).toHaveLength( 1 );
	} );

	it( 'ignores a stored submit under "Same as closing"', async () => {
		remember();
		expect( await dispatched( kind, { submit_reappearance: 'same_as_close' } ) ).toHaveLength( 1 );
	} );

	it( 'ignores a stored submit for a notification saved before the setting', async () => {
		remember();
		expect( await dispatched( kind ) ).toHaveLength( 1 );
	} );

	it( 'ignores a submit stored for another site', async () => {
		remember( Date.now(), 'notificationx_submitted_ffffffffff_12' );
		expect( await dispatched( kind, { submit_reappearance: 'never' } ) ).toHaveLength( 1 );
	} );
} );

describe( 'builder preview', () => {
	it( 'still shows a popup the admin has submitted', async () => {
		remember();
		const preview = {
			...ENTRY,
			nxPreview: true,
			active: {},
			pressbar: {},
			gdpr: {},
			exit_intent: {},
			popup: { 12: { post: { ...POSTS.popup, submit_reappearance: 'never' }, content: '' } },
		};
		act( () => {
			ReactDOM.render( <Harness config={ preview } />, container );
		} );
		await flush();
		act( () => {
			jest.advanceTimersByTime( 1500 );
		} );
		expect( ctx.state.notices ).toHaveLength( 1 );
	} );
} );
