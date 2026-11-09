import {
	getSubmitRule,
	getSubmitDays,
	getStorageScope,
	submittedKey,
	rememberSubmit,
	isHiddenBySubmit,
} from '../../../nxdev/notificationx/frontend/core/submitRule';

/**
 * "After a form submit" storage and check logic (submitRule.ts).
 */

const DAY = 24 * 60 * 60 * 1000;
const NOW = Date.UTC( 2026, 10, 9, 12, 0, 0 );
const ENTRY = { storage_scope: 'a1b2c3d4e5', rest: { root: 'https://shop.example/wp-json/' } };
const KEY = 'notificationx_submitted_a1b2c3d4e5_12';

const settings = ( rule, days ) => ( { nx_id: 12, submit_reappearance: rule, submit_hide_days: days } );
const store = ( at, key = KEY ) => localStorage.setItem( key, JSON.stringify( { v: 1, at } ) );
const stored = ( key = KEY ) => localStorage.getItem( key );

beforeEach( () => {
	jest.restoreAllMocks();
	localStorage.clear();
} );

describe( 'getSubmitRule', () => {
	it.each( [ undefined, null, '', 'same_as_close', 'forever', 'DAYS', 42 ] )( 'treats %p as "Same as closing"', ( value ) => {
		expect( getSubmitRule( { submit_reappearance: value, submit_hide_days: 30 } ) ).toBe( 'same_as_close' );
	} );

	it( 'treats missing settings as "Same as closing"', () => {
		expect( getSubmitRule( undefined ) ).toBe( 'same_as_close' );
	} );

	it( 'reads "never" and "days"', () => {
		expect( getSubmitRule( settings( 'never' ) ) ).toBe( 'never' );
		expect( getSubmitRule( settings( 'days', 30 ) ) ).toBe( 'days' );
		expect( getSubmitRule( settings( 'days', '7' ) ) ).toBe( 'days' );
	} );

	it.each( [ undefined, 0, -3, 'abc', '' ] )( 'falls back to "Same as closing" for "days" with %p days', ( days ) => {
		expect( getSubmitRule( settings( 'days', days ) ) ).toBe( 'same_as_close' );
	} );
} );

describe( 'getSubmitDays', () => {
	it.each( [
		[ 30, 30 ], [ '7', 7 ], [ 7.9, 7 ], [ '7.5', 7 ], [ 1, 1 ], [ 365, 365 ], [ 999, 365 ], [ '999', 365 ],
		[ 0, 0 ], [ -3, 0 ], [ 'abc', 0 ], [ undefined, 0 ], [ null, 0 ], [ Infinity, 0 ],
	] )( '%p gives %p', ( value, expected ) => {
		expect( getSubmitDays( { submit_hide_days: value } ) ).toBe( expected );
	} );
} );

describe( 'getStorageScope and submittedKey', () => {
	it( 'uses the site scope from the localize data', () => {
		expect( getStorageScope( { storage_scope: 'A1B2C3D4E5' } ) ).toBe( 'a1b2c3d4e5' );
		expect( submittedKey( 12, ENTRY ) ).toBe( KEY );
	} );

	it.each( [
		[ 'https://Shop.Example/wp-json/', 'shop_example' ],
		[ 'https://example.com/sub/wp-json/', 'example_com_sub' ],
		[ 'https://example.com/sub/index.php?rest_route=/', 'example_com_sub' ],
		[ 'http://localhost:8080/wp-json/', 'localhost_8080' ],
	] )( 'falls back to the REST root for old Cross Domain snippets: %s', ( root, scope ) => {
		expect( getStorageScope( { rest: { root } } ) ).toBe( scope );
	} );

	it( 'ignores a scope that is not a string', () => {
		expect( getStorageScope( { storage_scope: 123, rest: { root: 'https://shop.example/wp-json/' } } ) ).toBe( 'shop_example' );
	} );

	it( 'has no scope without either', () => {
		expect( getStorageScope( {} ) ).toBe( '' );
		expect( getStorageScope( { rest: { root: 'not a url' } } ) ).toBe( '' );
		expect( getStorageScope( undefined ) ).toBe( '' );
		expect( submittedKey( 12, undefined ) ).toBe( 'notificationx_submitted_12' );
	} );

	it( 'gives no key without a notification ID', () => {
		expect( submittedKey( undefined, ENTRY ) ).toBe( '' );
		expect( submittedKey( '', ENTRY ) ).toBe( '' );
	} );
} );

