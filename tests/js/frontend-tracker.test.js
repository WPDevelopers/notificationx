/**
 * The event beacon behind the Analytics Audience reports
 * (frontend/core/tracker.ts).
 */
import { startTracking, trackEvent, flush, __resetTracking } from '../../nxdev/notificationx/frontend/core/tracker';

let observed;
let ioCallback;

class FakeIO {
	constructor( cb ) {
		ioCallback = cb;
	}
	observe( el ) {
		observed.push( el );
	}
	unobserve( el ) {
		observed = observed.filter( ( e ) => e !== el );
	}
	disconnect() {}
}

const sent = () => global.fetch.mock.calls.map( ( c ) => JSON.parse( c[ 1 ].body ) );
const events = () => sent().flatMap( ( b ) => b.ev );
const config = { url: 'https://site.test/wp-json/notificationx/v1/track', token: 'tok' };

const mount = ( html ) => {
	const div = document.createElement( 'div' );
	div.innerHTML = html;
	document.body.appendChild( div );
	return div.firstElementChild;
};

const intersect = ( el, ratio ) =>
	ioCallback( [ { target: el, isIntersecting: ratio > 0, intersectionRatio: ratio, intersectionRect: { height: 10 } } ] );

beforeEach( () => {
	jest.useFakeTimers();
	observed = [];
	global.IntersectionObserver = FakeIO;
	global.fetch = jest.fn( () => Promise.resolve() );
	document.body.innerHTML = '';
	__resetTracking();
} );

afterEach( () => {
	jest.useRealTimers();
	delete navigator.globalPrivacyControl;
} );

