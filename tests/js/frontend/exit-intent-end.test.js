import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { isExitIntentPastEnd, msUntilExitIntentEnd } from '../../../nxdev/notificationx/frontend/core/exitIntentEnd';

/**
 * Exit Intent "Stop Showing After End Date" (free issue #211).
 * PHP sends `exit_intent_end_ts` (UTC, ms) and `exit_intent_hide_after_end`.
 */

const NOW = 1800000000000;

describe( 'exitIntentEnd helpers', () => {
	it( 'is past the end only when the toggle is on and the end time has passed', () => {
		expect( isExitIntentPastEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW - 1 }, NOW ) ).toBe( true );
		expect( isExitIntentPastEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW }, NOW ) ).toBe( true );
		expect( isExitIntentPastEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW + 1 }, NOW ) ).toBe( false );
		expect( isExitIntentPastEnd( { exit_intent_hide_after_end: false, exit_intent_end_ts: NOW - 1 }, NOW ) ).toBe( false );
	} );

	it( 'never stops without a server end time (admin preview, older data)', () => {
		expect( msUntilExitIntentEnd( { exit_intent_hide_after_end: true }, NOW ) ).toBeNull();
		expect( msUntilExitIntentEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: 'x' }, NOW ) ).toBeNull();
		expect( msUntilExitIntentEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: 0 }, NOW ) ).toBeNull();
		expect( msUntilExitIntentEnd( undefined, NOW ) ).toBeNull();
	} );

	it( 'returns the time left', () => {
		expect( msUntilExitIntentEnd( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW + 5000 }, NOW ) ).toBe( 5000 );
	} );
} );

describe( 'ExitIntentPopup closes at the end time', () => {
	const frontendContext = {
		rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
		state: { is_pro: false },
	};
	let container;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( NOW );
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		sessionStorage.clear();
	} );
	afterEach( () => {
		ReactDOM.unmountComponentAtNode( container );
		container.remove();
		jest.useRealTimers();
	} );

	const render = ( extra, dispatch ) => {
		const nxExitIntent = {
			id: 'abc',
			config: {
				nx_id: 7,
				source: 'exit_intent_custom',
				themes: 'exit_intent_custom_theme-three',
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
	};

	it( 'closes without writing a dismissal when the end time arrives', () => {
		const dispatch = jest.fn();
		render( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW + 3000 }, dispatch );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).not.toBeNull();

		act( () => { jest.advanceTimersByTime( 2999 ); } );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).not.toBeNull();

		act( () => { jest.advanceTimersByTime( 1 ); } );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).toBeNull();
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 'abc' } );
		expect( sessionStorage.length ).toBe( 0 );
	} );

	it( 'stays open when the toggle is off', () => {
		render( { exit_intent_hide_after_end: false, exit_intent_end_ts: NOW + 3000 }, jest.fn() );
		act( () => { jest.advanceTimersByTime( 60000 ); } );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).not.toBeNull();
	} );

	it( 'handles an end time beyond the setTimeout limit', () => {
		const days30 = 30 * 24 * 3600 * 1000;
		render( { exit_intent_hide_after_end: true, exit_intent_end_ts: NOW + days30 }, jest.fn() );
		act( () => { jest.advanceTimersByTime( 1000 ); } );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).not.toBeNull();
		act( () => { jest.advanceTimersByTime( days30 ); } );
		expect( container.querySelector( '.nx-exit-intent-popup' ) ).toBeNull();
	} );
} );
