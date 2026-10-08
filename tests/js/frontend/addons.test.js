import {
	ADDON_SCRIPT_TIMEOUT,
	createHooksRegistry,
	ensureHooksRegistry,
	hasHooksRegistry,
	loadAddonScripts,
	needsAddonScripts,
	resetAddonState,
} from '../../../nxdev/notificationx/frontend/core/addons';
import { nxApplyFilters } from '../../../nxdev/notificationx/frontend/core/hooks';
import { installHooks } from '../helpers';

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
const scriptTag = ( handle ) => document.getElementById( `${ handle }-js` );
const fire = ( handle, type = 'load' ) => scriptTag( handle ).dispatchEvent( new Event( type ) );

afterEach( () => {
	resetAddonState();
	document.head.querySelectorAll( 'script' ).forEach( ( el ) => el.remove() );
	delete window.nxTestData;
} );

describe( 'createHooksRegistry', () => {
	it( 'runs filters by priority, then in the order they were added', () => {
		const hooks = createHooksRegistry();
		hooks.addFilter( 'h', 'a', ( v ) => v + 'a' );
		hooks.addFilter( 'h', 'b', ( v ) => v + 'b', 5 );
		hooks.addFilter( 'h', 'c', ( v ) => v + 'c' );
		expect( hooks.applyFilters( 'h', '' ) ).toBe( 'bac' );
	} );

	it( 'passes extra arguments and returns the value when no filter is set', () => {
		const hooks = createHooksRegistry();
		expect( hooks.applyFilters( 'none', 1 ) ).toBe( 1 );
		hooks.addFilter( 'h', 'a', ( v, x, y ) => v + x + y );
		expect( hooks.applyFilters( 'h', 1, 2, 3 ) ).toBe( 6 );
	} );

	it( 'removes and reports filters by namespace', () => {
		const hooks = createHooksRegistry();
		hooks.addFilter( 'h', 'a', ( v ) => v + 1 );
		expect( hooks.hasFilter( 'h' ) ).toBe( true );
		expect( hooks.hasFilter( 'h', 'b' ) ).toBe( false );
		expect( hooks.removeFilter( 'h', 'a' ) ).toBe( 1 );
		expect( hooks.hasFilter( 'h' ) ).toBe( false );
		expect( hooks.applyFilters( 'h', 1 ) ).toBe( 1 );
	} );

	it( 'runs actions', () => {
		const hooks = createHooksRegistry();
		const action = jest.fn();
		hooks.addAction( 'a', 'ns', action );
		hooks.doAction( 'a', 1, 2 );
		expect( action ).toHaveBeenCalledWith( 1, 2 );
	} );
} );

describe( 'ensureHooksRegistry', () => {
	it( 'creates window.wp.hooks when the page has none, and nxApplyFilters uses it', () => {
		expect( hasHooksRegistry() ).toBe( false );
		ensureHooksRegistry().addFilter( 'nx_frontend_template', 'test', () => [ 'claimed' ] );
		expect( hasHooksRegistry() ).toBe( true );
		expect( nxApplyFilters( 'nx_frontend_template', undefined ) ).toEqual( [ 'claimed' ] );
	} );

	it( 'keeps an existing registry, so filters already on it stay', () => {
		const existing = installHooks();
		existing.addFilter( 'h', 'a', () => 'kept' );
		expect( ensureHooksRegistry() ).toBe( existing );
		expect( nxApplyFilters( 'h', 'default' ) ).toBe( 'kept' );
	} );
} );

describe( 'loadAddonScripts', () => {
	it( 'does nothing for an empty or invalid list', async () => {
		await loadAddonScripts( undefined );
		await loadAddonScripts( [ { handle: 'x', src: 'javascript:alert(1)' }, { src: 'https://a.test/a.js' } ] );
		expect( document.head.querySelector( 'script' ) ).toBeNull();
		expect( hasHooksRegistry() ).toBe( false );
	} );

	it( 'creates the registry, sets the data object and loads the scripts in order', async () => {
		const done = jest.fn();
		loadAddonScripts( [
			{ handle: 'first', src: 'https://a.test/first.js', data: { name: 'nxTestData', value: { a: 1 } } },
			{ handle: 'second', src: 'https://a.test/second.js', data: null },
		] ).then( done );
		await flush();

		expect( hasHooksRegistry() ).toBe( true );
		expect( window.nxTestData ).toEqual( { a: 1 } );
		expect( scriptTag( 'first' ).src ).toBe( 'https://a.test/first.js' );
		expect( scriptTag( 'second' ) ).toBeNull();

		fire( 'first' );
		await flush();
		expect( scriptTag( 'second' ) ).not.toBeNull();
		expect( done ).not.toHaveBeenCalled();

		fire( 'second' );
		await flush();
		expect( done ).toHaveBeenCalled();
	} );

	it( 'continues after a script fails to load', async () => {
		const error = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		const done = jest.fn();
		loadAddonScripts( [ { handle: 'broken', src: 'https://a.test/broken.js' } ] ).then( done );
		await flush();
		fire( 'broken', 'error' );
		await flush();
		expect( done ).toHaveBeenCalled();
		expect( error ).toHaveBeenCalled();
		error.mockRestore();
	} );

	it( 'skips a script that is already on the page and keeps an existing data object', async () => {
		const tag = document.createElement( 'script' );
		tag.id = 'present-js';
		document.head.appendChild( tag );
		window.nxTestData = 'existing';

		await loadAddonScripts( [
			{ handle: 'present', src: 'https://a.test/present.js', data: { name: 'nxTestData', value: 'new' } },
		] );
		expect( document.head.querySelectorAll( 'script' ) ).toHaveLength( 1 );
		expect( window.nxTestData ).toBe( 'existing' );
	} );

	it( 'gives up on a script that never loads', async () => {
		jest.useFakeTimers();
		const done = jest.fn();
		loadAddonScripts( [ { handle: 'slow', src: 'https://a.test/slow.js' } ] ).then( done );
		// The first script is added after one microtask.
		await Promise.resolve();
		expect( scriptTag( 'slow' ) ).not.toBeNull();
		jest.advanceTimersByTime( ADDON_SCRIPT_TIMEOUT );
		jest.useRealTimers();
		await flush();
		expect( done ).toHaveBeenCalled();
	} );

	it( 'makes a second config wait for a script the first one started', async () => {
		const first = jest.fn();
		const second = jest.fn();
		loadAddonScripts( [ { handle: 'shared', src: 'https://a.test/shared.js' } ] ).then( first );
		await flush();
		// The second config's response has no list (or the same list).
		loadAddonScripts( undefined ).then( second );
		loadAddonScripts( [ { handle: 'shared', src: 'https://a.test/shared.js' } ] ).then( second );
		await flush();
		expect( document.head.querySelectorAll( 'script' ) ).toHaveLength( 1 );
		expect( first ).not.toHaveBeenCalled();
		expect( second ).not.toHaveBeenCalled();

		fire( 'shared' );
		await flush();
		expect( first ).toHaveBeenCalled();
		expect( second ).toHaveBeenCalledTimes( 2 );
	} );
} );

describe( 'needsAddonScripts', () => {
	it( 'is true without a registry, and stays true once the runtime created one', () => {
		expect( needsAddonScripts() ).toBe( true );
		ensureHooksRegistry();
		expect( hasHooksRegistry() ).toBe( true );
		expect( needsAddonScripts() ).toBe( true );
	} );

	it( 'is false on a WordPress page with wp-hooks', () => {
		installHooks();
		expect( needsAddonScripts() ).toBe( false );
	} );
} );

