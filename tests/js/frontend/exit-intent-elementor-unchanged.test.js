/**
 * Elementor-built Exit Intent popups keep exactly today's markup and close
 * behaviour. The snapshots were written before "Build With Gutenberg" changed
 * ExitIntentPopup.tsx and must not change.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import ExitIntentPopup from '../../../nxdev/notificationx/frontend/core/ExitIntentPopup';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	state: { is_pro: false },
};

const ELEMENTOR_HTML = '<div class="nx-exit-intent-elementor-wrap" style="--nx-exit-width:648px;"><div data-elementor-type="container" class="elementor elementor-210"><div class="elementor-element e-con"><h2 class="elementor-heading-title">Wait! Before You Go</h2><a class="elementor-button" href="#nx-close">No thanks</a></div></div></div>';

let container;
let dispatch;
beforeEach( () => {
	sessionStorage.clear();
	dispatch = jest.fn();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
} );

const render = ( extra = {} ) => {
	const nxExitIntent = {
		id: 36,
		config: {
			nx_id: 36,
			source: 'exit_intent_custom',
			themes: null,
			mode: 'elementor',
			elementor_id: 210,
			elementor_html: ELEMENTOR_HTML,
			popup_layout: { width: '648px' },
			exit_intent_cookie_days: 7,
			...extra,
		},
	};
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ frontendContext }>
				<ExitIntentPopup nxExitIntent={ nxExitIntent } rest={ frontendContext.rest } dispatch={ dispatch } />
			</NotificationProvider>,
			container
		);
	} );
	return container;
};

describe( 'Elementor Exit Intent popup', () => {
	it( 'renders the same markup as before', () => {
		expect( render().innerHTML ).toMatchSnapshot();
	} );

	it( 'renders the same markup with the close button styled and hidden overlay colour', () => {
		expect( render( { advance_edit: true, exit_intent_close_color: '#ff0000', exit_intent_close_size: 20, exit_intent_overlay_color: 'rgba(1,2,3,0.4)' } ).innerHTML ).toMatchSnapshot();
	} );

	it( 'renders the same markup without a close button', () => {
		expect( render( { show_close_button: false } ).innerHTML ).toMatchSnapshot();
	} );

	it( 'the close button writes the same key as before and removes the notice', () => {
		render();
		act( () => {
			container.querySelector( '.nx-exit-intent-close' ).click();
		} );
		expect( sessionStorage.getItem( 'notificationx_exit_intent_36_' ) ).toBe( 'closed' );
		expect( document.cookie ).toContain( 'notificationx_exit_intent_36_=closed' );
		expect( dispatch ).toHaveBeenCalledWith( { type: 'REMOVE_NOTIFICATION', payload: 36 } );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBeNull();
	} );

	it( 'a backdrop click closes it; a click inside does not', () => {
		render();
		act( () => {
			container.querySelector( '.nx-exit-intent-elementor-body h2' ).click();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).not.toBeNull();
		act( () => {
			container.querySelector( '.nx-exit-intent-overlay' ).click();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBeNull();
	} );

	it( 'a #nx-close link inside an Elementor design does nothing new', () => {
		render();
		act( () => {
			container.querySelector( 'a[href="#nx-close"]' ).click();
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).not.toBeNull();
	} );

	it( 'Escape does not close an Elementor popup (unchanged)', () => {
		render();
		act( () => {
			document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );
		} );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).not.toBeNull();
	} );
} );
