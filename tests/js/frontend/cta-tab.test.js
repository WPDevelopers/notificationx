import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from '../helpers';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	state: { is_pro: false },
	getTime: () => ( { fromNow: () => '5 days ago' } ),
};

const config = ( extra = {} ) => ( {
	nx_id: 7,
	type: 'conversions',
	source: 'woocommerce',
	themes: 'conversions_theme-one',
	position: 'bottom_left',
	display_for: 5,
	animation_notification_hide: 'default',
	link_type: 'product_page',
	link_button: true,
	link_button_text: 'Buy now',
	template: [ '{{name}}', '{{title}}' ],
	...extra,
} );

const entry = { name: 'Jane', title: 'Shoes', link: 'https://example.com/shoes', updated_at: '2026-10-01 00:00:00' };

let container;
beforeEach( () => {
	jest.useFakeTimers( 'modern' );
	installHooks();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
	jest.useRealTimers();
	jest.restoreAllMocks();
} );

const render = ( cfg, dispatch = jest.fn() ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ frontendContext }>
				<Notification config={ cfg } data={ entry } id={ 1 } dispatch={ dispatch } assets={ {} } />
			</NotificationProvider>,
			container
		);
	} );
	return container.querySelector( '.notification-item' );
};

// Run the card's timer in 25ms steps (one tick at 5s) and note when each state first appears.
const runTimeline = ( cfg ) => {
	const dispatch = jest.fn();
	const card = render( cfg, dispatch );
	const seen = {};
	const end = ( cfg.display_for || 5 ) * 1000 + 1000;
	for ( let t = 25; t <= end; t += 25 ) {
		act( () => {
			jest.advanceTimersByTime( 25 );
		} );
		if ( seen.tuck === undefined && card.classList.contains( 'cta-tuck' ) ) seen.tuck = t;
		if ( seen.exit === undefined && card.classList.contains( 'exit' ) ) seen.exit = t;
		if ( seen.hide === undefined && card.classList.contains( cfg.animation_notification_hide ) ) seen.hide = t;
		if ( seen.removed === undefined && dispatch.mock.calls.some( ( [ a ] ) => a.type === 'REMOVE_NOTIFICATION' ) ) seen.removed = t;
	}
	ReactDOM.unmountComponentAtNode( container );
	return seen;
};

describe( 'CTA tab tuck (cta-tuck)', () => {
	it( 'is added 200ms before the card hides', () => {
		const seen = runTimeline( config() );
		expect( seen.exit ).toBeDefined();
		expect( seen.tuck ).toBeGreaterThanOrEqual( seen.exit - 225 );
		expect( seen.tuck ).toBeLessThanOrEqual( seen.exit - 175 );
	} );

	it( 'does not move the hide or the removal', () => {
		const withButton = runTimeline( config() );
		const withoutButton = runTimeline( config( { link_button: false } ) );
		expect( withButton.exit ).toBe( withoutButton.exit );
		expect( withButton.removed ).toBe( withoutButton.removed );
		// As before the change: hide at 99.5% of 5s, removal 500ms later.
		expect( withButton.exit ).toBe( 4975 );
		expect( withButton.removed ).toBe( 5475 );
	} );

	it( 'leads a Pro slide-out exit as well', () => {
		const seen = runTimeline( config( { animation_notification_hide: 'animate__slideOutDown' } ) );
		// Slide-outs start at 85% of a 5s display: 4250ms.
		expect( seen.tuck ).toBeGreaterThanOrEqual( 4250 - 225 );
		expect( seen.tuck ).toBeLessThanOrEqual( 4250 - 175 );
	} );

	it( 'is never added without the Button', () => {
		expect( runTimeline( config( { link_button: false } ) ).tuck ).toBeUndefined();
	} );
} );

describe( 'CTA tab tuck leads every hide animation by 200ms', () => {
	// Pro's `nx_frontend_animation` adds the hide class while `animation` is true.
	beforeEach( () => {
		window.wp.hooks.addFilter( 'nx_frontend_animation', 'test', ( value, { settings, animation } ) =>
			animation && settings.animation_notification_hide !== 'default'
				? { classNames: [ 'animate__animated', settings.animation_notification_hide, 'animate__faster' ], style: {} }
				: value
		);
	} );

	const hides = [
		'default', 'animate__fadeOut', 'animate__fadeOutDown', 'animate__fadeOutRight', 'animate__fadeOutUp',
		'animate__lightSpeedOutLeft', 'animate__zoomOut',
		'animate__slideOutDown', 'animate__slideOutLeft', 'animate__slideOutRight', 'animate__slideOutUp',
	];
	const cases = [];
	hides.forEach( ( hide ) => [ 2, 4, 5, 9, 12, 15, 18, 25 ].forEach( ( secs ) => cases.push( [ hide, secs ] ) ) );

	it.each( cases )( '%s, %is', ( hide, secs ) => {
		const seen = runTimeline( config( { animation_notification_hide: hide, display_for: secs } ) );
		// The hide starts with the Pro class, or with `exit` for the default.
		const hideAt = hide === 'default' ? seen.exit : seen.hide;
		const tick = Math.max( 25, secs * 1000 * 0.005 );
		expect( hideAt ).toBeDefined();
		expect( hideAt - seen.tuck ).toBeGreaterThanOrEqual( 200 - tick );
		expect( hideAt - seen.tuck ).toBeLessThanOrEqual( 200 + tick );
	} );
} );

describe( 'CTA tab peek (cta-in)', () => {
	const withEntrance = () => {
		const real = window.getComputedStyle;
		jest.spyOn( window, 'getComputedStyle' ).mockImplementation( ( el ) =>
			el.classList?.contains( 'notification-item' ) ? { animationName: 'SlideTop' } : real( el )
		);
	};
	const animationEnd = ( el ) => act( () => {
		el.dispatchEvent( new Event( 'animationend', { bubbles: true } ) );
	} );

	it( 'waits for the card\'s own entrance to end, not a child\'s', () => {
		withEntrance();
		const card = render( config() );
		expect( card.classList.contains( 'cta-in' ) ).toBe( false );
		animationEnd( card.querySelector( '.notificationx-link' ) );
		expect( card.classList.contains( 'cta-in' ) ).toBe( false );
		animationEnd( card );
		expect( card.classList.contains( 'cta-in' ) ).toBe( true );
	} );

	it( 'shows at once when the card has no entrance animation', () => {
		jest.spyOn( window, 'getComputedStyle' ).mockReturnValue( { animationName: 'none' } );
		expect( render( config() ).classList.contains( 'cta-in' ) ).toBe( true );
	} );

	it( 'falls back after 3s when the entrance never ends', () => {
		withEntrance();
		const card = render( config( { display_for: 10 } ) );
		act( () => {
			jest.advanceTimersByTime( 2975 );
		} );
		expect( card.classList.contains( 'cta-in' ) ).toBe( false );
		act( () => {
			jest.advanceTimersByTime( 50 );
		} );
		expect( card.classList.contains( 'cta-in' ) ).toBe( true );
	} );
} );
