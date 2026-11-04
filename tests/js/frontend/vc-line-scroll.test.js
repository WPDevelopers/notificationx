/**
 * Option C on the slim Verified Card: the pure timing (vcScrollPlan) and the
 * hook (useVcLineScroll). jsdom has no matchMedia, Element.animate or layout,
 * so all three are stubbed here.
 */
import React, { useRef } from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import { vcScrollPlan, VC_SCROLL } from '../../../nxdev/notificationx/frontend/themes/vc/vcScrollPlan';
import useVcLineScroll from '../../../nxdev/notificationx/frontend/themes/vc/useVcLineScroll';

describe( 'vcScrollPlan', () => {
	it( 'has the approved constants', () => {
		expect( VC_SCROLL ).toEqual( { WAIT: 800, HOLD: 800, VMIN: 40, VMAX: 80, WIN: 2900, DMAX: 232 } );
	} );

	it.each( [
		// d, v (px/s), t (ms)
		[ 10, 40, 250 ],
		[ 50, 40, 1250 ],
		[ 116, 40, 2900 ],
		[ 117, ( 117 * 1000 ) / 2900, 2900 ],
		[ 150, ( 150 * 1000 ) / 2900, 2900 ],
		[ 232, 80, 2900 ],
	] )( 'd = %ipx scrolls at %fpx/s for %ims', ( d, v, t ) => {
		const plan = vcScrollPlan( d );
		expect( plan.d ).toBe( d );
		expect( plan.v ).toBeCloseTo( v, 6 );
		expect( plan.v ).toBeGreaterThanOrEqual( 40 );
		expect( plan.v ).toBeLessThanOrEqual( 80 );
		expect( plan.t ).toBe( t );
		expect( plan.total ).toBe( 800 + t + 800 );
		expect( plan.total ).toBeLessThanOrEqual( 4500 );
	} );

	it.each( [ 233, 400, 0, 0.5, -5, NaN, undefined ] )( 'd = %p does not scroll', ( d ) => {
		expect( vcScrollPlan( d ) ).toBeNull();
	} );
} );

/* ---------- hook ---------- */

const flush = async () => {
	for ( let i = 0; i < 6; i++ ) {
		// eslint-disable-next-line no-await-in-loop
		await Promise.resolve();
	}
};

// A fake Web Animation whose `finished` the test settles by hand.
const makeAnimation = ( keyframes, options, { rejectOnCancel = true, noFinished = false } = {} ) => {
	let resolve;
	let reject;
	const a = {
		keyframes,
		options,
		playState: 'running',
		pause: jest.fn( () => {
			a.playState = 'paused';
		} ),
		play: jest.fn( () => {
			a.playState = 'running';
		} ),
		cancel: jest.fn( () => {
			a.playState = 'idle';
			if ( rejectOnCancel && reject ) reject( new Error( 'AbortError' ) );
		} ),
		finish: () => {
			a.playState = 'finished';
			resolve();
		},
	};
	if ( ! noFinished ) {
		a.finished = new Promise( ( res, rej ) => {
			resolve = res;
			reject = rej;
		} );
		a.finished.catch( () => {} );
	}
	return a;
};

let container;
let anims;
let animOptions;
let motion;
let originalMatchMedia;
let originalAnimate;

const stubMotion = ( { reduce = false, legacy = false } = {} ) => {
	motion = {
		matches: reduce,
		media: '(prefers-reduced-motion: reduce)',
		handlers: [],
	};
	if ( legacy ) {
		motion.addListener = jest.fn( ( h ) => motion.handlers.push( h ) );
		motion.removeListener = jest.fn( ( h ) => {
			motion.handlers = motion.handlers.filter( ( x ) => x !== h );
		} );
	} else {
		motion.addEventListener = jest.fn( ( type, h ) => motion.handlers.push( h ) );
		motion.removeEventListener = jest.fn( ( type, h ) => {
			motion.handlers = motion.handlers.filter( ( x ) => x !== h );
		} );
	}
	window.matchMedia = jest.fn( () => motion );
};
const setMotion = ( reduce ) => {
	motion.matches = reduce;
	motion.handlers.slice().forEach( ( h ) => h( { matches: reduce } ) );
};

const Harness = () => {
	const msgRef = useRef( null );
	const inRef = useRef( null );
	useVcLineScroll( msgRef, inRef );
	return (
		<div className="notification-item nx-notification nx-vc" id="root">
			<div className="nx-vc-card nx-vc-slim">
				<div className="nx-vc-l1">
					<span className="nx-vc-msg" ref={ msgRef }>
						<span className="nx-vc-msg-in" ref={ inRef }>
							<b>Jane D.</b> just purchased <span>A very long product title</span>
						</span>
					</span>
				</div>
			</div>
			<div className="notificationx-link-wrapper">
				<a className="notificationx-link" href="https://example.com/p" />
			</div>
		</div>
	);
};