describe( 'rememberSubmit', () => {
	it( 'writes nothing for "Same as closing", and leaves the close path to the caller', () => {
		const setItem = jest.spyOn( Storage.prototype, 'setItem' );
		expect( rememberSubmit( settings( 'same_as_close', 30 ), ENTRY, NOW ) ).toBe( false );
		expect( rememberSubmit( { nx_id: 12 }, ENTRY, NOW ) ).toBe( false );
		expect( setItem ).not.toHaveBeenCalled();
		expect( localStorage.length ).toBe( 0 );
	} );

	it.each( [ [ 'days', 30 ], [ 'never', undefined ] ] )( 'stores the submit time for "%s"', ( rule, days ) => {
		expect( rememberSubmit( settings( rule, days ), ENTRY, NOW ) ).toBe( true );
		expect( JSON.parse( stored() ) ).toEqual( { v: 1, at: NOW } );
		expect( localStorage.length ).toBe( 1 );
	} );

	it( 'writes nothing in the builder preview', () => {
		expect( rememberSubmit( settings( 'never' ), { ...ENTRY, nxPreview: true }, NOW ) ).toBe( false );
		expect( localStorage.length ).toBe( 0 );
	} );

	it( 'reports a full quota, so the caller falls back to the close keys', () => {
		jest.spyOn( Storage.prototype, 'setItem' ).mockImplementation( () => {
			throw new DOMException( 'full', 'QuotaExceededError' );
		} );
		expect( rememberSubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'reports blocked storage (reading window.localStorage throws)', () => {
		jest.spyOn( window, 'localStorage', 'get' ).mockImplementation( () => {
			throw new DOMException( 'blocked', 'SecurityError' );
		} );
		expect( rememberSubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'writes nothing without a notification ID', () => {
		expect( rememberSubmit( { submit_reappearance: 'never' }, ENTRY, NOW ) ).toBe( false );
		expect( localStorage.length ).toBe( 0 );
	} );
} );

describe( 'isHiddenBySubmit', () => {
	it( 'is never hidden without a stored submit', () => {
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'does not even read storage for "Same as closing"', () => {
		store( NOW );
		const getItem = jest.spyOn( Storage.prototype, 'getItem' );
		expect( isHiddenBySubmit( settings( 'same_as_close' ), ENTRY, NOW ) ).toBe( false );
		expect( isHiddenBySubmit( {}, ENTRY, NOW ) ).toBe( false );
		expect( getItem ).not.toHaveBeenCalled();
		expect( stored() ).not.toBeNull();
	} );

	it( 'hides for good under "never"', () => {
		store( NOW - 5000 * DAY );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( true );
	} );

	it( 'hides for exactly N days under "days"', () => {
		store( NOW );
		expect( isHiddenBySubmit( settings( 'days', 1 ), ENTRY, NOW + DAY - 1 ) ).toBe( true );
		expect( stored() ).not.toBeNull();
		expect( isHiddenBySubmit( settings( 'days', 1 ), ENTRY, NOW + DAY ) ).toBe( false );
		expect( stored() ).toBeNull();
	} );

	it( 'applies a later change of the rule to an earlier submit', () => {
		store( NOW - 10 * DAY );
		expect( isHiddenBySubmit( settings( 'days', 30 ), ENTRY, NOW ) ).toBe( true );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( true );
		expect( isHiddenBySubmit( settings( 'same_as_close' ), ENTRY, NOW ) ).toBe( false );
		expect( isHiddenBySubmit( settings( 'days', 7 ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'caps the days at 365', () => {
		store( NOW - 366 * DAY );
		expect( isHiddenBySubmit( settings( 'days', 999 ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'moves a submit time up to a day ahead (clock set back) to now', () => {
		store( NOW + 2 * 60 * 60 * 1000 );
		expect( isHiddenBySubmit( settings( 'days', 1 ), ENTRY, NOW ) ).toBe( true );
		expect( JSON.parse( stored() ).at ).toBe( NOW );
		expect( isHiddenBySubmit( settings( 'days', 1 ), ENTRY, NOW + DAY ) ).toBe( false );
	} );

	it( 'drops a submit time more than a day ahead', () => {
		store( NOW + 2 * DAY );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
		expect( stored() ).toBeNull();
	} );

	it.each( [ 'not json', '{"v":1}', '{"at":"soon"}', '{"at":-5}', 'null', '42' ] )( 'drops an unreadable record: %s', ( raw ) => {
		localStorage.setItem( KEY, raw );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
		expect( stored() ).toBeNull();
	} );

	it( 'hides nothing when reading storage throws', () => {
		store( NOW );
		jest.spyOn( Storage.prototype, 'getItem' ).mockImplementation( () => {
			throw new DOMException( 'blocked', 'SecurityError' );
		} );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( false );
	} );

	it( 'ignores the key in the builder preview', () => {
		store( NOW );
		expect( isHiddenBySubmit( settings( 'never' ), { ...ENTRY, nxPreview: true }, NOW ) ).toBe( false );
	} );

	it( 'keeps two sites with the same notification ID apart', () => {
		const other = { storage_scope: 'ffffffffff' };
		expect( rememberSubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( true );
		expect( isHiddenBySubmit( settings( 'never' ), other, NOW ) ).toBe( false );
		expect( isHiddenBySubmit( settings( 'never' ), ENTRY, NOW ) ).toBe( true );
	} );

	it( 'keeps an old snippet (REST root only) apart from another site', () => {
		const siteA = { rest: { root: 'https://a.example/wp-json/' } };
		const siteB = { rest: { root: 'https://b.example/wp-json/' } };
		expect( rememberSubmit( settings( 'never' ), siteA, NOW ) ).toBe( true );
		expect( isHiddenBySubmit( settings( 'never' ), siteB, NOW ) ).toBe( false );
		expect( isHiddenBySubmit( settings( 'never' ), siteA, NOW ) ).toBe( true );
	} );
} );
