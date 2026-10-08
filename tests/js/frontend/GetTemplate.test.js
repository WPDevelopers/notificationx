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
	// Free 3.3.4 has no layout for the Discount Alert themes or Cart Peek:
	// NotificationX Pro claims them. Without a filter they get the generic
	// fallback rows, never the old Pro-only layouts.
	it( 'has no built-in announcements layout', () => {
		const spy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		expect( GetTemplate( announcement ) ).toEqual( [
			'<span>{{title}}</span> <span>Second</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
		spy.mockRestore();
	} );

	it( 'has no built-in Cart Peek layout', () => {
		// conv-theme-fourteen is a Sales layout: count + label + product, then time.
		expect( GetTemplate( cartPeek ) ).toEqual( [
			'<span>{{title}}</span> <span>Second</span> <span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );

	it( 'uses the rows an add-on returns for Cart Peek', () => {
		installHooks().addFilter( 'nx_frontend_template', 'test', ( value, settings, params ) =>
			settings.source === 'woocommerce_cart_peek' ? [ params.first_param, params.third_param ] : value
		);
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
		expect( GetTemplate( sales ) ).toEqual( [
			'<span>{{title}}</span> <span>Second</span>',
			'<span>{{offer}}</span>',
			'<span>{{time}}</span>',
		] );
	} );
} );