// Layout: clientWidth 200, scrollWidth 200 + hidden; the inner span is 200 + hidden wide.
const setLayout = ( hidden ) => {
	const msg = container.querySelector( '.nx-vc-msg' );
	const inner = container.querySelector( '.nx-vc-msg-in' );
	Object.defineProperty( msg, 'clientWidth', { configurable: true, get: () => 200 } );
	Object.defineProperty( msg, 'scrollWidth', { configurable: true, get: () => 200 + hidden } );
	msg.getBoundingClientRect = () => ( { width: 200 } );
	inner.getBoundingClientRect = () => ( { width: 200 + hidden } );
};

const mount = () => {
	act( () => {
		ReactDOM.render( <Harness />, container );
	} );
};
const el = ( sel ) => container.querySelector( sel );
const isRun = () => el( '.nx-vc-msg' ).classList.contains( 'is-run' );
const fire = ( node, type ) => node.dispatchEvent( new Event( type, { bubbles: true } ) );

beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	anims = [];
	animOptions = {};
	originalMatchMedia = window.matchMedia;
	originalAnimate = Element.prototype.animate;
	Element.prototype.animate = jest.fn( function ( keyframes, options ) {
		const a = makeAnimation( keyframes, options, animOptions );
		anims.push( a );
		return a;
	} );
	stubMotion();
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	if ( originalMatchMedia ) window.matchMedia = originalMatchMedia;
	else delete window.matchMedia;
	if ( originalAnimate ) Element.prototype.animate = originalAnimate;
	else delete Element.prototype.animate;
} );

const Z = 'translateX(0)';

