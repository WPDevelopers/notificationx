/**
 * Verified Cards (vc-*): rendered markup of Receipt, Showcase (Part 1),
 * Thread, Scorecard, Counter (Part 2) and Pledge, Enrolment, Submission,
 * Ratings, Installs (Part 3) through the real Theme, the credit, the slim (≤480px) layout, the Pro mobile guard,
 * Advanced Template and the Notification root class.
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import Theme from '../../../nxdev/notificationx/frontend/themes/Theme';
import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import Notification from '../../../nxdev/notificationx/frontend/core/Notification';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';

const frontendContext = {
	rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: false },
	getTime: () => ( { fromNow: ( withoutSuffix ) => ( withoutSuffix ? '5 days' : '5 days ago' ) } ),
	state: { is_pro: false },
};

const AFFILIATE = 'https://notificationx.com/?utm_source=example&ref=42';

const SALES = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just purchased',
	third_param: 'tag_product_title',
	custom_third_param: 'Anonymous Product',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const SHOWCASE = { ...SALES, second_param: 'Just purchased' };
const FREEMIUS = {
	...SALES,
	freemius_fifth_param: 'tag_plan_title',
	freemius_sixth_param: 'Subscription For',
	freemius_seventh_param: 'tag_pricing',
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
		image_shape: 'rounded',
		size: 500,
		'notification-template': tmpl,
		...extra,
	};
	config.template = config.template_adv ? config.template : GetTemplate( config );
	return config;
};

const receipt = ( extra ) => base( 'conversions_vc-receipt', 'woocommerce', 'conversions', SALES, extra );
const showcase = ( extra ) => base( 'woocommerce_sales_vc-showcase', 'woocommerce_sales', 'woocommerce_sales', SHOWCASE, extra );

const COMMENTS = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'commented on',
	third_param: 'tag_post_comment',
	custom_third_param: 'Anonymous Post',
	fourth_param: 'tag_time',
	custom_fourth_param: 'Some time ago',
};
const SCORECARD = {
	first_param: 'tag_username',
	custom_first_param: 'Someone',
	second_param: 'on',
	third_param: 'tag_plugin_name',
	fourth_param: 'tag_rating',
	fifth_param: 'tag_plugin_review',
	sixth_param: 'tag_title',
};
const COUNTER = {
	first_param: 'tag_plugin_theme_name',
	second_param: 'has been downloaded',
	third_param: 'tag_last_week',
	fourth_param: 'tag_last_week_text',
};

const thread = ( extra ) => base( 'comments_vc-thread', 'wp_comments', 'comments', COMMENTS, { image_shape: 'circle', link_type: 'comment_url', ...extra } );
const scorecard = ( extra ) => base( 'reviews_vc-scorecard', 'wp_reviews', 'reviews', SCORECARD, { image_shape: 'circle', link_type: 'review_page', ...extra } );
const counter = ( extra ) => base( 'download_stats_vc-counter', 'wp_stats', 'download_stats', COUNTER, { link_type: 'stats_page', ...extra } );

const DONATION = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'donated',
	third_param: 'tag_amount',
	fourth_param: 'tag_title',
	fifth_param: 'tag_time',
};
const ELEARNING = {
	first_param: 'tag_name',
	custom_first_param: 'Someone',
	second_param: 'just enrolled',
	third_param: 'tag_course_title',
	fourth_param: 'tag_time',
};
const FORM = {
	first_param: 'tag_your-name',
	custom_first_param: 'Someone',
	second_param: 'recently contacted via',
	third_param: 'tag_title',
	fourth_param: 'tag_time',
};
const RATINGS = { first_param: 'tag_rated', second_param: 'people rated', third_param: 'tag_plugin_name' };
const GOOGLE_RATINGS = { first_param: 'tag_rated', second_param: 'reviews', third_param: 'tag_place_name', fourth_param: 'tag_rating' };
const INSTALLS = { first_param: 'tag_active_installs', second_param: 'people are actively using', third_param: 'tag_plugin_theme_name' };

const pledge = ( extra ) => base( 'donation_vc-pledge', 'give', 'donation', DONATION, { image_shape: 'circle', ...extra } );
const enrol = ( extra ) => base( 'elearning_vc-enrol', 'tutor', 'elearning', ELEARNING, extra );
const submission = ( extra ) => base( 'form_vc-submission', 'cf7', 'form', FORM, { image_shape: 'circle', ...extra } );
const ratings = ( extra ) => base( 'reviews_vc-ratings', 'wp_reviews', 'reviews', RATINGS, { link_type: 'review_page', ...extra } );
const googleRatings = ( extra ) => base( 'google_reviews_vc-ratings', 'google_reviews', 'reviews', GOOGLE_RATINGS, extra );
const googleEntry = ( extra = {} ) => ( {
	rated: '1284',
	rating: '4.7',
	place_name: 'Blue Bottle Coffee – Hayes Valley',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/place.png', alt: '', classes: 'greview_icon' },
	...extra,
} );
const installs = ( extra ) => base( 'download_stats_vc-installs', 'wp_stats', 'download_stats', INSTALLS, { link_type: 'stats_page', ...extra } );

const donationEntry = ( extra = {} ) => ( {
	name: 'Emily S.',
	amount: '$50.00 for',
	title: 'Fundraising Camp for Health',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/avatar.png', alt: 'Emily S.', classes: 'gravatar' },
	...extra,
} );
const courseEntry = ( extra = {} ) => ( {
	name: 'John D.',
	course_title: 'PHP Beginners – Become a PHP Master',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/course.png', alt: '', classes: 'featured_image' },
	...extra,
} );
const formEntry = ( extra = {} ) => ( {
	'your-name': 'Emily S.',
	title: 'Support Us Form',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/avatar.png', alt: '', classes: 'gravatar' },
	...extra,
} );
const ratedEntry = ( extra = {} ) => ( {
	rated: '435',
	rating: '3',
	plugin_name: 'NotificationX',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/icon.png', alt: '', classes: 'featured_image' },
	...extra,
} );
const installsEntry = ( extra = {} ) => ( {
	active_installs: '40K',
	plugin_theme_name: 'Essential Addons for Elementor',
	image_data: { url: 'https://example.com/icon.png', alt: '' },
	...extra,
} );

const commentEntry = ( extra = {} ) => ( {
	name: 'Emily S.',
	post_title: 'Ten growth tips',
	post_comment: 'Tip #3 doubled our newsletter signups in a week, thanks!',
	link: 'https://example.com/ten-growth-tips/#comment-7',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/avatar.png', alt: 'Emily S.' },
	...extra,
} );
const reviewEntry = ( extra = {} ) => ( {
	username: 'jdoe_dev',
	rating: '4.4',
	title: 'Excellent plugin',
	plugin_review: 'Super easy to set up and the sales popups look great.',
	plugin_name: 'NotificationX',
	link: 'https://wordpress.org/support/topic/excellent',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/avatar.png', alt: 'jdoe_dev' },
	...extra,
} );
const statsEntry = ( extra = {} ) => ( {
	plugin_theme_name: 'NotificationX',
	last_week: '2.1K times in last 7 days',
	last_week_text: 'Get Started for Free.',
	link: 'https://wordpress.org/plugins/notificationx/',
	image_data: { url: 'https://example.com/icon.png', alt: 'NotificationX' },
	...extra,
} );

const saleEntry = ( extra = {} ) => ( {
	name: 'Jane D.',
	title: 'Blue T-shirt',
	city: 'Dhaka',
	country: 'Bangladesh',
	link: 'https://example.com/product/blue-t-shirt',
	updated_at: '2026-10-01 00:00:00',
	image_data: { url: 'https://example.com/product.png', alt: 'Blue T-shirt' },
	...extra,
} );

let container;
let originalMatchMedia;
const stubMatchMedia = ( slim ) => {
	window.matchMedia = jest.fn( ( query ) => ( {
		matches: query === '(max-width: 480px)' ? slim : false,
		media: query,
		onchange: null,
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		addListener: jest.fn(),
		removeListener: jest.fn(),
		dispatchEvent: jest.fn(),
	} ) );
};

beforeEach( () => {
	originalMatchMedia = window.matchMedia;
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	if ( originalMatchMedia ) {
		window.matchMedia = originalMatchMedia;
	} else {
		delete window.matchMedia;
	}
} );

const render = ( element ) => {
	act( () => {
		ReactDOM.render( <NotificationProvider value={ frontendContext }>{ element }</NotificationProvider>, container );
	} );
	return container;
};

const renderTheme = ( config, data = saleEntry(), extra = {} ) =>
	render( <Theme config={ config } data={ data } id="nx-vc" dispatch={ jest.fn() } { ...extra } /> );

const text = ( selector ) => container.querySelector( selector )?.textContent;

describe( 'Verified Card: desktop / tablet', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it( 'Receipt renders the vc card, not the generic content', () => {
		renderTheme( receipt() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-receipt' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( true );
		expect( card.classList.contains( 'nx-vc-slim' ) ).toBe( false );
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-inner' ) ).toBeNull();

		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-name' ) ).toBe( 'Jane D.' );
		expect( text( '.nx-vc-panel .nx-vc-label' ) ).toBe( 'just purchased' );
		expect( text( '.nx-vc-panel .nx-vc-body .nx-vc-em-a' ) ).toBe( 'Blue T-shirt' );
		// The Freemius plan row is hidden for every other source.
		expect( container.querySelector( '.nx-vc-plan' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel .notificationx-image img' ).getAttribute( 'src' ) ).toBe(
			'https://example.com/product.png'
		);
		expect( container.querySelector( '.nx-vc-card > .notificationx-close' ) ).not.toBeNull();
		// Only one layout is mounted.
		expect( container.querySelector( '.nx-vc-l1' ) ).toBeNull();
	} );

	it( 'Receipt on freemius_conversions shows plan and price', () => {
		renderTheme(
			base( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', FREEMIUS ),
			saleEntry( { plan_title: 'Pro Plus', pricing: '99 USD/Year' } )
		);
		const plan = container.querySelector( '.nx-vc-panel .nx-vc-plan' );
		expect( plan ).not.toBeNull();
		expect( [ ...plan.querySelectorAll( ':scope > span' ) ].map( ( s ) => s.textContent ) ).toEqual( [ 'Pro Plus', '99 USD/Year' ] );
	} );

	it( 'hides empty slots', () => {
		renderTheme( receipt(), saleEntry( { name: '' } ) );
		expect( container.querySelector( '.nx-vc-name' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).not.toBeNull();

		// No fourth param: no time and no reserved space for it.

		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		renderTheme( receipt( { 'notification-template': { ...SALES, fourth_param: '' } } ) );
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-card' ).classList.contains( 'has-time' ) ).toBe( false );
	} );

	it( 'renders no image when the entry has none', () => {
		renderTheme( receipt(), saleEntry( { image_data: false } ) );
		expect( container.querySelector( '.notificationx-image' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
	} );

	it( 'Showcase renders chip, title, buyer, location and image', () => {
		renderTheme( showcase() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-showcase' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-chip' ) ).toBe( 'Just purchased' );
		expect( text( '.nx-vc-sc-title' ) ).toBe( 'Blue T-shirt' );
		expect( text( '.nx-vc-sc-buyer b' ) ).toBe( 'Jane D.' );
		expect( container.querySelector( '.nx-vc-sc-buyer .nx-vc-sep' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-loc .nx-vc-pin' ) ).not.toBeNull();
		expect( text( '.nx-vc-loc' ).trim() ).toBe( 'Dhaka, Bangladesh' );
		// The image sits after the text column (right side).
		const main = container.querySelector( '.nx-vc-sc-main' );
		expect( main.lastElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
	} );

	it( 'Showcase without city and country shows no pin or separator', () => {
		renderTheme( showcase(), saleEntry( { city: '', country: '' } ) );
		expect( text( '.nx-vc-sc-buyer b' ) ).toBe( 'Jane D.' );
		expect( container.querySelector( '.nx-vc-loc' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-sep' ) ).toBeNull();
		expect( text( '.nx-vc-sc-buyer' ) ).toBe( 'Jane D.' );
	} );

	it( 'Showcase with only a location shows no leading separator', () => {
		renderTheme( showcase(), saleEntry( { name: '' } ) );
		expect( container.querySelector( '.nx-vc-sc-buyer b' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-sep' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc' ) ).not.toBeNull();
	} );

	it( 'escapes entry values', () => {
		renderTheme( receipt(), saleEntry( { name: '<img src=x onerror=alert(1)>' } ) );
		expect( container.querySelector( '.nx-vc-name img' ) ).toBeNull();
		expect( text( '.nx-vc-name' ) ).toBe( '<img src=x onerror=alert(1)>' );
	} );

	it( 'escapes the Showcase city and country', () => {
		renderTheme(
			showcase(),
			saleEntry( { city: '<img src=x onerror=alert(1)>', country: '<b>X</b>' } )
		);
		expect( container.querySelector( '.nx-vc-loc img' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc b' ) ).toBeNull();
		expect( text( '.nx-vc-loc' ).trim() ).toBe( '<img src=x onerror=alert(1)>, <b>X</b>' );
	} );

	it( 'skips the generic content filters (documented)', () => {
		const before = jest.fn( ( v ) => v );
		window.wp = { hooks: require( '@wordpress/hooks' ).createHooks() };
		try {
			window.wp.hooks.addFilter( 'nx_theme_before_content', 'test', before );
			renderTheme( receipt() );
			expect( before ).not.toHaveBeenCalled();
		} finally {
			delete window.wp;
		}
	} );
} );

describe( 'Verified Card: credit', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it.each( [
		[ 'Receipt', receipt, saleEntry ],
		[ 'Showcase', showcase, saleEntry ],
		[ 'Thread', thread, commentEntry ],
		[ 'Scorecard', scorecard, reviewEntry ],
		[ 'Counter', counter, statsEntry ],
		[ 'Pledge', pledge, donationEntry ],
		[ 'Enrolment', enrol, courseEntry ],
		[ 'Submission', submission, formEntry ],
		[ 'Ratings', ratings, ratedEntry ],
		[ 'Installs', installs, installsEntry ],
	] )( '%s: ✓ Verified by [bell] NotificationX', ( label, make, entry ) => {
		renderTheme( make(), entry() );
		const credit = container.querySelector( '.nx-vc-card .nx-vc-credit' );
		expect( credit ).not.toBeNull();
		expect( credit.tagName ).toBe( 'SMALL' );
		expect( credit.classList.contains( 'nx-branding' ) ).toBe( true );
		expect( credit.classList.contains( 'nx-branding-verified' ) ).toBe( true );
		expect( credit.firstElementChild.classList.contains( 'nx-verified-check' ) ).toBe( true );
		expect( credit.querySelector( '.nx-byline' ).textContent ).toBe( 'Verified by' );

		const link = credit.querySelector( 'a.nx-powered-by' );
		expect( link.getAttribute( 'href' ) ).toBe( AFFILIATE );
		expect( link.getAttribute( 'rel' ) ).toBe( 'nofollow' );
		expect( link.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( link.getAttribute( 'aria-label' ) ).toBe( 'NotificationX' );
		const svgs = link.querySelectorAll( 'svg' );
		expect( svgs ).toHaveLength( 2 );
		expect( svgs[ 0 ].getAttribute( 'class' ) ).toBe( 'nx-vc-bell' );
		expect( svgs[ 0 ].getAttribute( 'viewBox' ) ).toBe( '5.17 7.2 33.23 33.6' );
		expect( svgs[ 1 ].getAttribute( 'class' ) ).toBe( 'nx-vc-word' );
		expect( svgs[ 1 ].getAttribute( 'viewBox' ) ).toBe( '49.99 16.49 124.85 15.05' );
		// Not the old byline.
		expect( container.querySelector( '.nx-brand-logo' ) ).toBeNull();
	} );

	it( 'disable_powered_by removes the credit', () => {
		renderTheme( receipt( { disable_powered_by: true } ) );
		expect( container.querySelector( '.nx-vc-card' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
		expect( container.querySelector( '.nx-branding' ) ).toBeNull();
	} );
} );

describe( 'Verified Card: slim (≤480px)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it( 'Receipt line 1: <b>name</b> action product, time at the right, credit A', () => {
		renderTheme( receipt() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-receipt.nx-vc-slim' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();

		const inner = container.querySelector( '.nx-vc-l1 > .nx-vc-msg > .nx-vc-msg-in' );
		expect( inner ).not.toBeNull();
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Jane D.' );
		expect( inner.querySelector( '.nx-vc-em-a' ).textContent ).toBe( 'Blue T-shirt' );
		expect( inner.textContent ).toBe( 'Jane D. just purchased Blue T-shirt' );
		expect( text( '.nx-vc-l1 > .nx-vc-slim-time' ) ).toBe( '5 days ago' );

		const credit = container.querySelector( '.nx-vc-credit.is-slim' );
		expect( credit ).not.toBeNull();
		expect( credit.querySelector( '.nx-byline' ).textContent ).toBe( 'Verified by' );
		expect( credit.querySelector( 'a.nx-powered-by' ).getAttribute( 'href' ) ).toBe( AFFILIATE );
		expect( card.querySelector( ':scope > .notificationx-image' ) ).not.toBeNull();
		expect( card.querySelector( ':scope > .notificationx-close' ) ).not.toBeNull();
	} );

	it( 'Receipt slim drops the Freemius plan', () => {
		renderTheme(
			base( 'conversions_vc-receipt', 'freemius_conversions', 'conversions', FREEMIUS ),
			saleEntry( { plan_title: 'Pro Plus', pricing: '99 USD/Year' } )
		);
		expect( container.querySelector( '.nx-vc-msg-in' ).textContent ).toBe( 'Jane D. just purchased Blue T-shirt' );
		expect( container.querySelector( '.nx-vc-plan' ) ).toBeNull();
	} );

	it( 'Showcase line 1: <b>product</b> · action, time at the right', () => {
		renderTheme( showcase() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-showcase.nx-vc-slim' ) ).not.toBeNull();
		const inner = container.querySelector( '.nx-vc-msg-in' );
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Blue T-shirt' );
		expect( inner.querySelector( '.nx-vc-dim' ).textContent ).toBe( '·' );
		expect( inner.querySelector( '.nx-vc-em-a' ).textContent ).toBe( 'Just purchased' );
		expect( inner.textContent ).toBe( 'Blue T-shirt · Just purchased' );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		// The buyer, location and chip are dropped on phones.
		expect( container.querySelector( '.nx-vc-chip' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-loc' ) ).toBeNull();
	} );

	it( 'flags no image and no credit for the grid', () => {
		renderTheme( receipt( { disable_powered_by: true } ), saleEntry( { image_data: false } ) );
		const card = container.querySelector( '.nx-vc-slim' );
		expect( card.classList.contains( 'is-no-img' ) ).toBe( true );
		expect( card.classList.contains( 'is-no-credit' ) ).toBe( true );
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
	} );

	it( 'falls back to the desktop card without matchMedia', () => {
		delete window.matchMedia;
		renderTheme( receipt() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-receipt' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-slim' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-panel' ) ).not.toBeNull();
	} );
} );

describe( 'Verified Card: guards', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it.each( [
		[ 'Receipt', receipt, [ 'Jane D. just purchased', 'Blue T-shirt ', '5 days ago' ] ],
		[ 'Showcase', showcase, [ 'Jane D. Just purchased', 'Blue T-shirt', '5 days ago' ] ],
	] )( 'Pro mobile card (is_mobile) renders %s through the generic path with compact rows', ( label, make, rows ) => {
		renderTheme( make(), saleEntry(), { is_mobile: true } );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-inner' ) ).not.toBeNull();
		const content = container.querySelector( '.notificationx-content' );
		expect( content ).not.toBeNull();
		const ps = [ ...content.querySelectorAll( 'p' ) ];
		expect( ps ).toHaveLength( 3 );
		expect( ps.map( ( p ) => p.firstElementChild.textContent ) ).toEqual( rows );
		// The old "by NotificationX" credit in the last row.
		expect( ps[ 2 ].querySelector( '.nx-branding .nx-byline' ).textContent ).toBe( 'by' );
		expect( ps[ 2 ].querySelector( '.nx-branding-verified' ) ).toBeNull();
	} );

	it( 'Advanced Template on a vc key renders the generic card', () => {
		renderTheme( receipt( { template_adv: true, template: [ '{{name}} bought {{title}}', '{{time}}' ] } ) );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		const content = container.querySelector( '.notificationx-content' );
		expect( content ).not.toBeNull();
		expect( content.querySelector( '.nx-first-row' ).textContent ).toBe( 'Jane D. bought Blue T-shirt' );
	} );

	it( 'a vc key whose slug is not whitelisted takes the generic path', () => {
		const spy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		renderTheme(
			base( 'comments_vc-postcard', 'wp_comments', 'comments', {
				first_param: 'tag_name',
				second_param: 'commented on',
				third_param: 'tag_post_title',
				fourth_param: 'tag_time',
			} ),
			{ name: 'Alex', post_title: 'Hello', updated_at: '2026-10-01 00:00:00', image_data: false }
		);
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).not.toBeNull();
		spy.mockRestore();
	} );
} );

describe( 'Notification root class', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		stubMatchMedia( false );
	} );
	afterEach( () => {
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		jest.clearAllTimers();
		jest.useRealTimers();
	} );

	const renderRoot = ( config ) => {
		render( <Notification config={ config } data={ saleEntry() } id="nx-vc" dispatch={ jest.fn() } /> );
		return container.querySelector( '#notificationx-42' );
	};

	it.each( [
		[ 'conversions_vc-receipt', () => receipt() ],
		[ 'woocommerce_sales_vc-showcase', () => showcase() ],
		[ 'comments_vc-thread', () => thread() ],
		[ 'reviews_vc-scorecard', () => scorecard() ],
		[ 'download_stats_vc-counter', () => counter() ],
		[ 'donation_vc-pledge', () => pledge() ],
		[ 'elearning_vc-enrol', () => enrol() ],
		[ 'form_vc-submission', () => submission() ],
		[ 'reviews_vc-ratings', () => ratings() ],
		[ 'download_stats_vc-installs', () => installs() ],
	] )( '%s gets nx-vc and renders the vc card inside', ( label, make ) => {
		const root = renderRoot( make() );
		expect( root.classList.contains( `themes-${ label }` ) ).toBe( true );
		expect( root.classList.contains( 'nx-vc' ) ).toBe( true );
		expect( root.querySelector( '.nx-vc-card' ) ).not.toBeNull();
		// The Analytics overlay is still rendered after the card.
		expect( root.lastElementChild.classList.contains( 'notificationx-link-wrapper' ) ).toBe( true );
		expect( root.lastElementChild.querySelector( 'a.notificationx-link' ) ).not.toBeNull();
	} );

	it( 'a vc key with Advanced Template gets no nx-vc', () => {
		const root = renderRoot( receipt( { template_adv: true, template: [ '{{name}}' ] } ) );
		expect( root.classList.contains( 'nx-vc' ) ).toBe( false );
		expect( root.classList.contains( 'themes-vc-receipt' ) ).toBe( true );
	} );

	it( 'existing themes get no nx-vc (className unchanged)', () => {
		const root = renderRoot( base( 'conversions_theme-one', 'woocommerce', 'conversions', SALES, { image_shape: 'circle' } ) );
		expect( root.className ).toBe(
			'notification-item nx-notification source-woocommerce position-bottom_left type-conversions themes-theme-one themes-conversions_theme-one notificationx-42 has-close-btn'
		);
	} );
} );

describe( 'Verified Card Part 2: desktop / tablet', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it( 'Thread: avatar + name + action, time, the comment block', () => {
		renderTheme( thread(), commentEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-thread' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( true );
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );

		const head = container.querySelector( '.nx-vc-th-head' );
		expect( head.classList.contains( 'nx-vc-rsv' ) ).toBe( true );
		expect( head.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( head.querySelector( '.notificationx-image' ).classList.contains( 'image-circle' ) ).toBe( true );
		expect( text( '.nx-vc-th-who b' ) ).toBe( 'Emily S.' );
		expect( text( '.nx-vc-th-who .nx-vc-th-action' ) ).toBe( 'commented on' );
		expect( text( '.nx-vc-th-who' ) ).toBe( 'Emily S. commented on' );

		const quote = container.querySelector( '.nx-vc-quote' );
		expect( quote.textContent ).toBe( 'Tip #3 doubled our newsletter signups in a week, thanks!' );
		expect( quote.classList.contains( 'is-title' ) ).toBe( false );
		expect( container.querySelector( '.nx-vc-card > .notificationx-close' ) ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-l1' ) ).toBeNull();
	} );

	it( 'Thread with Post Title marks the block as a title', () => {
		renderTheme( thread( { 'notification-template': { ...COMMENTS, third_param: 'tag_post_title' } } ), commentEntry() );
		const quote = container.querySelector( '.nx-vc-quote' );
		expect( quote.textContent ).toBe( 'Ten growth tips' );
		expect( quote.classList.contains( 'is-title' ) ).toBe( true );
	} );

	it( 'Thread hides an empty comment and escapes it', () => {
		renderTheme( thread(), commentEntry( { post_comment: '' } ) );
		expect( container.querySelector( '.nx-vc-quote' ) ).toBeNull();
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		renderTheme( thread(), commentEntry( { post_comment: '<img src=x onerror=alert(1)>' } ) );
		expect( container.querySelector( '.nx-vc-quote img' ) ).toBeNull();
		expect( text( '.nx-vc-quote' ) ).toBe( '<img src=x onerror=alert(1)>' );
	} );

	it( 'Scorecard: reviewer, stars + rating + title, text, on <subject>, time', () => {
		renderTheme( scorecard(), reviewEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-scorecard' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-sr-head .nx-vc-name' ) ).toBe( 'jdoe_dev' );

		const stars = container.querySelector( '.nx-vc-rate .nx-vc-stars' );
		expect( stars.getAttribute( 'role' ) ).toBe( 'img' );
		expect( stars.getAttribute( 'aria-label' ) ).toBe( '4.4 out of 5' );
		// Partial fill, not rounded: four full stars and the fifth 40% filled.
		expect( stars.querySelectorAll( '.nx-vc-star.is-on' ) ).toHaveLength( 4 );
		expect( stars.querySelectorAll( '.nx-vc-star.is-off' ) ).toHaveLength( 0 );
		const fifth = stars.querySelectorAll( '.nx-vc-star' )[ 4 ];
		expect( fifth.classList.contains( 'is-part' ) ).toBe( true );
		expect( fifth.querySelector( '.nx-vc-star-fg' ).style.width ).toBe( '40%' );
		expect( text( '.nx-vc-rnum' ) ).toBe( '4.4' );
		expect( text( '.nx-vc-rtitle' ) ).toBe( 'Excellent plugin' );
		expect( text( '.nx-vc-rtext' ) ).toBe( 'Super easy to set up and the sales popups look great.' );
		expect( text( '.nx-vc-on' ) ).toBe( 'on NotificationX' );
		expect( text( '.nx-vc-on b' ) ).toBe( 'NotificationX' );
		// No raw rating text anywhere.
		expect( card.textContent ).not.toContain( 'rating::' );
	} );

	it( 'Scorecard: "5" shows five stars and 5.0', () => {
		renderTheme( scorecard(), reviewEntry( { rating: '5' } ) );
		expect( container.querySelectorAll( '.nx-vc-stars .nx-vc-star.is-on' ) ).toHaveLength( 5 );
		expect( text( '.nx-vc-rnum' ) ).toBe( '5.0' );
	} );

	it( 'Scorecard: no rating means no stars, no number and no rating:: text', () => {
		renderTheme( scorecard(), reviewEntry( { rating: '' } ) );
		expect( container.querySelector( '.nx-vc-stars' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-rnum' ) ).toBeNull();
		expect( text( '.nx-vc-rtitle' ) ).toBe( 'Excellent plugin' );
		expect( container.textContent ).not.toContain( 'rating::' );
	} );

	it( 'Scorecard hides the title on sources where it is not a headline', () => {
		renderTheme(
			base( 'woo_reviews_vc-scorecard', 'woo_reviews', 'reviews', { ...SCORECARD, third_param: 'tag_product_title', sixth_param: 'tag_title' }, { image_shape: 'circle' } ),
			// `title` is the product here; a leftover sixth_param must not show it
			// (the slot itself is locked in vc-get-template.test.js).
			reviewEntry( { title: 'Blue T-shirt' } )
		);
		expect( container.querySelector( '.nx-vc-rtitle' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-stars' ) ).not.toBeNull();
		expect( text( '.nx-vc-on b' ) ).toBe( 'Blue T-shirt' );
	} );

	it( 'Scorecard on ReviewX: product subject and the headline; a missing headline is not repeated', () => {
		const rx = base( 'reviewx_vc-scorecard', 'reviewx', 'reviews', { ...SCORECARD, third_param: 'tag_product_post_title' }, { image_shape: 'circle' } );
		renderTheme( rx, reviewEntry( { title: 'Great fit', post_title: 'Blue T-shirt' } ) );
		expect( text( '.nx-vc-rtitle' ) ).toBe( 'Great fit' );
		expect( text( '.nx-vc-on b' ) ).toBe( 'Blue T-shirt' );

		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		// Reviews::conversion_data copies post_title into an empty title.
		renderTheme( rx, reviewEntry( { title: 'Blue T-shirt', post_title: 'Blue T-shirt' } ) );
		expect( container.querySelector( '.nx-vc-rtitle' ) ).toBeNull();
		expect( text( '.nx-vc-on b' ) ).toBe( 'Blue T-shirt' );
	} );

	it.each( [
		[ 'featured_image', true ],
		[ 'gravatar', false ],
		[ 'greview_avatar', false ],
	] )( 'Scorecard image carries its type (%s) for the tile / circle CSS', ( type, tile ) => {
		renderTheme( scorecard(), reviewEntry( { image_data: { url: 'https://example.com/i.png', alt: '', classes: type } } ) );
		const image = container.querySelector( '.nx-vc-scorecard .notificationx-image' );
		// The theme shape stays circle; _vc.scss squares only featured_image.
		expect( image.classList.contains( 'image-circle' ) ).toBe( true );
		expect( image.classList.contains( 'featured_image' ) ).toBe( tile );
		expect( image.classList.contains( type ) ).toBe( true );
	} );

	it( 'Scorecard shows the time only at the top right, never in the body', () => {
		// e.g. the review text and rating fields left on "Definite Time" by another theme.
		renderTheme(
			scorecard( { 'notification-template': { ...SCORECARD, fourth_param: 'tag_time', fifth_param: 'tag_time' } } ),
			reviewEntry()
		);
		const card = container.querySelector( '.nx-vc-card.nx-vc-scorecard' );
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( container.querySelector( '.nx-vc-rtext' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-stars' ) ).toBeNull();
		expect( card.textContent.split( '5 days ago' ) ).toHaveLength( 2 );
		expect( text( '.nx-vc-rtitle' ) ).toBe( 'Excellent plugin' );
		expect( text( '.nx-vc-on b' ) ).toBe( 'NotificationX' );
	} );

	it( 'Scorecard hides empty review text and an empty subject', () => {
		renderTheme( scorecard(), reviewEntry( { plugin_review: '', plugin_name: '' } ) );
		expect( container.querySelector( '.nx-vc-rtext' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-on' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-stars' ) ).not.toBeNull();
	} );

	it( 'Counter: icon + name over the lead, the count, the call to action, no time', () => {
		renderTheme( counter(), statsEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-counter' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( false );
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();

		const head = container.querySelector( '.nx-vc-ct-head' );
		expect( head.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( text( '.nx-vc-ct-head .nx-vc-name' ) ).toBe( 'NotificationX' );
		expect( text( '.nx-vc-ct-head .nx-vc-label' ) ).toBe( 'has been downloaded' );
		expect( text( '.nx-vc-hero' ) ).toBe( '2.1K times in last 7 days' );
		expect( text( '.nx-vc-cta' ) ).toBe( 'Get Started for Free.' );
	} );

	it( 'Counter hides an empty call to action', () => {
		renderTheme( counter(), statsEntry( { last_week_text: '' } ) );
		expect( container.querySelector( '.nx-vc-cta' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-hero' ) ).not.toBeNull();
	} );
} );

describe( 'Verified Card Part 2: slim (≤480px)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it( 'Thread line 1: <b>name</b> rule comment, time at the right, credit A', () => {
		renderTheme( thread(), commentEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-thread.nx-vc-slim' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-quote' ) ).toBeNull();
		const inner = container.querySelector( '.nx-vc-l1 > .nx-vc-msg > .nx-vc-msg-in' );
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Emily S.' );
		const rule = inner.querySelector( '.nx-vc-q-rule' );
		expect( rule ).not.toBeNull();
		expect( rule.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		// "commented on" is dropped on phones.
		expect( inner.textContent.replace( /\s+/g, ' ' ) ).toBe( 'Emily S. Tip #3 doubled our newsletter signups in a week, thanks!' );
		expect( text( '.nx-vc-l1 > .nx-vc-slim-time' ) ).toBe( '5 days ago' );
		expect( container.querySelector( '.nx-vc-credit.is-slim' ) ).not.toBeNull();
	} );

	it( 'Scorecard line 1: stars <b>reviewer</b> on <b>subject</b>, time at the right', () => {
		renderTheme( scorecard(), reviewEntry() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-scorecard.nx-vc-slim' ) ).not.toBeNull();
		const inner = container.querySelector( '.nx-vc-msg-in' );
		// The stars come first and sit inside the scrolling line.
		expect( inner.firstElementChild.classList.contains( 'nx-vc-stars' ) ).toBe( true );
		expect( inner.querySelectorAll( '.nx-vc-stars .nx-vc-star.is-on' ) ).toHaveLength( 4 );
		expect( inner.querySelectorAll( '.nx-vc-stars .nx-vc-star.is-part' ) ).toHaveLength( 1 );
		const bold = [ ...inner.querySelectorAll( 'b' ) ].map( ( b ) => b.textContent );
		expect( bold ).toEqual( [ 'jdoe_dev', 'NotificationX' ] );
		expect( inner.querySelector( '.nx-vc-dim' ).textContent ).toBe( 'on' );
		expect( inner.textContent.trim() ).toBe( 'jdoe_dev on NotificationX' );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		// Dropped on phones: the number, the title and the text.
		expect( container.querySelector( '.nx-vc-rnum' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-rtitle' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-rtext' ) ).toBeNull();
	} );

	it( 'Scorecard line 1 on ReviewX shows the product', () => {
		renderTheme(
			base( 'reviewx_vc-scorecard', 'reviewx', 'reviews', { ...SCORECARD, third_param: 'tag_product_post_title' }, { image_shape: 'circle' } ),
			reviewEntry( { title: 'Great fit', post_title: 'Blue T-shirt' } )
		);
		expect( container.querySelector( '.nx-vc-msg-in' ).textContent.trim() ).toBe( 'jdoe_dev on Blue T-shirt' );
	} );

	it( 'Counter line 1: count · <b>name</b> lead, no time', () => {
		renderTheme( counter(), statsEntry() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-counter.nx-vc-slim' ) ).not.toBeNull();
		const inner = container.querySelector( '.nx-vc-msg-in' );
		expect( inner.firstElementChild.classList.contains( 'nx-vc-num' ) ).toBe( true );
		expect( inner.querySelector( '.nx-vc-num' ).textContent ).toBe( '2.1K times in last 7 days' );
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'NotificationX' );
		expect( inner.textContent ).toBe( '2.1K times in last 7 days · NotificationX has been downloaded' );
		expect( container.querySelector( '.nx-vc-slim-time' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-cta' ) ).toBeNull();
	} );
} );

describe( 'Verified Card Part 2: Pro mobile card (is_mobile)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it.each( [
		[ 'Thread', thread, commentEntry, [ 'Emily S. commented on', 'Tip #3 doubled our newsletter signups in a week, thanks!', '5 days ago' ] ],
		[ 'Counter', counter, statsEntry, [ 'NotificationX', 'has been downloaded 2.1K times in last 7 days', 'Get Started for Free.' ] ],
	] )( '%s renders through the generic path with compact rows', ( label, make, entry, rows ) => {
		renderTheme( make(), entry(), { is_mobile: true } );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-credit' ) ).toBeNull();
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps ).toHaveLength( 3 );
		expect( ps.map( ( p ) => p.firstElementChild.textContent ) ).toEqual( rows );
		expect( ps[ 2 ].querySelector( '.nx-branding .nx-byline' ).textContent ).toBe( 'by' );
	} );

	it( 'Scorecard renders the rating row with the generic stars', () => {
		renderTheme( scorecard(), reviewEntry(), { is_mobile: true } );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps ).toHaveLength( 3 );
		expect( ps[ 0 ].firstElementChild.textContent ).toBe( 'jdoe_dev on NotificationX' );
		// Content.tsx turns 'rating::4.4' into the shared Star renderer.
		expect( ps[ 1 ].querySelectorAll( 'svg' ) ).toHaveLength( 5 );
		expect( ps[ 1 ].textContent ).not.toContain( 'rating::' );
		expect( ps[ 2 ].firstElementChild.textContent ).toBe( '5 days ago' );
	} );
} );

describe( 'Verified Card Part 3: desktop / tablet', () => {
	beforeEach( () => stubMatchMedia( false ) );

	it( 'Pledge: avatar with a heart badge, name, "donated $50.00 for", the campaign strip, time', () => {
		renderTheme( pledge(), donationEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-pledge' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( true );
		expect( container.querySelector( '.notificationx-content' ) ).toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		const wrap = container.querySelector( '.nx-vc-pl-head .nx-vc-avwrap' );
		expect( wrap.querySelector( '.notificationx-image' ) ).not.toBeNull();
		expect( wrap.querySelector( '.nx-vc-badge svg' ) ).not.toBeNull();
		expect( text( '.nx-vc-pl-head .nx-vc-name' ) ).toBe( 'Emily S.' );
		expect( text( '.nx-vc-pl-head .nx-vc-body' ) ).toBe( 'donated $50.00 for' );
		expect( text( '.nx-vc-amt' ) ).toBe( '$50.00 for' );
		expect( text( '.nx-vc-strip' ) ).toBe( 'Fundraising Camp for Health' );
		expect( container.querySelector( '.nx-vc-strip .nx-vc-strip-ic' ) ).not.toBeNull();
	} );

	it( 'Pledge with the amount set to None, and without an image', () => {
		renderTheme( pledge( { 'notification-template': { ...DONATION, third_param: 'tag_none' } } ), donationEntry( { image_data: false } ) );
		expect( container.querySelector( '.nx-vc-amt' ) ).toBeNull();
		expect( text( '.nx-vc-pl-head .nx-vc-body' ) ).toBe( 'donated' );
		// No image: no badge either.
		expect( container.querySelector( '.nx-vc-avwrap' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-badge' ) ).toBeNull();
	} );

	it( 'Enrolment: name + action with the time, the course panel', () => {
		renderTheme( enrol(), courseEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-enrol' );
		expect( card ).not.toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		expect( text( '.nx-vc-en-who b' ) ).toBe( 'John D.' );
		expect( text( '.nx-vc-en-who' ) ).toBe( 'John D. just enrolled' );
		expect( container.querySelector( '.nx-vc-en-who' ).classList.contains( 'nx-vc-rsv' ) ).toBe( true );
		const panel = container.querySelector( '.nx-vc-panel.nx-vc-en-panel' );
		expect( panel.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( text( '.nx-vc-en-course' ) ).toBe( 'PHP Beginners – Become a PHP Master' );
	} );

	it( 'Submission: avatar + submitter, the lead, the form in a field box', () => {
		renderTheme( submission(), formEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-submission' );
		expect( card ).not.toBeNull();
		expect( text( '.nx-vc-time' ) ).toBe( '5 days ago' );
		const head = container.querySelector( '.nx-vc-fm-head' );
		expect( head.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( text( '.nx-vc-fm-head .nx-vc-name' ) ).toBe( 'Emily S.' );
		expect( text( '.nx-vc-fm-lead' ) ).toBe( 'recently contacted via' );
		expect( text( '.nx-vc-field' ) ).toBe( 'Support Us Form' );
		expect( container.querySelector( '.nx-vc-field .nx-vc-field-ic' ) ).not.toBeNull();
	} );

	it( 'Ratings: count + label | five stars, icon + subject; no time', () => {
		renderTheme( ratings(), ratedEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-ratings' );
		expect( card ).not.toBeNull();
		expect( card.classList.contains( 'has-time' ) ).toBe( false );
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( text( '.nx-vc-rt-left .nx-vc-rt-hero' ) ).toBe( '435' );
		expect( text( '.nx-vc-rt-left .nx-vc-label' ) ).toBe( 'people rated' );
		// Always five stars: the count is of 5-star ratings (the entry's own rating is ignored).
		const stars = container.querySelector( '.nx-vc-rt-right .nx-vc-stars' );
		expect( stars.querySelectorAll( '.nx-vc-star.is-on' ) ).toHaveLength( 5 );
		expect( stars.getAttribute( 'aria-label' ) ).toBe( '5.0 out of 5' );
		const subject = container.querySelector( '.nx-vc-rt-subject' );
		expect( subject.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( text( '.nx-vc-rt-subject .nx-vc-name' ) ).toBe( 'NotificationX' );
		expect( card.textContent ).not.toContain( 'rating::' );
	} );

	it( 'Ratings without a count keeps the stars and subject', () => {
		renderTheme( ratings(), ratedEntry( { rated: '' } ) );
		expect( container.querySelector( '.nx-vc-rt-hero' ) ).toBeNull();
		expect( text( '.nx-vc-rt-left .nx-vc-label' ) ).toBe( 'people rated' );
		expect( container.querySelectorAll( '.nx-vc-stars .nx-vc-star.is-on' ) ).toHaveLength( 5 );
	} );

	it( 'Installs: the count tile, the lead, icon + name; no time', () => {
		renderTheme( installs(), installsEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-installs' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( text( '.nx-vc-is-main > .nx-vc-numtile' ) ).toBe( '40K' );
		expect( text( '.nx-vc-is-main .nx-vc-label' ) ).toBe( 'people are actively using' );
		const name = container.querySelector( '.nx-vc-is-name' );
		expect( name.firstElementChild.classList.contains( 'notificationx-image' ) ).toBe( true );
		expect( text( '.nx-vc-is-name .nx-vc-name' ) ).toBe( 'Essential Addons for Elementor' );
	} );

	it( 'escapes entry values in the new cards', () => {
		renderTheme( pledge(), donationEntry( { title: '<img src=x onerror=alert(1)>' } ) );
		expect( container.querySelector( '.nx-vc-strip img' ) ).toBeNull();
		expect( text( '.nx-vc-strip' ) ).toBe( '<img src=x onerror=alert(1)>' );
	} );
} );

describe( 'Verified Card Part 3: slim (≤480px)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	const line = () => container.querySelector( '.nx-vc-l1 > .nx-vc-msg > .nx-vc-msg-in' );

	it( 'Pledge line 1: <b>donor</b> donated <amt>$50.00 for</amt> <b>campaign</b>, time', () => {
		renderTheme( pledge(), donationEntry() );
		expect( container.querySelector( '.nx-vc-card.nx-vc-pledge.nx-vc-slim' ) ).not.toBeNull();
		expect( line().textContent ).toBe( 'Emily S. donated $50.00 for Fundraising Camp for Health' );
		expect( [ ...line().querySelectorAll( 'b' ) ].map( ( b ) => b.textContent ) ).toEqual( [ 'Emily S.', 'Fundraising Camp for Health' ] );
		expect( line().querySelector( '.nx-vc-amt' ).textContent ).toBe( '$50.00 for' );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		expect( container.querySelector( '.nx-vc-badge' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-strip' ) ).toBeNull();
	} );

	it( 'Enrolment line 1: <b>name</b> just enrolled <em-a>course</em-a>, time', () => {
		renderTheme( enrol(), courseEntry() );
		expect( line().textContent ).toBe( 'John D. just enrolled PHP Beginners – Become a PHP Master' );
		expect( line().querySelector( '.nx-vc-em-a' ).textContent ).toBe( 'PHP Beginners – Become a PHP Master' );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		expect( container.querySelector( '.nx-vc-en-panel' ) ).toBeNull();
	} );

	it( 'Submission line 1: <b>name</b> recently contacted via ✈ <em-a>form</em-a>, time', () => {
		renderTheme( submission(), formEntry() );
		expect( line().textContent ).toBe( 'Emily S. recently contacted via Support Us Form' );
		const icon = line().querySelector( 'svg.nx-vc-ic-in' );
		expect( icon ).not.toBeNull();
		expect( icon.nextElementSibling.classList.contains( 'nx-vc-em-a' ) ).toBe( true );
		expect( text( '.nx-vc-slim-time' ) ).toBe( '5 days ago' );
		expect( container.querySelector( '.nx-vc-field' ) ).toBeNull();
	} );

	it( 'Ratings line 1: five stars, count, label, <b>subject</b>; no time', () => {
		renderTheme( ratings(), ratedEntry() );
		expect( line().firstElementChild.classList.contains( 'nx-vc-stars' ) ).toBe( true );
		expect( line().querySelectorAll( '.nx-vc-stars .nx-vc-star.is-on' ) ).toHaveLength( 5 );
		expect( line().querySelector( '.nx-vc-num' ).textContent ).toBe( '435' );
		expect( line().querySelector( '.nx-vc-dim' ).textContent ).toBe( 'people rated' );
		expect( line().querySelector( 'b' ).textContent ).toBe( 'NotificationX' );
		expect( line().textContent.trim() ).toBe( '435 people rated NotificationX' );
		expect( container.querySelector( '.nx-vc-slim-time' ) ).toBeNull();
	} );

	it( 'Installs line 1: count, lead, <b>name</b>; no time', () => {
		renderTheme( installs(), installsEntry() );
		expect( line().firstElementChild.classList.contains( 'nx-vc-num' ) ).toBe( true );
		expect( line().textContent ).toBe( '40K people are actively using Essential Addons for Elementor' );
		expect( container.querySelector( '.nx-vc-slim-time' ) ).toBeNull();
		expect( container.querySelector( '.nx-vc-numtile' ) ).toBeNull();
	} );
} );

describe( 'Verified Card Part 3: Pro mobile card (is_mobile)', () => {
	beforeEach( () => stubMatchMedia( true ) );

	it.each( [
		[ 'Pledge', pledge, donationEntry, [ 'Emily S. donated $50.00 for', 'Fundraising Camp for Health', '5 days ago' ] ],
		[ 'Enrolment', enrol, courseEntry, [ 'John D. just enrolled', 'PHP Beginners – Become a PHP Master', '5 days ago' ] ],
		[ 'Submission', submission, formEntry, [ 'Emily S. recently contacted via', 'Support Us Form', '5 days ago' ] ],
		[ 'Installs', installs, installsEntry, [ '40K people are actively using', 'Essential Addons for Elementor' ] ],
	] )( '%s renders through the generic path with compact rows', ( label, make, entry, rows ) => {
		renderTheme( make(), entry(), { is_mobile: true } );
		expect( container.querySelector( '.nx-vc-card' ) ).toBeNull();
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps.slice( 0, rows.length ).map( ( p ) => p.firstElementChild.textContent ) ).toEqual( rows );
		expect( ps[ ps.length - 1 ].querySelector( '.nx-branding .nx-byline' ).textContent ).toBe( 'by' );
	} );

	it( 'Ratings shows five generic stars in its third row', () => {
		renderTheme( ratings(), ratedEntry(), { is_mobile: true } );
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps ).toHaveLength( 3 );
		expect( ps[ 0 ].firstElementChild.textContent ).toBe( '435 people rated' );
		expect( ps[ 1 ].firstElementChild.textContent ).toBe( 'NotificationX' );
		// The shared Star renderer (viewBox 0 0 511 510); the row also carries the "by" branding.
		expect( ps[ 2 ].querySelectorAll( 'svg[viewBox="0 0 511 510"]' ) ).toHaveLength( 5 );
		expect( ps[ 2 ].textContent ).not.toContain( 'rating::' );
	} );
} );

describe( 'Verified Card Part 3: partial stars and Google Ratings', () => {
	const fills = ( root ) =>
		[ ...root.querySelectorAll( '.nx-vc-star' ) ].map( ( star ) => {
			const fg = star.querySelector( '.nx-vc-star-fg' );
			return fg ? fg.style.width : '0';
		} );

	it( 'vcStarFill gives each star its share of the rating, not rounded', () => {
		const { vcStarFill } = require( '../../../nxdev/notificationx/frontend/themes/vc/VcStars' );
		expect( [ 1, 2, 3, 4, 5 ].map( ( i ) => vcStarFill( 4.7, i ) ) ).toEqual( [ 100, 100, 100, 100, 70 ] );
		expect( [ 1, 2, 3, 4, 5 ].map( ( i ) => vcStarFill( 4.5, i ) ) ).toEqual( [ 100, 100, 100, 100, 50 ] );
		expect( [ 1, 2, 3, 4, 5 ].map( ( i ) => vcStarFill( 2.25, i ) ) ).toEqual( [ 100, 100, 25, 0, 0 ] );
		expect( [ 1, 2, 3, 4, 5 ].map( ( i ) => vcStarFill( 5, i ) ) ).toEqual( [ 100, 100, 100, 100, 100 ] );
		expect( [ 1, 2, 3, 4, 5 ].map( ( i ) => vcStarFill( 0, i ) ) ).toEqual( [ 0, 0, 0, 0, 0 ] );
	} );

	it( 'Scorecard 4.5 shows four and a half stars, label "4.5 out of 5"', () => {
		stubMatchMedia( false );
		renderTheme( scorecard(), reviewEntry( { rating: '4.5' } ) );
		const stars = container.querySelector( '.nx-vc-rate .nx-vc-stars' );
		expect( fills( stars ) ).toEqual( [ '100%', '100%', '100%', '100%', '50%' ] );
		expect( stars.getAttribute( 'aria-label' ) ).toBe( '4.5 out of 5' );
		expect( text( '.nx-vc-rnum' ) ).toBe( '4.5' );
		// Each star is a grey star with an amber copy on top; the copies are hidden from screen readers.
		expect( stars.querySelectorAll( '.nx-vc-star-bg svg[aria-hidden="true"]' ) ).toHaveLength( 5 );
	} );

	it( 'Google Ratings desktop: the average over "N reviews", stars filled to the average', () => {
		stubMatchMedia( false );
		renderTheme( googleRatings(), googleEntry() );
		const card = container.querySelector( '.nx-vc-card.nx-vc-ratings.is-average' );
		expect( card ).not.toBeNull();
		expect( container.querySelector( '.nx-vc-time' ) ).toBeNull();
		expect( text( '.nx-vc-rt-left .nx-vc-rt-hero' ) ).toBe( '4.7' );
		expect( text( '.nx-vc-rt-left .nx-vc-label' ) ).toBe( '1284 reviews' );
		const stars = container.querySelector( '.nx-vc-rt-right .nx-vc-stars' );
		expect( fills( stars ) ).toEqual( [ '100%', '100%', '100%', '100%', '70%' ] );
		expect( stars.getAttribute( 'aria-label' ) ).toBe( '4.7 out of 5' );
		expect( text( '.nx-vc-rt-subject .nx-vc-name' ) ).toBe( 'Blue Bottle Coffee – Hayes Valley' );
		expect( card.textContent ).not.toContain( 'rating::' );
	} );

	it( 'Google Ratings slim line 1: partial stars, the average, "N reviews", <b>place</b>', () => {
		stubMatchMedia( true );
		renderTheme( googleRatings(), googleEntry() );
		const inner = container.querySelector( '.nx-vc-msg-in' );
		expect( fills( inner.querySelector( '.nx-vc-stars' ) ) ).toEqual( [ '100%', '100%', '100%', '100%', '70%' ] );
		expect( inner.querySelector( '.nx-vc-num' ).textContent ).toBe( '4.7' );
		expect( inner.querySelector( '.nx-vc-dim' ).textContent ).toBe( '1284 reviews' );
		expect( inner.querySelector( 'b' ).textContent ).toBe( 'Blue Bottle Coffee – Hayes Valley' );
		expect( inner.textContent.trim() ).toBe( '4.7 1284 reviews Blue Bottle Coffee – Hayes Valley' );
		expect( container.querySelector( '.nx-vc-slim-time' ) ).toBeNull();
	} );

	it( 'other Ratings sources ignore a rating and keep five fixed stars', () => {
		stubMatchMedia( false );
		// A leftover fourth_param on wp_reviews: the slot is empty, so no average.
		renderTheme( ratings( { 'notification-template': { ...RATINGS, fourth_param: 'tag_rating' } } ), ratedEntry( { rating: '3' } ) );
		expect( container.querySelector( '.nx-vc-ratings.is-average' ) ).toBeNull();
		expect( text( '.nx-vc-rt-hero' ) ).toBe( '435' );
		expect( fills( container.querySelector( '.nx-vc-stars' ) ) ).toEqual( [ '100%', '100%', '100%', '100%', '100%' ] );
	} );

	it( 'Google Ratings on the Pro mobile card: the generic stars show the average', () => {
		stubMatchMedia( true );
		renderTheme( googleRatings(), googleEntry(), { is_mobile: true } );
		const ps = [ ...container.querySelectorAll( '.notificationx-content p' ) ];
		expect( ps[ 0 ].firstElementChild.textContent ).toBe( '1284 reviews' );
		expect( ps[ 1 ].firstElementChild.textContent ).toBe( 'Blue Bottle Coffee – Hayes Valley' );
		expect( ps[ 2 ].querySelectorAll( 'svg[viewBox="0 0 511 510"]' ) ).toHaveLength( 5 );
		expect( ps[ 2 ].textContent ).not.toContain( 'rating::' );
	} );
} );
