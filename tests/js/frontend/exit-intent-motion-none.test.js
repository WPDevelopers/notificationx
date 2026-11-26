/**
 * Exit Intent popups with the default animation ("None") look and close
 * exactly as before the Show/Hide animation options existed.
 *
 * The snapshots were written before the animation options were added and must
 * not change. Every variant (no animation keys, as saved before; "None"
 * saved; and a Pro-like `nx_frontend_animation` filter with is_pro) must
 * render the same markup as the snapshot.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import { installHooks } from '../helpers';

jest.mock( '../../../nxdev/notificationx/frontend/core/LoadAssets', () => ( { loadAssets: jest.fn( () => Promise.resolve() ) } ) );

// eslint-disable-next-line import/first
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
// eslint-disable-next-line import/first
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

const rest = { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false };

const ELEMENTOR_HTML = '<div class="nx-exit-intent-elementor-wrap" style="--nx-exit-width:648px;"><div class="elementor elementor-210"><h2 class="elementor-heading-title">Wait!</h2><a class="elementor-button" href="#nx-close">No thanks</a></div></div>';
const GUTENBERG_HTML = '<div class="wp-block-group"><h2 class="wp-block-heading">Wait! Here’s 10% off</h2><p><a href="#nx-close">No thanks</a></p></div>';

const DESIGNS = {
	'theme one': { themes: 'exit_intent_custom_theme-one' },
	'theme three': { themes: 'exit_intent_custom_theme-three', exit_intent_image_url: 'https://example.com/a.png' },
	'theme four': { themes: 'exit_intent_custom_theme-four', exit_intent_t4_video_url: 'https://youtu.be/abc' },
	'theme one, advanced design': { themes: 'exit_intent_custom_theme-one', advance_edit: true, exit_intent_bg_color: '#fff', exit_intent_overlay_color: 'rgba(1,2,3,0.4)' },
	'bottom-right position': { themes: 'exit_intent_custom_theme-one', exit_intent_position: 'bottom-right' },
	elementor: { themes: null, mode: 'elementor', elementor_id: 210, elementor_html: ELEMENTOR_HTML, popup_layout: { width: '648px' } },
	gutenberg: { themes: null, mode: 'gutenberg', title: 'Coupon popup', gutenberg_id: 9, gutenberg_html: GUTENBERG_HTML, gutenberg_layout: { width: '520px', radius: '16px' } },
};

// Saved "None" for both options (the field value is `default`).
const NONE = { exit_intent_animation_show: 'default', exit_intent_animation_hide: 'default' };

// Pro's filter (nxdev/frontend-themes/features/animation.ts) leaves the value
// alone when both options are the default.
const proLikeFilter = ( value, { settings, is_pro } ) => {
	if ( value !== undefined ) return value;
	if ( ! is_pro || ( settings?.animation_notification_show === 'default' && settings?.animation_notification_hide === 'default' ) ) return value;
	return { classNames: [ 'animate__animated', settings.animation_notification_show, 'animate__faster' ], style: {} };
};

let container;
let dispatch;
beforeEach( () => {
	sessionStorage.clear();
	document.body.className = '';
	dispatch = jest.fn();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
} );

const render = ( config, isPro = false ) => {
	const nxExitIntent = { id: 36, config: { nx_id: 36, source: 'exit_intent_custom', exit_intent_cookie_days: 7, ...config } };
	const ctx = { rest, state: { is_pro: isPro } };
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ ctx }>
				<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ rest } dispatch={ dispatch } />
			</NotificationProvider>,
			container
		);
	} );
	return container.innerHTML;
};
const fresh = () => {
	ReactDOM.unmountComponentAtNode( container );
	container.innerHTML = '';
};

describe.each( Object.entries( DESIGNS ) )( '%s with the default animation', ( name, design ) => {
	it( 'renders the same markup as before', () => {
		installHooks();
		const before = render( design );
		expect( before ).toMatchSnapshot();

		fresh();
		expect( render( { ...design, ...NONE } ) ).toBe( before );

		fresh();
		installHooks().addFilter( 'nx_frontend_animation', 'test', proLikeFilter );
		expect( render( { ...design, ...NONE }, true ) ).toBe( before );

		fresh();
		expect( render( design, true ) ).toBe( before );
	} );

	it( 'the close button removes the popup at once, as before', () => {
		installHooks().addFilter( 'nx_frontend_animation', 'test', proLikeFilter );
		render( { ...design, ...NONE }, true );
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBeNull();
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 36 } );
	} );
} );

describe( 'Pro themes get the same props as before', () => {
	it( 'overlay class and helpers passed to nx_exit_intent_theme', () => {
		const seen = [];
		const hooks = installHooks();
		hooks.addFilter( 'nx_frontend_animation', 'test', proLikeFilter );
		hooks.addFilter( 'nx_exit_intent_theme', 'test', ( node, props ) => {
			seen.push( { theme: props.theme, overlayClass: props.overlayClass, showClose: props.showClose, closeStyle: props.closeStyle, keys: Object.keys( props ).sort() } );
			return <div className={ props.overlayClass }><button className="nx-exit-intent-close" onClick={ props.handleClose }>×</button></div>;
		} );
		const html = render( { themes: 'exit_intent_custom_theme-five', ...NONE }, true );
		expect( { html, seen: seen[ 0 ] } ).toMatchSnapshot();
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBeNull();
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 36 } );
	} );
} );
