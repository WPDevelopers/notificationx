/**
 * An open Exit Intent popup keeps its own state when other notifications
 * appear or go: NotificationContainer used to render the position groups by
 * index, so a corner notification's group showing up before the Exit Intent
 * group re-mounted the popup (typed text cleared, focus lost, animations
 * replayed). Drives the real hook (useNotificationX) and container.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import useNotificationX from '../../../nxdev/notificationx/frontend/core/useNotificationX';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import NotificationContainer from '../../../nxdev/notificationx/frontend/core/NotificationContainer';

const rest = { root: 'https://shop.example/wp-json/', namespace: 'notificationx/v1', omit_credentials: true };

const EXIT = {
	id: 'exit-12',
	config: { nx_id: 12, type: 'exit_intent', source: 'exit_intent_custom', themes: 'exit_intent_custom_theme-one', exit_intent_show_message: true },
	data: null,
};
const CORNER = ( id, position = 'bottom_left' ) => ( {
	id,
	config: { nx_id: 30, type: 'conversions', source: 'woocommerce', themes: 'theme-one', position, display_for: 5, delay_between: 5 },
	data: { title: 'Someone bought a cake', name: 'A', timestamp: 0 },
} );

let ctx;
let container;
const Harness = () => {
	ctx = useNotificationX( { config: { rest, gmt_offset: 0 } } );
	return (
		<NotificationProvider value={ ctx }>
			<NotificationContainer />
		</NotificationProvider>
	);
};

beforeEach( () => {
	sessionStorage.clear();
	// Set by the page's localize script.
	window.notificationXArr = [ {} ];
	global.fetch = jest.fn( () => new Promise( () => {} ) );
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	act( () => {
		ReactDOM.render( <Harness />, container );
	} );
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	delete global.fetch;
} );

const type = ( el, value ) => {
	const setter = Object.getOwnPropertyDescriptor( window.HTMLTextAreaElement.prototype, 'value' ).set;
	act( () => {
		setter.call( el, value );
		el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	} );
};
const add = ( notice ) => act( () => ctx.dispatch( { type: 'ADD_NOTIFICATION', payload: notice } ) );
const remove = ( id ) => act( () => ctx.dispatch( { type: 'REMOVE_NOTIFICATION', payload: id } ) );

describe( 'an open Exit Intent popup and other notifications', () => {
	it( 'keeps the typed text, the focus and its DOM when a corner notification comes and goes', () => {
		add( EXIT );
		const overlay = container.querySelector( '.nx-exit-intent-overlay' );
		const textarea = overlay.querySelector( 'textarea' );
		type( textarea, 'I was typing this' );
		textarea.focus();

		add( CORNER( 'corner-1' ) );
		expect( container.querySelector( '.nx-container.nxc-bottom_left' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBe( overlay );
		expect( container.querySelector( 'textarea' ).value ).toBe( 'I was typing this' );
		expect( document.activeElement ).toBe( textarea );

		remove( 'corner-1' );
		expect( container.querySelector( '.nx-container.nxc-bottom_left' ) ).toBeNull();
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBe( overlay );
		expect( container.querySelector( 'textarea' ).value ).toBe( 'I was typing this' );
		expect( document.activeElement ).toBe( textarea );
	} );

	it( 'the same with groups in several positions changing', () => {
		add( CORNER( 'corner-1', 'top_right' ) );
		add( EXIT );
		const overlay = container.querySelector( '.nx-exit-intent-overlay' );
		type( overlay.querySelector( 'textarea' ), 'kept' );
		add( CORNER( 'corner-2', 'bottom_left' ) );
		remove( 'corner-1' );
		add( CORNER( 'corner-3', 'bottom_right' ) );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBe( overlay );
		expect( container.querySelector( 'textarea' ).value ).toBe( 'kept' );
	} );

	it( 'other notifications still render and go as before', () => {
		add( CORNER( 'corner-1' ) );
		add( EXIT );
		expect( container.querySelectorAll( '.nx-container.nxc-bottom_left' ) ).toHaveLength( 1 );
		remove( 'exit-12' );
		expect( container.querySelector( '.nx-exit-intent-overlay' ) ).toBeNull();
		expect( container.querySelectorAll( '.nx-container.nxc-bottom_left' ) ).toHaveLength( 1 );
	} );
} );
