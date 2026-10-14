import { resolveNotificationLink } from '../../../nxdev/notificationx/frontend/core/Analytics';
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
