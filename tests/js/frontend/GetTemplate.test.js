import GetTemplate from '../../../nxdev/notificationx/frontend/themes/GetTemplate';
import { installHooks } from '../helpers';

const template = {
	first_param: 'tag_title',
	second_param: 'Second',
	third_param: 'tag_offer',
	fourth_param: 'tag_time',
};

const announcement = { themes: 'announcements_theme-1', source: 'announcements', 'notification-template': template };
const cartPeek = { themes: 'woocommerce_cart_peek_conv-theme-fourteen', source: 'woocommerce_cart_peek', 'notification-template': template };
const sales = { themes: 'woocommerce_sales_theme-one', source: 'woocommerce_sales', 'notification-template': template };

describe( 'GetTemplate / nx_frontend_template', () => {
	it( 'keeps the built-in announcements layout when no filter claims it', () => {
		expect( GetTemplate( announcement ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'keeps the built-in Cart Peek layout when no filter claims it', () => {
		expect( GetTemplate( cartPeek ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
		] );
	} );

	it( 'uses the rows an add-on returns, and passes settings and the prepared params', () => {
		const filter = jest.fn( ( value, settings, params ) =>
			settings.source === 'announcements' ? [ `claimed ${ params.first_param }` ] : value
		);
		installHooks().addFilter( 'nx_frontend_template', 'test', filter );

		expect( GetTemplate( announcement ) ).toEqual( [ 'claimed <span>{{title}}</span>' ] );
		expect( filter.mock.calls[ 0 ][ 0 ] ).toBeUndefined();
		expect( filter.mock.calls[ 0 ][ 1 ] ).toBe( announcement );
	} );

	it( 'leaves notifications the add-on passes through untouched', () => {
		installHooks().addFilter( 'nx_frontend_template', 'test', ( value, settings ) =>
			settings.source === 'announcements' ? [ 'claimed' ] : value
		);
		expect( GetTemplate( sales ) ).toEqual( [
			'<span>{{title}}</span> <span>Second</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'ignores a non-array return and renders the built-in layout', () => {
		installHooks().addFilter( 'nx_frontend_template', 'test', () => 'not an array' );
		expect( GetTemplate( cartPeek ) ).toEqual( [
			'<span>{{title}}</span>',
			'<span>{{offer}}</span>',
		] );
	} );
} );
