import SCRIPT from '../fixtures/bar-reserve-script.json';
import { isHiddenBySubmit, submittedKey } from '../../../nxdev/notificationx/frontend/core/submitRule';

/**
 * The pre-paint script FrontEnd\BarSpace prints for a top bar with an "After a
 * form submit" rule (tests/test-bar-space.php checks the PHP prints exactly this
 * function). It must turn the space reservation off exactly when the frontend
 * will not show the bar: the close cookie is set, or isHiddenBySubmit() is true.
 * Otherwise a subscriber's page jumps up on every load.
 */

const DAY = 24 * 60 * 60 * 1000;
const NOW = Date.UTC( 2026, 10, 9, 12, 0, 0 );
const ENTRY = { storage_scope: 'a1b2c3d4e5' };
const KEY = submittedKey( 12, ENTRY );
const COOKIE = 'notificationx_12';

// eslint-disable-next-line no-new-func
const prePaint = new Function( 'n', 's', `${ SCRIPT.function }(n,s);` );

const reserveOff = () => document.documentElement.classList.contains( 'nx-bar-reserve-off' );

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
	jest.setSystemTime( NOW );
	localStorage.clear();
	clearCookies();
	document.documentElement.classList.remove( 'nx-bar-reserve-off' );
} );

afterEach( () => {
	jest.useRealTimers();
} );

// Every rule the PHP passes (d: 0 = never) and the stored records to try.
const RULES = [
	[ 'never', { submit_reappearance: 'never' }, 0 ],
	[ '1 day', { submit_reappearance: 'days', submit_hide_days: 1 }, 1 ],
	[ '30 days', { submit_reappearance: 'days', submit_hide_days: 30 }, 30 ],
	[ '365 days', { submit_reappearance: 'days', submit_hide_days: 365 }, 365 ],
];
const RECORDS = [
	[ 'nothing stored', null ],
	[ 'a submit just now', { v: 1, at: NOW } ],
	[ 'a submit 1 ms short of a day ago', { v: 1, at: NOW - DAY + 1 } ],
	[ 'a submit exactly a day ago', { v: 1, at: NOW - DAY } ],
	[ 'a submit 29 days ago', { v: 1, at: NOW - 29 * DAY } ],
	[ 'a submit 31 days ago', { v: 1, at: NOW - 31 * DAY } ],
	[ 'a submit 400 days ago', { v: 1, at: NOW - 400 * DAY } ],
	[ 'a submit 2 hours ahead (clock set back)', { v: 1, at: NOW + 2 * 60 * 60 * 1000 } ],
	[ 'a submit exactly a day ahead', { v: 1, at: NOW + DAY } ],
	[ 'a submit 2 days ahead', { v: 1, at: NOW + 2 * DAY } ],
	[ 'no submit time', { v: 1 } ],
	[ 'a text submit time', { v: 1, at: 'soon' } ],
	[ 'a negative submit time', { v: 1, at: -5 } ],
	[ 'a number', 42 ],
	[ 'not JSON', 'not json' ],
];

describe.each( RULES )( 'rule %s', ( label, settings, days ) => {
	it.each( RECORDS )( 'with %s, matches isHiddenBySubmit()', ( recordLabel, record ) => {
		const raw = null === record ? null : ( 'string' === typeof record ? record : JSON.stringify( record ) );
		if ( null !== raw ) {
			localStorage.setItem( KEY, raw );
		}
		prePaint( COOKIE, { k: KEY, d: days } );
		const off = reserveOff();
		// isHiddenBySubmit() may tidy the record, so it runs second.
		expect( off ).toBe( isHiddenBySubmit( { nx_id: 12, ...settings }, ENTRY, NOW ) );
	} );
} );

describe( 'the close cookie', () => {
	it( 'turns the reservation off, as today', () => {
		document.cookie = `${ COOKIE }=true; path=/`;
		prePaint( COOKIE, { k: KEY, d: 0 } );
		expect( reserveOff() ).toBe( true );
	} );

	it.each( [ 'false', '' ] )( 'keeps it with the value "%s", as today', ( value ) => {
		document.cookie = `${ COOKIE }=${ value }; path=/`;
		prePaint( COOKIE, { k: KEY, d: 0 } );
		expect( reserveOff() ).toBe( false );
	} );

	it( 'still counts when reading localStorage throws', () => {
		document.cookie = `${ COOKIE }=true; path=/`;
		jest.spyOn( window, 'localStorage', 'get' ).mockImplementation( () => {
			throw new DOMException( 'blocked', 'SecurityError' );
		} );
		prePaint( COOKIE, { k: KEY, d: 0 } );
		expect( reserveOff() ).toBe( true );
	} );

	it( 'keeps the reservation when nothing hides the bar and storage throws', () => {
		jest.spyOn( window, 'localStorage', 'get' ).mockImplementation( () => {
			throw new DOMException( 'blocked', 'SecurityError' );
		} );
		prePaint( COOKIE, { k: KEY, d: 0 } );
		expect( reserveOff() ).toBe( false );
	} );
} );
