import { nxApplyFilters, nxApplyListFilter } from '../../../nxdev/notificationx/frontend/core/hooks';
import { installHooks } from '../helpers';

describe( 'nxApplyFilters', () => {
	it( 'returns the default value when window.wp is missing (crossSite.js on a non-WordPress site)', () => {
		expect( nxApplyFilters( 'nx_test', 'default' ) ).toBe( 'default' );
		expect( nxApplyFilters( 'nx_test', undefined ) ).toBeUndefined();
	} );

	it( 'returns the default value when wp exists without hooks', () => {
		window.wp = {};
		expect( nxApplyFilters( 'nx_test', 'default' ) ).toBe( 'default' );
	} );

	it( 'runs filters registered on the global window.wp.hooks registry', () => {
		const hooks = installHooks();
		hooks.addFilter( 'nx_test', 'test', ( value, a, b ) => `${ value }:${ a }:${ b }` );
		expect( nxApplyFilters( 'nx_test', 'v', 1, 2 ) ).toBe( 'v:1:2' );
	} );

	it( 'sees a registry that is installed after the module loaded', () => {
		expect( nxApplyFilters( 'nx_test', 'v' ) ).toBe( 'v' );
		installHooks().addFilter( 'nx_test', 'test', () => 'late' );
		expect( nxApplyFilters( 'nx_test', 'v' ) ).toBe( 'late' );
	} );

	it( 'logs and falls back to the default value when a filter throws', () => {
		const error = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		installHooks().addFilter( 'nx_test', 'test', () => {
			throw new Error( 'broken add-on' );
		} );
		expect( nxApplyFilters( 'nx_test', 'default' ) ).toBe( 'default' );
		expect( error ).toHaveBeenCalled();
		error.mockRestore();
	} );
} );

describe( 'nxApplyListFilter', () => {
	it( 'returns the built-in list without a registry', () => {
		expect( nxApplyListFilter( 'nx_list', [ 'a' ] ) ).toEqual( [ 'a' ] );
	} );

	it( 'returns the filtered list', () => {
		installHooks().addFilter( 'nx_list', 'test', ( list ) => [ ...list, 'b' ] );
		expect( nxApplyListFilter( 'nx_list', [ 'a' ] ) ).toEqual( [ 'a', 'b' ] );
	} );

	it.each( [ [ null ], [ 'a,b' ], [ { a: 1 } ], [ undefined ] ] )(
		'keeps the built-in list when a filter returns %p',
		( bad ) => {
			installHooks().addFilter( 'nx_list', 'test', () => bad );
			expect( nxApplyListFilter( 'nx_list', [ 'a' ] ) ).toEqual( [ 'a' ] );
		}
	);
} );
