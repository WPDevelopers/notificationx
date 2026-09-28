/**
 * shared/helpers.ts is used by both the admin app and the frontend bundle.
 * Admin modules re-export from it, so behaviour must be identical to the
 * definitions that used to live in core/functions.ts and core/constants.ts.
 */
import { getIconUrl, themes_has_bg, modalStyle } from '../../nxdev/notificationx/shared/helpers';
import * as adminConstants from '../../nxdev/notificationx/core/constants';

describe( 'getIconUrl', () => {
	it( 'returns an empty string for empty values', () => {
		expect( getIconUrl( '' ) ).toBe( '' );
		expect( getIconUrl( null ) ).toBe( '' );
		expect( getIconUrl( undefined ) ).toBe( '' );
	} );

	it( 'returns absolute and data URLs unchanged', () => {
		expect( getIconUrl( 'https://cdn.example.com/a.svg' ) ).toBe( 'https://cdn.example.com/a.svg' );
		expect( getIconUrl( 'http://example.com/a.png', 'https://ignored/' ) ).toBe( 'http://example.com/a.png' );
		expect( getIconUrl( 'data:image/svg+xml;base64,AAA' ) ).toBe( 'data:image/svg+xml;base64,AAA' );
	} );

	// Characterization test: the moved code drops a slash here ("adminimages").
	// No caller passes a prefix today, so the branch is unused; fixing it is
	// tracked separately and out of scope for a no-behaviour-change move.
	it( 'rewrites a wp-admin prefix (current, known-quirky output)', () => {
		expect( getIconUrl( 'cart.svg', 'https://site.test/wp-admin/images/icons/' ) ).toBe(
			'https://site.test/wp-content/plugins/notificationx/assets/adminimages/icons/cart.svg'
		);
	} );

	it( 'uses a non-admin prefix as given', () => {
		expect( getIconUrl( 'cart.svg', 'https://cdn.test/icons/' ) ).toBe( 'https://cdn.test/icons/cart.svg' );
	} );

	it( 'defaults to the plugin icons folder on the current origin', () => {
		expect( getIconUrl( 'cart.svg' ) ).toBe(
			window.location.origin + '/wp-content/plugins/notificationx/assets/admin/images/icons/cart.svg'
		);
	} );
} );

describe( 'themes_has_bg', () => {
	it( 'lists the press bar themes that carry a background image', () => {
		expect( themes_has_bg ).toEqual( [ 'press_bar_theme-four', 'press_bar_theme-five' ] );
	} );
} );

describe( 'modalStyle', () => {
	it( 'keeps the overlay and content styles the GDPR and admin modals rely on', () => {
		expect( modalStyle ).toEqual( {
			overlay: {
				position: 'fixed',
				display: 'flex',
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				backgroundColor: 'rgba(3, 6, 60, 0.7)',
				zIndex: 9999999,
				padding: '60px 15px',
			},
			content: {
				position: 'static',
				width: '900px',
				margin: 'auto',
				border: '0px solid #5414D0',
				overflow: 'auto',
				WebkitOverflowScrolling: 'touch',
				borderRadius: '4px',
				outline: 'none',
				padding: '15px',
			},
		} );
	} );

	it( 'is the same object the admin constants module exports', () => {
		expect( adminConstants.modalStyle ).toBe( modalStyle );
	} );
} );
