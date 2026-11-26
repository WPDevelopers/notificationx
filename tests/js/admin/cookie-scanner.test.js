/**
 * Cookie Scanner polling (issue #224): one status request at a time, stops on
 * a failed scan, a 4xx reply, 3 bad replies in a row or the 10-minute limit,
 * re-enables Scan Now, and never adds a failed scan to the history. A
 * completed scan works as before.
 *
 * `fetch` is a Jest mock, so no request reaches WordPress or the scanner API.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';

jest.mock( 'quickbuilder', () => ( { useBuilderContext: () => mockBuilder } ) );
jest.mock( '../../../nxdev/notificationx/hooks', () => ( { useNotificationXContext: () => mockNx } ) );
jest.mock( '../../../nxdev/notificationx/core/ToasterMsg', () => ( {
	__esModule: true,
	default: { info: jest.fn(), error: jest.fn() },
} ) );
// The icons are plain SVGs (ScanHistory logs a React warning for `clip-path`); not under test here.
jest.mock( '../../../nxdev/notificationx/icons/ScanHistory', () => () => null );
jest.mock( '../../../nxdev/notificationx/icons/Close', () => () => null );
jest.mock( '../../../nxdev/notificationx/icons/DiscoveredCookies', () => () => null );
jest.mock( '../../../nxdev/notificationx/frontend/gdpr/utils/helper', () => ( {
	addCookiesAddedClass: jest.fn(),
	cookieCategoryPrefix: { necessary: [ 'wordpress_logged_in' ], analytics: [ '_ga' ] },
	formatDateTime: ( value ) => String( value ),
} ) );

// eslint-disable-next-line import/first
import nxToast from '../../../nxdev/notificationx/core/ToasterMsg';
// eslint-disable-next-line import/first
import CookieScanner, {
	classifyStatusResponse,
	SCAN_MAX_BAD_REPLIES,
	SCAN_POLL_INTERVAL_MS,
	SCAN_TIME_LIMIT_MS,
} from '../../../nxdev/notificationx/fields/CookieScanner';
// eslint-disable-next-line import/first
import ScannerHistory, { cleanScanHistory } from '../../../nxdev/notificationx/fields/ModalContent/ScannerHistory';

const START_URL = 'http://storefy.local/wp-json/notificationx/v1/scan';
const STATUS_URL = 'http://storefy.local/wp-json/notificationx/v1/scan/status';
const FAILED = 'Scan failed. Please try again.';
const TOO_LONG = 'The scan took too long. Please try again.';

let mockBuilder;
let mockNx;
let container;
let statusReplies;
let statusCalls;

const jsonReply = ( status, body ) => Promise.resolve( {
	ok: status >= 200 && status < 300,
	status,
	json: () => Promise.resolve( body ),
} );
const htmlReply = ( status ) => Promise.resolve( {
	ok: status >= 200 && status < 300,
	status,
	json: () => Promise.reject( new SyntaxError( 'Unexpected token <' ) ),
} );
const networkError = () => Promise.reject( new TypeError( 'Failed to fetch' ) );
const hanging = () => new Promise( () => {} );
const status = ( value, extra = {} ) => () => jsonReply( 200, { data: { status: value, ...extra } } );

const flush = async () => {
	await act( async () => {
		for ( let i = 0; i < 10; i++ ) {
			await Promise.resolve();
		}
	} );
};
const advance = async ( ms ) => {
	await act( async () => {
		jest.advanceTimersByTime( ms );
	} );
	await flush();
};
const poll = () => advance( SCAN_POLL_INTERVAL_MS );
// Moves the clock in poll-sized steps, so each reply is handled before the next timer.
const advanceInSteps = async ( total ) => {
	let left = total;
	while ( left > 0 ) {
		const step = Math.min( SCAN_POLL_INTERVAL_MS, left );
		await advance( step );
		left -= step;
	}
};

const scanButton = () => container.querySelector( '.scan-action-btns button' );
const toastMessages = () => [ ...nxToast.info.mock.calls, ...nxToast.error.mock.calls ].map( ( call ) => call[ 0 ] );
const savedHistory = () => mockBuilder.setFieldValue.mock.calls.filter( ( call ) => call[ 0 ] === 'scan_history' );

const startScan = async () => {
	await act( async () => {
		scanButton().click();
	} );
	await act( async () => {
		document.querySelector( '.wpsp-btn-scan-primary' ).click();
	} );
	await flush();
};

beforeEach( () => {
	jest.useFakeTimers();
	jest.clearAllMocks();
	statusReplies = [];
	statusCalls = [];
	mockBuilder = {
		values: { id: 7, scan_history: [] },
		scan_data: { nx_scan_count: 0, scans_used: '%1$s of %2$s free scans used' },
		is_pro_active: false,
		getFieldValue: jest.fn( () => [] ),
		setFieldValue: jest.fn(),
	};
	mockNx = {
		rest: { root: 'http://storefy.local/wp-json/', namespace: 'notificationx/v1', nonce: 'nonce' },
		assets: { admin: '/admin/' },
	};
	global.fetch = jest.fn( ( url, options ) => {
		if ( url === START_URL ) {
			return jsonReply( 200, { success: true, data: { scan_id: 'scan-1', status: 'pending' } } );
		}
		if ( url === STATUS_URL ) {
			statusCalls.push( options );
			const next = statusReplies.length > 1 ? statusReplies.shift() : statusReplies[ 0 ];
			return next ? next() : hanging();
		}
		return Promise.reject( new Error( 'Unexpected URL ' + url ) );
	} );
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	act( () => {
		ReactDOM.render( <CookieScanner />, container );
	} );
} );

afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	document.body.innerHTML = '';
	jest.useRealTimers();
	delete global.fetch;
} );

describe( 'classifyStatusResponse', () => {
	test.each( [
		[ 200, { data: { status: 'completed' } }, 'completed' ],
		[ 200, { data: { status: 'failed' } }, 'failed' ],
		[ 200, { data: { status: 'error' } }, 'failed' ],
		[ 400, { data: { status: 'failed' } }, 'failed' ],
		[ 403, { code: 'rest_forbidden' }, 'failed' ],
		[ 404, null, 'failed' ],
		[ 200, { data: { status: 'pending' } }, 'pending' ],
		[ 200, { data: { status: 'running' } }, 'pending' ],
		[ 200, { data: { status: 'queued' } }, 'pending' ],
		[ 200, { data: { data: { error: 'x' }, headers: [], status: 500 } }, 'bad' ],
		[ 200, { data: null }, 'bad' ],
		[ 200, { data: { status: '' } }, 'bad' ],
		[ 200, null, 'bad' ],
		[ 502, { data: { status: 'failed' } }, 'bad' ],
		[ 500, null, 'bad' ],
		[ 0, null, 'bad' ],
	] )( 'HTTP %s with %j is %s', ( httpStatus, body, expected ) => {
		expect( classifyStatusResponse( httpStatus, body ) ).toBe( expected );
	} );
} );

describe( 'CookieScanner polling', () => {
	test( 'a completed scan adds the cookies and one history entry, then polling stops', async () => {
		statusReplies = [
			status( 'pending' ),
			status( 'completed', {
				cookies: { a: { name: 'wordpress_logged_in_abc', domain: 'storefy.local', expires: -1 } },
				last_scan_date: '2026-11-26 10:00:00',
			} ),
		];
		await startScan();
		expect( scanButton().disabled ).toBe( true );

		await poll();
		await poll();

		expect( nxToast.info ).toHaveBeenCalledWith( 'Scan complete! Your results are now available.' );
		expect( nxToast.error ).not.toHaveBeenCalled();
		expect( scanButton().disabled ).toBe( false );
		expect( savedHistory() ).toHaveLength( 1 );
		expect( savedHistory()[ 0 ][ 1 ] ).toEqual( [ expect.objectContaining( { status: 'completed' } ) ] );
		expect( mockBuilder.setFieldValue ).toHaveBeenCalledWith( 'last_scan_date', '2026-11-26 10:00:00' );
		expect( mockBuilder.setFieldValue ).toHaveBeenCalledWith( 'necessary_cookie_lists', [
			expect.objectContaining( { cookies_id: 'wordpress_logged_in_abc', duration: 0 } ),
		] );

		await advance( 60000 );
		expect( statusCalls ).toHaveLength( 2 );
	} );

	test( 'sends the scan ID and waits for each reply before the next request', async () => {
		statusReplies = [ hanging ];
		await startScan();

		await advance( 60000 );

		expect( statusCalls ).toHaveLength( 1 );
		expect( JSON.parse( statusCalls[ 0 ].body ) ).toEqual( { scan_id: 'scan-1', nx_id: 7 } );
		expect( scanButton().disabled ).toBe( true );
	} );

	test( 'a failed status stops at once, re-enables Scan Now and adds nothing to the history', async () => {
		statusReplies = [ status( 'failed', { message: 'Raw API detail' } ) ];
		await startScan();

		await poll();

		expect( nxToast.error ).toHaveBeenCalledTimes( 1 );
		expect( nxToast.error ).toHaveBeenCalledWith( FAILED );
		expect( scanButton().disabled ).toBe( false );
		expect( savedHistory() ).toHaveLength( 0 );

		await advance( 60000 );
		expect( statusCalls ).toHaveLength( 1 );
	} );

	test.each( [ 400, 404 ] )( 'a %s reply stops at once', async ( code ) => {
		statusReplies = [ () => jsonReply( code, { data: { status: 'failed', message: 'Invalid scan ID' } } ) ];
		await startScan();

		await poll();

		expect( nxToast.error ).toHaveBeenCalledWith( FAILED );
		expect( scanButton().disabled ).toBe( false );
		await advance( 60000 );
		expect( statusCalls ).toHaveLength( 1 );
	} );

	test( `${ SCAN_MAX_BAD_REPLIES } bad replies in a row stop the scan, with no "undefined" message`, async () => {
		statusReplies = [
			networkError,
			() => htmlReply( 504 ),
			() => jsonReply( 200, { data: { data: { error: 'blocked' }, headers: [], status: 500 } } ),
		];
		await startScan();

		await poll();
		await poll();
		expect( nxToast.error ).not.toHaveBeenCalled();
		expect( scanButton().disabled ).toBe( true );

		await poll();

		expect( statusCalls ).toHaveLength( SCAN_MAX_BAD_REPLIES );
		expect( nxToast.error ).toHaveBeenCalledWith( FAILED );
		expect( scanButton().disabled ).toBe( false );
		expect( savedHistory() ).toHaveLength( 0 );
		expect( toastMessages().join( ' ' ) ).not.toMatch( /undefined/ );
	} );

	test( 'a good reply resets the bad-reply count', async () => {
		statusReplies = [ networkError, networkError, status( 'pending' ), networkError, networkError, hanging ];
		await startScan();

		for ( let i = 0; i < 6; i++ ) {
			await poll();
		}

		expect( statusCalls ).toHaveLength( 6 );
		expect( nxToast.error ).not.toHaveBeenCalled();
		expect( scanButton().disabled ).toBe( true );
	} );

	test( 'an unknown status keeps polling until the time limit, then stops', async () => {
		statusReplies = [ status( 'queued' ) ];
		await startScan();

		await advanceInSteps( SCAN_TIME_LIMIT_MS - 1000 );
		expect( nxToast.error ).not.toHaveBeenCalled();
		expect( statusCalls.length ).toBeGreaterThan( 100 );

		await advanceInSteps( 1000 );

		expect( nxToast.error ).toHaveBeenCalledTimes( 1 );
		expect( nxToast.error ).toHaveBeenCalledWith( TOO_LONG );
		expect( scanButton().disabled ).toBe( false );
		const calls = statusCalls.length;
		await advance( 60000 );
		expect( statusCalls ).toHaveLength( calls );
	} );

	test( 'the time limit aborts a request that is still in flight', async () => {
		statusReplies = [ hanging ];
		await startScan();
		await poll();
		expect( statusCalls[ 0 ].signal.aborted ).toBe( false );

		await advance( SCAN_TIME_LIMIT_MS );

		expect( statusCalls[ 0 ].signal.aborted ).toBe( true );
		expect( nxToast.error ).toHaveBeenCalledWith( TOO_LONG );
		expect( scanButton().disabled ).toBe( false );
	} );

	test( 'unmounting mid-request aborts it and sends nothing more', async () => {
		statusReplies = [ hanging ];
		await startScan();
		await poll();

		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		await advance( SCAN_TIME_LIMIT_MS );

		expect( statusCalls ).toHaveLength( 1 );
		expect( statusCalls[ 0 ].signal.aborted ).toBe( true );
		expect( nxToast.error ).not.toHaveBeenCalled();
	} );

	test( 'a start reply without a scan ID shows the fixed message and does not poll', async () => {
		global.fetch.mockImplementationOnce( () => jsonReply( 200, { status: 'Scan started.' } ) );
		await startScan();

		expect( nxToast.error ).toHaveBeenCalledWith( FAILED );
		expect( scanButton().disabled ).toBe( false );
		await advance( 60000 );
		expect( statusCalls ).toHaveLength( 0 );
	} );

	test( 'a second scan after a failed one polls again', async () => {
		statusReplies = [ status( 'failed' ) ];
		await startScan();
		await poll();
		expect( scanButton().disabled ).toBe( false );

		statusReplies = [ status( 'completed', { cookies: {}, last_scan_date: 'now' } ) ];
		await startScan();
		await poll();

		expect( statusCalls ).toHaveLength( 2 );
		expect( nxToast.info ).toHaveBeenCalledWith( 'Scan complete! Your results are now available.' );
	} );
} );

describe( 'scan history', () => {
	test( 'cleanScanHistory drops empty entries and anything that is not a list', () => {
		const entry = { status: 'completed' };
		expect( cleanScanHistory( [ null, entry, undefined ] ) ).toEqual( [ entry ] );
		expect( cleanScanHistory( undefined ) ).toEqual( [] );
		expect( cleanScanHistory( 'x' ) ).toEqual( [] );
		expect( cleanScanHistory( { 0: entry } ) ).toEqual( [] );
	} );

	test( 'the History popup renders a saved history that holds null entries', () => {
		const target = document.createElement( 'div' );
		act( () => {
			ReactDOM.render(
				<ScannerHistory
					setIsHistoryModalOpen={ () => {} }
					handleScannedCookieView={ () => {} }
					historyData={ [ null, { status: 'completed', last_scan_date: '2026-11-26 10:00:00', category_count: 1 } ] }
				/>,
				target
			);
		} );
		expect( target.querySelectorAll( 'tbody tr' ) ).toHaveLength( 1 );
		expect( target.textContent ).toContain( 'completed' );
		ReactDOM.unmountComponentAtNode( target );
	} );

	test( 'a saved history with null entries does not break the scanner', () => {
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		mockBuilder.values.scan_history = [ null ];
		mockBuilder.scan_data.nx_scan_count = 1;
		act( () => {
			ReactDOM.render( <CookieScanner />, container );
		} );
		expect( scanButton() ).not.toBeNull();
	} );
} );