test( 'does nothing without a config', () => {
	startTracking( undefined );
	trackEvent( 5, 'click' );
	flush();
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'respects Global Privacy Control', () => {
	Object.defineProperty( navigator, 'globalPrivacyControl', { value: true, configurable: true } );
	startTracking( config );
	trackEvent( 5, 'click' );
	flush();
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'a view needs half of the notification on screen for a second', async () => {
	const el = mount( '<div data-nx-id="7"><p>Someone bought</p></div>' );
	startTracking( config );
	expect( observed ).toContain( el );

	intersect( el, 0.3 );
	jest.advanceTimersByTime( 3000 );
	expect( events() ).toEqual( [] );

	intersect( el, 0.6 );
	jest.advanceTimersByTime( 500 );
	intersect( el, 0 ); // scrolled away before a second passed
	jest.advanceTimersByTime( 3000 );
	expect( events() ).toEqual( [] );

	intersect( el, 0.6 );
	jest.advanceTimersByTime( 1000 );
	flush();
	expect( events() ).toEqual( [ { n: 7, e: 'view' } ] );
	expect( sent()[ 0 ].t ).toBe( 'tok' );
} );

test( 'notifications added later are observed, and a view is counted once per page', async () => {
	startTracking( config );
	const el = mount( '<div data-nx-id="9"></div>' );
	await Promise.resolve(); // MutationObserver callback
	expect( observed ).toContain( el );
	trackEvent( 9, 'view' );
	trackEvent( 9, 'view' );
	flush();
	expect( events() ).toEqual( [ { n: 9, e: 'view' } ] );
} );

test( 'clicks, closes and submits are picked up from inside the notification', () => {
	const el = mount(
		'<div data-nx-id="3"><a href="/shop" class="cta">Buy</a><span class="notificationx-close">x</span><form><input name="e"><button type="submit">Go</button></form><a class="nx-powered-by" href="https://x.test">by</a></div>'
	);
	startTracking( config );
	el.querySelector( '.cta' ).addEventListener( 'click', ( e ) => e.preventDefault() );
	el.querySelector( '.cta' ).click();
	el.querySelector( '.notificationx-close' ).click();
	el.querySelector( 'input' ).click();
	el.querySelector( '.nx-powered-by' ).addEventListener( 'click', ( e ) => e.preventDefault() );
	el.querySelector( '.nx-powered-by' ).click();
	el.querySelector( 'form' ).dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );
	flush();
	expect( events() ).toEqual( [
		{ n: 3, e: 'click' },
		{ n: 3, e: 'close' },
		{ n: 3, e: 'submit' },
	] );
} );

test( 'a CTA reported twice (own handler + delegated listener) counts one click', () => {
	startTracking( config );
	trackEvent( 4, 'click' );
	trackEvent( 4, 'click' );
	jest.advanceTimersByTime( 1000 );
	trackEvent( 4, 'click' );
	flush();
	expect( events() ).toEqual( [ { n: 4, e: 'click' }, { n: 4, e: 'click' } ] );
} );

test( 'batches flush on their own after a short delay, at most 20 per request', () => {
	startTracking( config );
	for ( let i = 1; i <= 25; i++ ) {
		trackEvent( i, 'view' );
	}
	expect( global.fetch ).toHaveBeenCalledTimes( 1 );
	jest.advanceTimersByTime( 2000 );
	expect( sent().map( ( b ) => b.ev.length ) ).toEqual( [ 20, 5 ] );
	expect( global.fetch.mock.calls[ 0 ][ 1 ].credentials ).toBe( 'omit' );
} );

test( 'uses sendBeacon when the page is going away', () => {
	navigator.sendBeacon = jest.fn( () => true );
	startTracking( config );
	trackEvent( 2, 'click' );
	window.dispatchEvent( new Event( 'pagehide' ) );
	expect( navigator.sendBeacon ).toHaveBeenCalledTimes( 1 );
	expect( global.fetch ).not.toHaveBeenCalled();
	delete navigator.sendBeacon;
} );

test( 'a hover needs the mouse to rest on the notification for half a second, once per page', () => {
	const el = mount( '<div data-nx-id="12"><p class="in">Someone bought</p><span class="in2">x</span></div>' );
	startTracking( config );
	el.querySelector( '.in' ).dispatchEvent( new MouseEvent( 'mouseover', { bubbles: true } ) );
	jest.advanceTimersByTime( 300 );
	// Moving between children of the same notification keeps the timer.
	el.querySelector( '.in' ).dispatchEvent( new MouseEvent( 'mouseout', { bubbles: true, relatedTarget: el.querySelector( '.in2' ) } ) );
	jest.advanceTimersByTime( 300 );
	el.dispatchEvent( new MouseEvent( 'mouseover', { bubbles: true } ) );
	jest.advanceTimersByTime( 600 );
	// Leaving early never counts.
	const other = mount( '<div data-nx-id="13"></div>' );
	other.dispatchEvent( new MouseEvent( 'mouseover', { bubbles: true } ) );
	jest.advanceTimersByTime( 200 );
	other.dispatchEvent( new MouseEvent( 'mouseout', { bubbles: true, relatedTarget: document.body } ) );
	jest.advanceTimersByTime( 1000 );
	flush();
	expect( events() ).toEqual( [ { n: 12, e: 'hover' } ] );
} );

test( 'hover is not tracked on touch screens', () => {
	window.matchMedia = jest.fn( () => ( { matches: true } ) );
	const el = mount( '<div data-nx-id="14"></div>' );
	startTracking( config );
	el.dispatchEvent( new MouseEvent( 'mouseover', { bubbles: true } ) );
	jest.advanceTimersByTime( 1000 );
	flush();
	expect( events() ).toEqual( [] );
	delete window.matchMedia;
} );

test( 'events go to Google Analytics only when enabled', () => {
	window.gtag = jest.fn();
	startTracking( config );
	trackEvent( 3, 'click' );
	expect( window.gtag ).not.toHaveBeenCalled();
	__resetTracking();
	startTracking( { ...config, ga: true } );
	trackEvent( 3, 'click' );
	expect( window.gtag ).toHaveBeenCalledWith( 'event', 'nx_click', { notification_id: 3, event_category: 'NotificationX' } );
	delete window.gtag;
	__resetTracking();
	window.dataLayer = [];
	startTracking( { ...config, ga: true } );
	trackEvent( 3, 'view' );
	expect( window.dataLayer ).toEqual( [ { event: 'nx_view', notification_id: 3, event_category: 'NotificationX' } ] );
	delete window.dataLayer;
} );
