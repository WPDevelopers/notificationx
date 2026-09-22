/**
 * Frontend-only helpers touched by the B1 performance work.
 */
import { parseDelaySeconds } from '../../nxdev/notificationx/frontend/core/functions';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
import apiFetch from '@wordpress/api-fetch';
import { requestCookieDeletion } from '../../nxdev/notificationx/frontend/gdpr/utils/helper';

describe( 'parseDelaySeconds', () => {
	it.each( [
		[ undefined, 5 ],
		[ null, 5 ],
		[ '', 5 ],
		[ '   ', 5 ],
		[ 'abc', 5 ],
		[ NaN, 5 ],
		[ Infinity, 5 ],
	] )( 'falls back to the default for %p', ( value, expected ) => {
		expect( parseDelaySeconds( value, 5 ) ).toBe( expected );
	} );

	it.each( [
		[ 0, 0 ],
		[ '0', 0 ],
		[ 3, 3 ],
		[ '3', 3 ],
		[ '2.5', 2.5 ],
		[ ' 7 ', 7 ],
		[ 5, 5 ],
	] )( 'keeps the configured value %p', ( value, expected ) => {
		expect( parseDelaySeconds( value, 5 ) ).toBe( expected );
	} );

	it( 'clamps negative values to 0, matching how setTimeout treated them', () => {
		expect( parseDelaySeconds( -3, 5 ) ).toBe( 0 );
		expect( parseDelaySeconds( '-1', 5 ) ).toBe( 0 );
	} );

	it( 'uses 5 seconds when no fallback is given', () => {
		expect( parseDelaySeconds( '' ) ).toBe( 5 );
	} );
} );

describe( 'requestCookieDeletion', () => {
	beforeEach( () => apiFetch.mockReset() );

	it( 'sends the same request the admin nxHelper.get() sent', async () => {
		apiFetch.mockResolvedValue( { success: true } );

		await expect( requestCookieDeletion() ).resolves.toEqual( { success: true } );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/notificationx/v1/index.php?rest_route=/notificationx/v1/delete-cookies/',
			method: 'GET',
		} );
	} );

	it( 'swallows request errors like the admin helper did', async () => {
		apiFetch.mockRejectedValue( new Error( 'network' ) );

		await expect( requestCookieDeletion() ).resolves.toBeUndefined();
	} );
} );
