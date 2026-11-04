/**
 * GOLDEN: today's rendered output for existing themes. Captured on the
 * unchanged code (84707 step 0) so later changes (the Verified Card themes)
 * can prove existing themes render exactly as before. Never update these
 * snapshots with -u to make a change pass.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Theme from '../../../nxdev/notificationx/frontend/themes/Theme';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import NXBranding from '../../../nxdev/notificationx/frontend/themes/helpers/NXBranding';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

// Fixed time, same pattern as render.test.js.
const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '5 days' : '5 days ago' ) } ),
	state: { is_pro: false },
};

const AFFILIATE = 'https://notificationx.com/?utm_source=example&ref=42';

let container;
beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
} );

const render = ( element ) => {
	act( () => {
		ReactDOM.render( <NotificationProvider value={ frontendContext }>{ element }</NotificationProvider>, container );
	} );
	return container;
};

const SALES = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just purchased',
	third_param: 'tag_product_title',
	custom_third_param: 'Anonymous Product',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};

const base = ( themes, source, type, tmpl, extra = {} ) => {
	const config = {
		nx_id: 42,
		id: 42,
		themes,
		source,
		type,
		position: 'bottom_left',
		close_button: true,
		close_button_tab: true,
		close_button_mobile: true,
		link_type: 'product_page',
		affiliate_link: AFFILIATE,
		image_shape: 'circle',
		size: 500,
		'notification-template': tmpl,
		...extra,
	};
	config.template = GetTemplate( config );
	return config;
};

const saleEntry = {
	name: 'Jane D.',
	title: 'Blue T-shirt',
	city: 'Dhaka',
	country: 'Bangladesh',
	link: 'https://example.com/product/blue-t-shirt',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/avatar.png', alt: 'Jane' },
};
const reviewEntry = {
	username: 'Sam',
	rated: 120,
	rating: 5,
	plugin_name: 'NotificationX',
	plugin_review: 'Great plugin, easy to set up.',
	link: 'https://wordpress.org/plugins/notificationx/',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/plugin.png', alt: '' },
};
const commentEntry = {
	name: 'Alex',
	post_title: 'Hello world',
	link: 'https://example.com/hello-world/#comment-1',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/gravatar.png', alt: 'Alex' },
};
const statsEntry = {
	plugin_theme_name: 'NotificationX',
	today: '1,234 times',
	today_text: 'Try it out',
	link: 'https://wordpress.org/plugins/notificationx/',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/plugin.png', alt: '' },
};

const CASES = [
	[ 'conversions_theme-one', () => base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES ), saleEntry ],
	[ 'conversions_conv-theme-twelve', () => base( 'conversions_conv-theme-twelve', 'woocommerce', 'conversions', SALES, { link_button: true, link_button_text: 'Buy now' } ), saleEntry ],
	[ 'woocommerce_sales_conv-theme-fifteen', () => base( 'woocommerce_sales_conv-theme-fifteen', 'woocommerce_sales', 'woocommerce_sales', SALES, { image_shape: 'rounded', link_button: true, link_button_text: 'Buy now' } ), saleEntry ],
	[ 'reviews_review-comment', () => base( 'reviews_review-comment', 'wp_reviews', 'reviews', {
		first_param: 'tag_username',
		second_param: 'just reviewed',
		third_param: 'tag_plugin_review',
		fourth_param: 'tag_rating',
	} ), reviewEntry ],
	[ 'reviews_total-rated', () => base( 'reviews_total-rated', 'wp_reviews', 'reviews', {
		first_param: 'tag_rated',
		second_param: 'people rated',
		third_param: 'tag_plugin_name',
		fourth_param: 'tag_rating',
	}, { image_shape: 'square' } ), reviewEntry ],
	[ 'comments_theme-six-free', () => base( 'comments_theme-six-free', 'wp_comments', 'comments', {
		first_param: 'tag_name',
		second_param: 'commented on',
		third_param: 'tag_post_title',
		fourth_param: 'tag_time',
	} ), commentEntry ],
	[ 'download_stats_today-download', () => base( 'download_stats_today-download', 'wp_stats', 'download_stats', {
		first_param: 'tag_plugin_theme_name',
		second_param: 'has been downloaded',
		third_param: 'tag_today',
		fourth_param: 'tag_today_text',
	}, { image_shape: 'square' } ), statsEntry ],
];

describe( 'GOLDEN Theme DOM (existing themes)', () => {
	describe.each( CASES )( '%s', ( label, makeConfig, entry ) => {
		const renderTheme = ( isMobile ) => {
			render(
				<Theme
					config={ makeConfig() }
					data={ { ...entry, image_data: { ...entry.image_data } } }
					id="nx-golden"
					dispatch={ jest.fn() }
					is_mobile={ isMobile }
				/>
			);
			return container.innerHTML;
		};

		it( 'is_mobile false', () => {
			expect( renderTheme( false ) ).toMatchSnapshot();
		} );

		it( 'is_mobile true', () => {
			expect( renderTheme( true ) ).toMatchSnapshot();
		} );

		it( 'is_mobile true renders the same markup as is_mobile false', () => {
			const desktop = renderTheme( false );
			act( () => {
				ReactDOM.unmountComponentAtNode( container );
			} );
			expect( renderTheme( true ) ).toBe( desktop );
		} );
	} );

	it( 'disable_powered_by removes the branding (conversions_theme-one)', () => {
		render(
			<Theme
				config={ base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES, { disable_powered_by: true } ) }
				data={ { ...saleEntry, image_data: { ...saleEntry.image_data } } }
				id="nx-golden"
				dispatch={ jest.fn() }
			/>
		);
		expect( container.querySelector( '.nx-branding' ) ).toBeNull();
		expect( container.innerHTML ).toMatchSnapshot();
	} );
} );

describe( 'GOLDEN Notification root className', () => {
	let originalMatchMedia;
	beforeEach( () => {
		jest.useFakeTimers();
		originalMatchMedia = window.matchMedia;
		// Notification.tsx calls window.matchMedia unguarded.
		window.matchMedia = jest.fn( ( query ) => ( {
			matches: false,
			media: query,
			onchange: null,
			addEventListener: jest.fn(),
			removeEventListener: jest.fn(),
			addListener: jest.fn(),
			removeListener: jest.fn(),
			dispatchEvent: jest.fn(),
		} ) );
	} );
	afterEach( () => {
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		jest.clearAllTimers();
		jest.useRealTimers();
		if ( originalMatchMedia ) {
			window.matchMedia = originalMatchMedia;
		} else {
			delete window.matchMedia;
		}
	} );

	const renderRoot = ( config ) => {
		render(
			<Notification
				config={ config }
				data={ { ...saleEntry, image_data: { ...saleEntry.image_data } } }
				id="nx-golden"
				dispatch={ jest.fn() }
			/>
		);
		return container.querySelector( '#notificationx-42' );
	};

	it( 'conversions_theme-one', () => {
		const root = renderRoot( base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES ) );
		expect( root.className ).toBe(
			'notification-item nx-notification source-woocommerce position-bottom_left type-conversions themes-theme-one themes-conversions_theme-one notificationx-42 has-close-btn'
		);
		expect( root.className ).toMatchSnapshot();
	} );

	it( 'conversions_conv-theme-twelve', () => {
		const root = renderRoot(
			base( 'conversions_conv-theme-twelve', 'woocommerce', 'conversions', SALES, { link_button: true, link_button_text: 'Buy now' } )
		);
		expect( root.className ).toBe(
			'notification-item nx-notification source-woocommerce position-bottom_left type-conversions themes-conv-theme-twelve themes-conversions_conv-theme-twelve notificationx-42 button-link has-close-btn'
		);
		expect( root.className ).toMatchSnapshot();
	} );

	it( 'conversions_theme-one with Advanced Design on', () => {
		const root = renderRoot(
			base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES, { advance_edit: true, image_position: 'right' } )
		);
		expect( root.className ).toMatchSnapshot();
	} );
} );

describe( 'GOLDEN NXBranding', () => {
	it( 'no props', () => {
		render( <NXBranding /> );
		expect( container.innerHTML ).toMatchSnapshot();
		expect( container.querySelector( '.nx-byline' ).textContent ).toBe( 'by' );
		expect( container.querySelector( 'a.nx-powered-by' ).getAttribute( 'rel' ) ).toBe( 'nofollow' );
		expect( container.querySelector( 'a.nx-powered-by' ).getAttribute( 'target' ) ).toBe( '_blank' );
	} );

	it( 'conversions_conv-theme-twelve config (BrandLogo)', () => {
		render( <NXBranding config={ { themes: 'conversions_conv-theme-twelve', affiliate_link: AFFILIATE } } /> );
		expect( container.innerHTML ).toMatchSnapshot();
		const link = container.querySelector( 'a.nx-powered-by' );
		expect( link.getAttribute( 'href' ) ).toBe( AFFILIATE );
		expect( link.querySelector( 'svg' ).getAttribute( 'viewBox' ) ).toBe( '0 0 180 48' );
	} );

	it( 'conversions_theme-one config (NotificationText)', () => {
		render( <NXBranding config={ { themes: 'conversions_theme-one', affiliate_link: AFFILIATE } } /> );
		expect( container.innerHTML ).toMatchSnapshot();
	} );
} );
