import { analyticsOnClick, recordAnalyticsClick, resolveNotificationLink } from '../../../nxdev/notificationx/frontend/core/Analytics';
import { installHooks } from '../helpers';

const data = { link: 'https://example.com/entry' };

describe( 'resolveNotificationLink / nx_frontend_no_entry_link_types', () => {
	it.each( [ 'none', 'yt_channel_link' ] )(
		'returns null for the built-in link type %s',
		( link_type ) => {
			expect( resolveNotificationLink( { link_type }, data ) ).toBeNull();
		}
	);

	it( 'treats announcements_link as a plain link until an add-on registers it', () => {
		expect( resolveNotificationLink( { link_type: 'announcements_link' }, data ) ).toBe( data.link );
	} );

	it( 'returns the entry link for other link types', () => {
		expect( resolveNotificationLink( { link_type: 'product_page' }, data ) ).toBe( data.link );
	} );

	it( 'returns null for a link type an add-on registers', () => {
		installHooks().addFilter( 'nx_frontend_no_entry_link_types', 'test', ( types ) => [ ...types, 'my_link' ] );
		expect( resolveNotificationLink( { link_type: 'my_link' }, data ) ).toBeNull();
		expect( resolveNotificationLink( { link_type: 'yt_channel_link' }, data ) ).toBeNull();
	} );

	it( 'keeps the built-in list when a filter returns garbage', () => {
		installHooks().addFilter( 'nx_frontend_no_entry_link_types', 'test', () => 'none' );
		expect( resolveNotificationLink( { link_type: 'yt_channel_link' }, data ) ).toBeNull();
		expect( resolveNotificationLink( { link_type: 'product_page' }, data ) ).toBe( data.link );
	} );
} );

describe( 'preview clicks are never counted', () => {
	const restUrl = 'http://example.com/wp-json/notificationx/v1/analytics/?frontend=true';
	const click = () => ( { target: { href: 'https://example.com/product/' }, preventDefault: jest.fn() } );
	let fetchMock;

	beforeEach( () => {
		fetchMock = jest.fn( () => Promise.resolve( { json: () => ( {} ) } ) );
		global.fetch = fetchMock;
		window.notificationXArr = [ {} ];
	} );
	afterEach( () => {
		delete global.fetch;
		delete window.notificationXArr;
	} );

	it( 'a live click with analytics on is posted', async () => {
		analyticsOnClick( click(), restUrl, { nx_id: 12, enable_analytics: true }, jest.fn() );
		await recordAnalyticsClick( restUrl, { nx_id: 12, enable_analytics: true } );
		expect( fetchMock ).toHaveBeenCalledTimes( 2 );
		expect( fetchMock.mock.calls[ 0 ][ 0 ] ).toBe( restUrl );
	} );

	it( 'a preview click posts nothing, even with analytics on', async () => {
		const dispatch = jest.fn();
		const config = { nx_id: 98765, enable_analytics: true, is_preview: true };
		expect( analyticsOnClick( click(), restUrl, config, dispatch ) ).toBeUndefined();
		await recordAnalyticsClick( restUrl, config );
		expect( fetchMock ).not.toHaveBeenCalled();
	} );

	it( 'a preview click still closes the notification, as with analytics off', () => {
		const offDispatch = jest.fn();
		const previewDispatch = jest.fn();
		analyticsOnClick( click(), restUrl, { nx_id: 5, enable_analytics: false }, offDispatch );
		analyticsOnClick( click(), restUrl, { nx_id: 5, enable_analytics: true, is_preview: true }, previewDispatch );
		expect( previewDispatch.mock.calls ).toEqual( offDispatch.mock.calls );
		expect( fetchMock ).not.toHaveBeenCalled();
	} );
} );