describe( 'useVcLineScroll', () => {
	it( 'waits 800ms, then measures, scrolls -d (LTR) and holds 800ms at the end', async () => {
		mount();
		// At mount only the wait runs; nothing was measured yet.
		expect( anims ).toHaveLength( 1 );
		expect( anims[ 0 ].keyframes ).toEqual( [ { transform: Z }, { transform: Z } ] );
		expect( anims[ 0 ].options ).toEqual( { duration: 800, easing: 'linear', fill: 'forwards' } );
		expect( isRun() ).toBe( false );

		// jsdom has no layout, so at mount the line "fits" (0 <= 0). The real
		// font arrives during the wait and it overflows now: the hook must
		// not have bailed at mount.
		setLayout( 150 );
		anims[ 0 ].finish();
		await flush();

		expect( isRun() ).toBe( true );
		expect( anims ).toHaveLength( 2 );
		expect( anims[ 1 ].keyframes ).toEqual( [ { transform: Z }, { transform: 'translateX(-150px)' } ] );
		expect( anims[ 1 ].options ).toEqual( { duration: 2900, easing: 'linear', fill: 'forwards' } );

		anims[ 1 ].finish();
		await flush();
		expect( anims ).toHaveLength( 3 );
		expect( anims[ 2 ].keyframes ).toEqual( [ { transform: 'translateX(-150px)' }, { transform: 'translateX(-150px)' } ] );
		expect( anims[ 2 ].options.duration ).toBe( 800 );

		anims[ 2 ].finish();
		await flush();
		// Stays at the end: no scroll back, nothing cancelled, still is-run.
		expect( anims ).toHaveLength( 3 );
		anims.forEach( ( a ) => expect( a.cancel ).not.toHaveBeenCalled() );
		expect( isRun() ).toBe( true );
	} );

	it( 'measures the fit only after the wait (no scroll when it fits then)', async () => {
		mount();
		setLayout( 0 );
		anims[ 0 ].finish();
		await flush();
		expect( anims ).toHaveLength( 1 );
		expect( isRun() ).toBe( false );
	} );

	it( 'keeps the "…" when more than 232px is hidden', async () => {
		mount();
		setLayout( 233 );
		anims[ 0 ].finish();
		await flush();
		expect( anims ).toHaveLength( 1 );
		expect( isRun() ).toBe( false );
	} );

	it( 'scrolls +d in RTL', async () => {
		mount();
		el( '.nx-vc-msg' ).style.direction = 'rtl';
		setLayout( 50 );
		anims[ 0 ].finish();
		await flush();
		expect( anims[ 1 ].keyframes ).toEqual( [ { transform: Z }, { transform: 'translateX(50px)' } ] );
		expect( anims[ 1 ].options.duration ).toBe( 1250 );
	} );

	it( 'pauses on the notification root, including during the wait', async () => {
		mount();
		const root = el( '#root' );
		fire( root, 'pointerenter' );
		expect( anims[ 0 ].pause ).toHaveBeenCalled();
		expect( anims[ 0 ].playState ).toBe( 'paused' );
		fire( root, 'pointerleave' );
		expect( anims[ 0 ].play ).toHaveBeenCalled();
		expect( anims[ 0 ].playState ).toBe( 'running' );

		// Held when the scroll step starts: it is created paused.
		fire( root, 'touchstart' );
		setLayout( 100 );
		anims[ 0 ].finish();
		await flush();
		expect( anims[ 1 ].pause ).toHaveBeenCalled();
		expect( anims[ 1 ].playState ).toBe( 'paused' );
		fire( root, 'touchend' );
		expect( anims[ 1 ].playState ).toBe( 'running' );
		fire( root, 'touchstart' );
		fire( root, 'touchcancel' );
		expect( anims[ 1 ].playState ).toBe( 'running' );
	} );

	it( 'pauses on touches that land on the Analytics link overlay', async () => {
		mount();
		const overlay = el( 'a.notificationx-link' );
		fire( overlay, 'touchstart' );
		expect( anims[ 0 ].playState ).toBe( 'paused' );
		fire( overlay, 'touchend' );
		expect( anims[ 0 ].playState ).toBe( 'running' );

		setLayout( 100 );
		anims[ 0 ].finish();
		await flush();
		fire( overlay, 'touchstart' );
		expect( anims[ 1 ].playState ).toBe( 'paused' );
		// A pointer entering the overlay also enters the root, which gets its
		// own (non-bubbling) pointerenter.
		fire( overlay, 'touchend' );
		el( '#root' ).dispatchEvent( new Event( 'pointerenter' ) );
		expect( anims[ 1 ].playState ).toBe( 'paused' );
		el( '#root' ).dispatchEvent( new Event( 'pointerleave' ) );
		expect( anims[ 1 ].playState ).toBe( 'running' );
	} );

	it( 'does nothing under reduced motion at start', () => {
		stubMotion( { reduce: true } );
		mount();
		expect( Element.prototype.animate ).not.toHaveBeenCalled();
		expect( isRun() ).toBe( false );
	} );

	it( 'stops and restores the "…" when reduced motion turns on mid-run', async () => {
		mount();
		setLayout( 150 );
		anims[ 0 ].finish();
		await flush();
		expect( isRun() ).toBe( true );

		setMotion( true );
		expect( anims[ 0 ].cancel ).toHaveBeenCalled();
		expect( anims[ 1 ].cancel ).toHaveBeenCalled();
		expect( isRun() ).toBe( false );
		await flush();
		expect( anims ).toHaveLength( 2 );
	} );

	it( 'uses the legacy addListener when addEventListener is missing', async () => {
		stubMotion( { legacy: true } );
		mount();
		expect( motion.addListener ).toHaveBeenCalledTimes( 1 );
		setMotion( true );
		expect( anims[ 0 ].cancel ).toHaveBeenCalled();
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		expect( motion.removeListener ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'is a no-op when matchMedia or Element.animate is missing', () => {
		delete window.matchMedia;
		mount();
		expect( Element.prototype.animate ).not.toHaveBeenCalled();
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );

		stubMotion();
		delete Element.prototype.animate;
		expect( () => mount() ).not.toThrow();
		expect( isRun() ).toBe( false );
	} );

	it( 'stops when Animation.finished is missing', () => {
		animOptions = { noFinished: true };
		mount();
		expect( anims ).toHaveLength( 1 );
		expect( anims[ 0 ].cancel ).toHaveBeenCalled();
		expect( isRun() ).toBe( false );
	} );

	it( 'cleans up on unmount', async () => {
		mount();
		const root = el( '#root' );
		const removeSpy = jest.spyOn( root, 'removeEventListener' );
		setLayout( 150 );
		anims[ 0 ].finish();
		await flush();
		const msg = el( '.nx-vc-msg' );

		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		anims.forEach( ( a ) => expect( a.cancel ).toHaveBeenCalled() );
		expect( msg.classList.contains( 'is-run' ) ).toBe( false );
		const removed = removeSpy.mock.calls.map( ( c ) => c[ 0 ] ).sort();
		expect( removed ).toEqual( [ 'pointerenter', 'pointerleave', 'touchcancel', 'touchend', 'touchstart' ] );
		expect( motion.removeEventListener ).toHaveBeenCalledWith( 'change', expect.any( Function ) );
		expect( motion.handlers ).toHaveLength( 0 );

		// After unmount, events and late promises do nothing.
		fire( root, 'pointerenter' );
		await flush();
		expect( anims ).toHaveLength( 2 );
	} );

	it( 'ignores stale promises after a stop (run token)', async () => {
		// Cancel does not settle `finished` here, so the old wait can still
		// resolve later, as a late microtask would.
		animOptions = { rejectOnCancel: false };
		mount();
		setLayout( 150 );
		setMotion( true ); // stop(): token moves on
		expect( anims[ 0 ].cancel ).toHaveBeenCalled();
		anims[ 0 ].finish();
		await flush();
		expect( anims ).toHaveLength( 1 );
		expect( isRun() ).toBe( false );
	} );

	it( 'ignores a stale scroll step after a stop', async () => {
		animOptions = { rejectOnCancel: false };
		mount();
		setLayout( 150 );
		anims[ 0 ].finish();
		await flush();
		expect( anims ).toHaveLength( 2 );
		setMotion( true );
		anims[ 1 ].finish();
		await flush();
		// No hold step and no is-run after the stop.
		expect( anims ).toHaveLength( 2 );
		expect( isRun() ).toBe( false );
	} );
} );
