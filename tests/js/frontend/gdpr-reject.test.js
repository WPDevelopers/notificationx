import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import GdprActions from '../../../nxdev/notificationx/frontend/gdpr/utils/GdprActions';
import { NotificationProvider } from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { requestCookieDeletion } from '../../../nxdev/notificationx/frontend/gdpr/utils/helper';

jest.mock( '../../../nxdev/notificationx/frontend/gdpr/utils/helper', () => ( {
	...jest.requireActual( '../../../nxdev/notificationx/frontend/gdpr/utils/helper' ),
	requestCookieDeletion: jest.fn( () => Promise.resolve() ),
	handleConsentAPI: jest.fn(),
} ) );

/**
 * The cookie notice's "Reject" with cookie removal on also forgets the
 * visitor's remembered form submits ("After a form submit"), as the server
 * deletes the close cookies. Without cookie removal nothing is cleared.
 */

const ENTRY = { storage_scope: 'a1b2c3d4e5', rest: { root: 'https://shop.example/wp-json/' } };
const OWN = 'notificationx_submitted_a1b2c3d4e5_12';
const OTHER_SITE = 'notificationx_submitted_ffffffffff_12';

let container;

beforeEach( () => {
	localStorage.clear();
	localStorage.setItem( OWN, JSON.stringify( { v: 1, at: Date.now() } ) );
	localStorage.setItem( OTHER_SITE, JSON.stringify( { v: 1, at: Date.now() } ) );
	requestCookieDeletion.mockClear();
	container = document.createElement( 'div' );
	document.body.appendChild( container );
} );

afterEach( () => {
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
} );

const reject = ( settings ) => {
	act( () => {
		ReactDOM.render(
			<NotificationProvider value={ ENTRY }>
				<GdprActions settings={ { theme: 'gdpr_theme-light-two', ...settings } } onConsentGiven={ () => {} } setIsVisible={ () => {} } />
			</NotificationProvider>,
			container
		);
	} );
	act( () => {
		container.querySelector( '.btn-danger' ).click();
	} );
};

it( 'with cookie removal on, clears this site\'s submitted keys along with the cookies', () => {
	reject( { gdpr_cookie_removal: true } );
	expect( requestCookieDeletion ).toHaveBeenCalledTimes( 1 );
	expect( localStorage.getItem( OWN ) ).toBeNull();
	expect( localStorage.getItem( OTHER_SITE ) ).not.toBeNull();
} );

it( 'with cookie removal off, keeps them, as it keeps the cookies', () => {
	reject( { gdpr_cookie_removal: false } );
	expect( requestCookieDeletion ).not.toHaveBeenCalled();
	expect( localStorage.getItem( OWN ) ).not.toBeNull();
} );
