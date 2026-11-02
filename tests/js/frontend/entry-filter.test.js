import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import NotificationContainer from '../../../nxdev/notificationx/frontend/core/NotificationContainer';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { installHooks } from '../helpers';

const config = ( nx_id, extra = {} ) => ( {
	nx_id,
	type: 'page_analytics',
	source: 'live_visitors',
	themes: 'page_analytics_pa-theme-three',
	position: 'bottom_left',
	display_for: 5,
	animation_notification_hide: 'default',
	link_type: 'none',
	template: [ '{{views}} people looking', '{{title}}' ],
	...extra,
} );

const notice = ( nx_id, data ) => ( { id: `n${ nx_id }`, config: config( nx_id ), data } );

let container;
beforeEach( () => {
	jest.useFakeTimers( 'modern' );
	// Printed by FrontEnd::footer_scripts() on every page that has notices.
	window.notificationXArr = [ {} ];
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );
afterEach( () => {
	ReactDOM.unmountComponentAtNode( container );
	container.remove();
	delete window.notificationXArr;
	jest.useRealTimers();
} );

const render = ( notices ) => {
	const context = {
		rest: { root: 'https://example.com/wp-json/', namespace: 'notificationx/v1', omit_credentials: true },
		state: { is_pro: true },
		assets: {},
		dispatch: jest.fn(),
		getTime: () => ( { fromNow: () => 'a few seconds ago' } ),
		getNxToRender: ( callback ) => [ callback( 'bottom_left', notices ) ],
	};
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ context }>
				<NotificationContainer />
			</NotificationProvider>,
			container
		);
	} );
};

const text = () => container.textContent;
const cards = () => container.querySelectorAll( '.notification-item' );

describe( 'nx_frontend_entry', () => {
	it( 'leaves notices unchanged without a filter', () => {
		render( [ notice( 1, { views: 7, title: 'My Shop' } ) ] );
		expect( cards() ).toHaveLength( 1 );
		expect( text() ).toContain( '7 people looking' );
	} );

	it( 'passes the entry and the notification settings to the filter', () => {
		const filter = jest.fn( ( data ) => data );
		installHooks().addFilter( 'nx_frontend_entry', 'test', filter );
		render( [ notice( 1, { views: 7, title: 'My Shop' } ) ] );
		expect( filter ).toHaveBeenCalled();
		const [ data, args ] = filter.mock.calls[ 0 ];
		expect( data ).toEqual( { views: 7, title: 'My Shop' } );
		expect( args.settings.nx_id ).toBe( 1 );
		expect( text() ).toContain( '7 people looking' );
	} );

	it( 'renders the entry a filter returns', () => {
		installHooks().addFilter( 'nx_frontend_entry', 'test', ( data, { settings } ) =>
			settings.source === 'live_visitors' ? { ...data, views: 12 } : data
		);
		render( [ notice( 1, { views: 0, title: 'My Shop' } ) ] );
		expect( text() ).toContain( '12 people looking' );
		expect( text() ).not.toContain( '0 people looking' );
	} );

	it( 'holds back only the notice a filter returns null for', () => {
		installHooks().addFilter( 'nx_frontend_entry', 'test', ( data, { settings } ) =>
			settings.nx_id === 1 ? null : data
		);
		render( [ notice( 1, { views: 0, title: 'Held' } ), notice( 2, { views: 3, title: 'Shown' } ) ] );
		expect( cards() ).toHaveLength( 1 );
		expect( text() ).toContain( 'Shown' );
		expect( text() ).not.toContain( 'Held' );
	} );

	it.each( [ [ 'undefined', undefined ], [ 'a string', 'oops' ], [ 'a number', 5 ], [ 'false', false ] ] )(
		'ignores a filter that returns %s',
		( label, value ) => {
			installHooks().addFilter( 'nx_frontend_entry', 'test', () => value );
			render( [ notice( 1, { views: 7, title: 'My Shop' } ) ] );
			expect( cards() ).toHaveLength( 1 );
			expect( text() ).toContain( '7 people looking' );
		}
	);

	it( 'ignores a filter that throws', () => {
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		installHooks().addFilter( 'nx_frontend_entry', 'test', () => {
			throw new Error( 'broken add-on' );
		} );
		render( [ notice( 1, { views: 7, title: 'My Shop' } ) ] );
		expect( text() ).toContain( '7 people looking' );
		console.error.mockRestore();
	} );

	it( 're-runs the filter on the notificationx:refresh event', () => {
		let count = null;
		installHooks().addFilter( 'nx_frontend_entry', 'test', ( data ) =>
			count === null ? null : { ...data, views: count }
		);
		render( [ notice( 1, { views: 0, title: 'My Shop' } ) ] );
		expect( cards() ).toHaveLength( 0 );

		count = 4;
		act( () => {
			window.dispatchEvent( new Event( 'notificationx:refresh' ) );
		} );
		expect( cards() ).toHaveLength( 1 );
		expect( text() ).toContain( '4 people looking' );

		count = 9;
		act( () => {
			window.dispatchEvent( new Event( 'notificationx:refresh' ) );
		} );
		expect( cards() ).toHaveLength( 1 );
		expect( text() ).toContain( '9 people looking' );
	} );

	it( 'stops listening for refresh once unmounted', () => {
		const filter = jest.fn( ( data ) => data );
		installHooks().addFilter( 'nx_frontend_entry', 'test', filter );
		render( [ notice( 1, { views: 7, title: 'My Shop' } ) ] );
		ReactDOM.unmountComponentAtNode( container );
		filter.mockClear();
		act( () => {
			window.dispatchEvent( new Event( 'notificationx:refresh' ) );
		} );
		expect( filter ).not.toHaveBeenCalled();
	} );
} );
