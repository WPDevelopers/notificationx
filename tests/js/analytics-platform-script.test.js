/**
 * Analytics renders the link/button of every notification. The YouTube
 * subscribe widget (.g-ytsubscribe) and Google's platform.js are NotificationX
 * Pro code (issue #184): free never loads platform.js and renders a widget
 * only when an add-on returns one from `nx_frontend_link_widget`.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Analytics from '../../nxdev/notificationx/frontend/core/Analytics';
import { NotificationProvider } from '../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from './helpers';

const PLATFORM_SRC = 'https://apis.google.com/js/platform.js';

const context = {
	rest: { root: 'https://site.test/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
};

let container;

const platformScripts = () =>
	Array.from( document.querySelectorAll( 'script' ) ).filter( ( s ) => s.src === PLATFORM_SRC );

const render = ( props ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ context }>
				<Analytics { ...props } />
			</NotificationProvider>,
			container
		);
	} );
};

const unmount = () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
};

const ytChannelConfig = {
	source: 'youtube',
	link_type: 'yt_channel_link',
	nx_subscribe_button_type: 'yt_default',
	link_button: true,
	link_button_text_channel: 'Subscribe',
};

beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );

afterEach( () => {
	unmount();
	container.remove();
	platformScripts().forEach( ( s ) => s.remove() );
	delete window.wp;
} );

describe( 'link widget', () => {
	it( 'renders the regular link without a widget', () => {
		render( {
			config: { source: 'woocommerce', link_type: 'product_page', link_button: true, link_button_text: 'Buy' },
			data: { link: 'https://site.test/p/1' },
		} );

		expect( container.querySelector( '.g-ytsubscribe' ) ).toBeNull();
		expect( container.querySelector( 'a' ).getAttribute( 'href' ) ).toBe( 'https://site.test/p/1' );
	} );

	it( 'free never adds platform.js or the subscribe widget itself', () => {
		render( { config: ytChannelConfig, data: { id: 'UC123', link: 'https://youtube.com/c/x' } } );

		expect( platformScripts() ).toHaveLength( 0 );
		expect( container.querySelector( '.g-ytsubscribe' ) ).toBeNull();
	} );

	it( 'nx_frontend_link_widget replaces the link with the add-on element', () => {
		const filter = jest.fn( ( value, args ) =>
			args.config.nx_subscribe_button_type === 'yt_default' && args.show_default_subscribe
				? <div className="pro-widget" />
				: value
		);
		installHooks().addFilter( 'nx_frontend_link_widget', 'test', filter );
		render( { config: ytChannelConfig, data: { id: 'UC123', link: 'https://youtube.com/c/x' } } );

		expect( container.querySelectorAll( '.pro-widget' ) ).toHaveLength( 1 );
		expect( container.querySelector( 'a' ) ).toBeNull();
		expect( filter.mock.calls[ 0 ][ 1 ] ).toHaveProperty( 'data.id', 'UC123' );
	} );

	it( 'keeps the link when the add-on does not claim it', () => {
		installHooks().addFilter( 'nx_frontend_link_widget', 'test', ( value ) => value );
		render( {
			config: { ...ytChannelConfig, nx_subscribe_button_type: 'nx_custom' },
			data: { id: 'UC123', link: 'https://youtube.com/c/x' },
		} );

		expect( container.querySelector( 'a' ).getAttribute( 'href' ) ).toBe( 'https://youtube.com/c/x' );
	} );
} );
