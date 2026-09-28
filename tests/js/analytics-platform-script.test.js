/**
 * Analytics renders the link/button of every notification. It used to append
 * Google's platform.js (~63 KB) for every notification; now it does so only
 * when the YouTube subscribe widget (.g-ytsubscribe) is rendered.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Analytics from '../../nxdev/notificationx/frontend/core/Analytics';
import { NotificationProvider } from '../../nxdev/notificationx/frontend/core/NotificationProvider';

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
} );

describe( 'platform.js loading', () => {
	it( 'is not added for a regular notification link', () => {
		render( {
			config: { source: 'woocommerce', link_type: 'product_page', link_button: true, link_button_text: 'Buy' },
			data: { link: 'https://site.test/p/1' },
		} );

		expect( platformScripts() ).toHaveLength( 0 );
		expect( container.querySelector( '.g-ytsubscribe' ) ).toBeNull();
		expect( container.querySelector( 'a' ).getAttribute( 'href' ) ).toBe( 'https://site.test/p/1' );
	} );

	it( 'is not added for a press bar', () => {
		render( {
			config: { ...ytChannelConfig, source: 'press_bar', link_text: true },
			data: { id: 'UC123', link: 'https://site.test' },
		} );

		expect( platformScripts() ).toHaveLength( 0 );
	} );

	it( 'is not added for a YouTube channel using a custom button', () => {
		render( {
			config: { ...ytChannelConfig, nx_subscribe_button_type: 'custom' },
			data: { id: 'UC123', link: 'https://youtube.com/c/x' },
		} );

		expect( platformScripts() ).toHaveLength( 0 );
		expect( container.querySelector( '.g-ytsubscribe' ) ).toBeNull();
	} );

	it( 'is not added when the channel id is missing', () => {
		render( { config: ytChannelConfig, data: {} } );

		expect( platformScripts() ).toHaveLength( 0 );
	} );

	it( 'is added with the subscribe widget and removed on unmount', () => {
		render( { config: ytChannelConfig, data: { id: 'UC123' } } );

		const widget = container.querySelector( '.g-ytsubscribe' );
		expect( widget ).not.toBeNull();
		expect( widget.getAttribute( 'data-channelid' ) ).toBe( 'UC123' );
		expect( platformScripts() ).toHaveLength( 1 );
		expect( platformScripts()[ 0 ].async ).toBe( true );

		unmount();
		expect( platformScripts() ).toHaveLength( 0 );
	} );

	it( 'is added again for each newly mounted widget, as before', () => {
		render( { config: ytChannelConfig, data: { id: 'UC123' } } );
		unmount();
		render( { config: ytChannelConfig, data: { id: 'UC456' } } );

		expect( platformScripts() ).toHaveLength( 1 );
	} );

	it( 'is added for announcements using the default subscribe button', () => {
		render( {
			config: { ...ytChannelConfig, link_type: 'announcements_link', announcement_link_button_text: 'Go' },
			data: { id: 'UC123' },
		} );

		expect( platformScripts() ).toHaveLength( 1 );
	} );
} );
