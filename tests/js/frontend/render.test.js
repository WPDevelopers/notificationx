import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Theme from '../../../nxdev/notificationx/frontend/themes/Theme';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import Content from '../../../nxdev/notificationx/frontend/themes/helpers/Content';
import Image from '../../../nxdev/notificationx/frontend/themes/helpers/Image';
import Analytics from '../../../nxdev/notificationx/frontend/core/Analytics';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import NotificationForMobile from '../../../nxdev/notificationx/frontend/core/NotificationForMobile';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from '../helpers';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '5 days' : '5 days ago' ) } ),
};

let container;
beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
} );

const render = ( element ) => {
	act( () => {
		ReactDOM.render( <NotificationProvider value={ frontendContext }>{ element }</NotificationProvider>, container );
	} );
	return container;
};

const announcementConfig = ( themes ) => ( {
	nx_id: 1,
	themes,
	source: 'announcements',
	link: 'https://example.com/sale',
	announcement_link_button_text: 'Shop now',
	template: [ '{{title}}', '{{time}}' ],
} );

const entry = ( extra = {} ) => ( {
	title: 'Big sale',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/i.png', alt: '' },
	...extra,
} );

describe( 'Theme hooks', () => {
	it( 'renders the built-in announcement button for theme-13 and theme-15 without filters', () => {
		render( <Theme config={ announcementConfig( 'announcements_theme-13' ) } data={ entry() } /> );
		expect( container.querySelectorAll( 'a[href="https://example.com/sale"]' ) ).toHaveLength( 1 );
		expect( container.querySelector( 'a svg' ) ).not.toBeNull();
	} );

	it( 'nx_theme_before_content replaces the built-in button (no double render)', () => {
		const filter = jest.fn( ( value, props ) =>
			props.config.themes === 'announcements_theme-13' ? <b className="pro-before">pro</b> : value
		);
		installHooks().addFilter( 'nx_theme_before_content', 'test', filter );
		render( <Theme config={ announcementConfig( 'announcements_theme-13' ) } data={ entry() } /> );

		expect( container.querySelectorAll( '.pro-before' ) ).toHaveLength( 1 );
		expect( container.querySelectorAll( 'a[href="https://example.com/sale"]' ) ).toHaveLength( 0 );
		expect( filter.mock.calls[ 0 ][ 1 ] ).toHaveProperty( 'announcementCSS' );
	} );

	it( 'nx_theme_after_content replaces the built-in theme-15 button', () => {
		installHooks().addFilter( 'nx_theme_after_content', 'test', ( value, props ) =>
			props.config.themes === 'announcements_theme-15' ? <i className="pro-after" /> : value
		);
		render( <Theme config={ announcementConfig( 'announcements_theme-15' ) } data={ entry() } /> );

		expect( container.querySelectorAll( '.pro-after' ) ).toHaveLength( 1 );
		expect( container.querySelectorAll( 'a[href="https://example.com/sale"]' ) ).toHaveLength( 0 );
	} );

	it( 'nx_frontend_time_is_countdown defaults to true for announcements', () => {
		render( <Theme config={ announcementConfig( 'announcements_theme-13' ) } data={ entry() } /> );
		expect( container.textContent ).toContain( '5 days remaining' );
	} );

	it( 'nx_frontend_time_is_countdown lets an add-on switch the time format', () => {
		installHooks().addFilter( 'nx_frontend_time_is_countdown', 'test', () => false );
		render( <Theme config={ announcementConfig( 'announcements_theme-13' ) } data={ entry() } /> );
		expect( container.textContent ).toContain( '5 days ago' );
		expect( container.textContent ).not.toContain( 'remaining' );
	} );
} );

describe( 'Content / nx_content_append', () => {
	const props = () => ( { config: announcementConfig( 'announcements_theme-14' ), data: entry(), template: [ 'Row' ] } );

	it( 'renders the built-in theme-14 button without filters', () => {
		render( <Content { ...props() } /> );
		expect( container.querySelectorAll( 'a[href="https://example.com/sale"]' ) ).toHaveLength( 1 );
	} );

	it( 'renders the add-on element instead of the built-in button', () => {
		installHooks().addFilter( 'nx_content_append', 'test', ( value, p ) =>
			p.config.themes === 'announcements_theme-14' ? <u className="pro-append" /> : value
		);
		render( <Content { ...props() } /> );
		expect( container.querySelectorAll( '.pro-append' ) ).toHaveLength( 1 );
		expect( container.querySelectorAll( 'a[href="https://example.com/sale"]' ) ).toHaveLength( 0 );
	} );
} );

describe( 'Image / nx_frontend_image', () => {
	const props = ( themes ) => ( { config: { themes }, data: entry(), id: 1, theme: themes.split( '_' ).pop() } );

	it( 'renders the plain image for other themes', () => {
		render( <Image { ...props( 'woocommerce_sales_theme-one' ) } /> );
		expect( container.querySelector( 'img' ).getAttribute( 'src' ) ).toBe( 'https://example.com/i.png' );
	} );

	it( 'renders the add-on element for a theme it claims', () => {
		const filter = jest.fn( ( value, args ) =>
			args.config.themes === 'announcements_theme-1' ? <em className="pro-image" /> : value
		);
		installHooks().addFilter( 'nx_frontend_image', 'test', filter );
		render( <Image { ...props( 'announcements_theme-1' ) } /> );

		expect( container.querySelectorAll( '.pro-image' ) ).toHaveLength( 1 );
		expect( container.querySelector( 'img' ) ).toBeNull();
		expect( Object.keys( filter.mock.calls[ 0 ][ 1 ] ) ).toEqual(
			expect.arrayContaining( [ 'themeName', 'data', 'config', 'id', 'componentClasses', 'announcementCSS' ] )
		);
	} );

	it( 'renders nothing and skips the filter when there is no image data', () => {
		const filter = jest.fn();
		installHooks().addFilter( 'nx_frontend_image', 'test', filter );
		render( <Image config={ { themes: 'announcements_theme-1' } } data={ {} } /> );
		expect( container.innerHTML ).toBe( '' );
		expect( filter ).not.toHaveBeenCalled();
	} );
} );

