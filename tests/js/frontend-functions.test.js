/**
 * Frontend-only helpers touched by the B1 performance work.
 */
import { parseDelaySeconds, getAnimationTiming } from '../../nxdev/notificationx/frontend/core/functions';

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

describe( 'getAnimationTiming (80989 Animation Duration)', () => {
	const UNCHANGED = { className: '', style: {}, scale: 1, exitDelay: 500 };
	const hide = { animation_notification_hide: 'nx-anim-rise-soft-out' };

	it( 'changes nothing when the value is missing', () => {
		expect( getAnimationTiming( {}, true ) ).toEqual( UNCHANGED );
		expect( getAnimationTiming( undefined, true ) ).toEqual( UNCHANGED );
	} );

	it.each( [ 0.5, '0.5', ' 0.5 ' ] )( 'changes nothing at the default %p', ( value ) => {
		expect( getAnimationTiming( { ...hide, animation_duration: value }, true ) ).toEqual( UNCHANGED );
	} );

	it.each( [ 0, '0', -1, '-0.3', '', '   ', 'abc', null, NaN, Infinity ] )(
		'falls back to the default for invalid input %p',
		( value ) => {
			expect( getAnimationTiming( { ...hide, animation_duration: value }, true ) ).toEqual( UNCHANGED );
		}
	);

	it( 'changes nothing on Free', () => {
		expect( getAnimationTiming( { ...hide, animation_duration: 2 }, false ) ).toEqual( UNCHANGED );
	} );

	it( 'scales a small value and keeps the 500ms removal floor', () => {
		expect( getAnimationTiming( { ...hide, animation_duration: 0.25 }, true ) ).toEqual( {
			className: 'nx-anim-timed',
			scale: 0.5,
			style: { '--nx-anim-scale': '0.5', '--animate-duration': '500ms' },
			exitDelay: 500,
		} );
	} );

	it( 'scales a large value and waits for the exit', () => {
		expect( getAnimationTiming( { ...hide, animation_duration: '1.5', delay_between: 8 }, true ) ).toEqual( {
			className: 'nx-anim-timed',
			scale: 3,
			style: { '--nx-anim-scale': '3', '--animate-duration': '3000ms' },
			exitDelay: 1500,
		} );
	} );

	it( 'caps the removal delay at Delay Between', () => {
		expect( getAnimationTiming( { ...hide, animation_duration: 4, delay_between: 2 }, true ).exitDelay ).toBe( 2000 );
	} );

	it( 'reads Delay Between like the scheduler (empty or 0 means 5s)', () => {
		expect( getAnimationTiming( { ...hide, animation_duration: 9 }, true ).exitDelay ).toBe( 5000 );
		expect( getAnimationTiming( { ...hide, animation_duration: 9, delay_between: 0 }, true ).exitDelay ).toBe( 5000 );
	} );

	it( 'keeps 500ms removal when Hide is Default (no exit animation to wait for)', () => {
		const t = getAnimationTiming( { animation_notification_hide: 'default', animation_duration: 2 }, true );
		expect( t.className ).toBe( 'nx-anim-timed' );
		expect( t.exitDelay ).toBe( 500 );
	} );
} );