describe( 'Analytics / nx_frontend_link_button', () => {
	const data = { link: 'https://example.com/entry' };

	it( 'keeps the built-in announcements_link button text', () => {
		render(
			<Analytics
				config={ { link_type: 'announcements_link', link_button: true, announcement_link_button_text: 'Grab it' } }
				data={ data }
			/>
		);
		expect( container.querySelector( 'a' ).textContent ).toContain( 'Grab it' );
	} );

	it( 'uses the link text an add-on returns for its own link type', () => {
		const filter = jest.fn( ( value, config ) =>
			config.link_type === 'my_link' ? { link_text: 'Claim offer' } : value
		);
		installHooks().addFilter( 'nx_frontend_link_button', 'test', filter );
		render( <Analytics config={ { link_type: 'my_link', link_button: true } } data={ data } /> );

		expect( container.querySelector( 'a' ).textContent ).toContain( 'Claim offer' );
		expect( filter.mock.calls[ 0 ][ 2 ] ).toBe( data );
	} );

	it( 'ignores a non-object return', () => {
		installHooks().addFilter( 'nx_frontend_link_button', 'test', () => 'Claim offer' );
		render(
			<Analytics config={ { link_type: 'custom', link_button: true, link_button_text: 'Buy' } } data={ data } />
		);
		expect( container.querySelector( 'a' ).textContent ).toContain( 'Buy' );
	} );
} );

describe( 'Notification / Live Effects (84711)', () => {
	let matchMedia;
	beforeEach( () => {
		jest.useFakeTimers();
		matchMedia = window.matchMedia;
		window.matchMedia = () => ( { matches: false, addEventListener() {}, removeEventListener() {} } );
	} );
	afterEach( () => {
		ReactDOM.unmountComponentAtNode( container );
		jest.useRealTimers();
		window.matchMedia = matchMedia;
	} );

	const renderCard = ( Card, isPro, liveEffects ) => {
		const config = {
			nx_id: 1,
			themes: 'woocommerce_sales_conv-theme-seven',
			responsive_themes: 'woocommerce_sales_res-theme-nine',
			source: 'woocommerce_sales',
			type: 'conversions',
			display_for: 5,
			animation_notification_show: 'default',
			animation_notification_hide: 'default',
			template: [ 'Row' ],
			live_effects: liveEffects,
		};
		act( () => {
			ReactDOM.render(
				<NotificationProvider value={ { ...frontendContext, state: { is_pro: isPro } } }>
					<Card id={ 1 } dispatch={ () => {} } data={ entry() } config={ config } />
				</NotificationProvider>,
				container
			);
		} );
		return container.firstElementChild;
	};

	[ [ 'desktop', Notification ], [ 'mobile', NotificationForMobile ] ].forEach( ( [ name, Card ] ) => {
		it( `adds nx-live-effects to the ${ name } card with Pro and the toggle on`, () => {
			const card = renderCard( Card, true, true );
			expect( card.classList.contains( 'nx-live-effects' ) ).toBe( true );
			expect( card.querySelector( '.notificationx-content' ).classList.contains( 'nx-live-effects' ) ).toBe( false );
		} );

		it( `leaves the ${ name } card classes unchanged with the toggle off or on Free`, () => {
			const off = renderCard( Card, true, false ).className;
			expect( off ).not.toContain( 'nx-live-effects' );
			expect( renderCard( Card, false, true ).className ).toBe( off );
		} );
	} );
} );

describe( 'Theme / Live Effects rotating line (84711)', () => {
	const peekConfig = () => {
		const config = {
			nx_id: 1,
			source: 'woocommerce_cart_peek',
			type: 'woocommerce_cart_peek',
			custom_type: 'woocommerce_sales',
			themes: 'woocommerce_sales_theme-one',
			live_effects: true,
			'notification-template': { first_param: 'tag_cart_peek_count', third_param: 'tag_product_title', fourth_param: 'tag_time' },
		};
		return { ...config, template: GetTemplate( config ) };
	};
	const peekEntry = ( extra = {} ) => entry( { title: 'Hoodie', cart_peek_count: '3 shoppers have this in their cart', ...extra } );

	it( 'rotates the product title and the add-to-cart time', () => {
		render( <Theme config={ peekConfig() } data={ peekEntry() } /> );
		const facts = container.querySelectorAll( '.nx-second-row .nx-live-rotator > .nx-live-fact' );
		expect( facts ).toHaveLength( 2 );
		expect( facts[ 0 ].textContent ).toBe( 'Hoodie' );
		expect( facts[ 1 ].textContent ).toBe( 'Added to a cart 5 days ago' );
	} );

	it( 'shows only the product title, without rotation, when there is no time', () => {
		render( <Theme config={ peekConfig() } data={ peekEntry( { updated_at: '' } ) } /> );
		expect( container.querySelector( '.nx-live-rotator' ) ).toBeNull();
		expect( container.querySelector( '.nx-second-row' ).textContent ).toBe( 'Hoodie' );
		expect( container.textContent ).not.toContain( 'Added to a cart' );
	} );
} );
