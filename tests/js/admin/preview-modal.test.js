/**
 * Builder Preview popup: today's request and top bar unchanged, plus the page
 * picker and "Open in new tab" (layout A).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import { fromBase64 } from 'js-base64';

jest.mock( 'quickbuilder', () => ( { useBuilderContext: () => mockBuilder } ) );
jest.mock( '../../../nxdev/notificationx/hooks', () => ( { useNotificationXContext: () => ( {} ) } ) );
jest.mock( '@wordpress/components', () => {
	const mockReact = require( 'react' );
	return { Button: ( { children, ...rest } ) => mockReact.createElement( 'button', rest, children ) };
} );
jest.mock( '../../../nxdev/notificationx/icons/responsive/desktop.svg', () => ( { ReactComponent: () => null } ) );
jest.mock( '../../../nxdev/notificationx/icons/responsive/tablet.svg', () => ( { ReactComponent: () => null } ) );
jest.mock( '../../../nxdev/notificationx/icons/responsive/mobile.svg', () => ( { ReactComponent: () => null } ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// eslint-disable-next-line import/first
import apiFetch from '@wordpress/api-fetch';
// eslint-disable-next-line import/first
import ReactModal from 'react-modal';
// eslint-disable-next-line import/first
import { addFilter, removeAllFilters } from '@wordpress/hooks';
// eslint-disable-next-line import/first
import PreviewModal from '../../../nxdev/notificationx/fields/PreviewModal';
// eslint-disable-next-line import/first
import { createForm, previewPayload, resetFrameName } from '../../../nxdev/notificationx/fields/preview/form';
// eslint-disable-next-line import/first
import { popupWidth, toolbarSize } from '../../../nxdev/notificationx/fields/preview/size';
// eslint-disable-next-line import/first
import { groupPages, pagesPath, SEARCH_DELAY, statusBadges } from '../../../nxdev/notificationx/fields/preview/PagePicker';

let mockBuilder;
let container;
let submitted;

const HOME = 'http://storefy.local/';
const urls = { default: HOME, woocommerce_sales_inline: 'http://storefy.local/product/honey-oat-bread/' };
const page = ( id, extra = {} ) => ( {
	id,
	title: `Page ${ id }`,
	type: 'page',
	type_label: 'Pages',
	status: 'publish',
	password: false,
	public: true,
	front: false,
	url: `http://storefy.local/page-${ id }/`,
	...extra,
} );

beforeEach( () => {
	jest.useFakeTimers();
	mockBuilder = { values: { source: 'woocommerce', type: 'conversions', themes: 'theme-one', id: 14 } };
	window.notificationxTabs = { preview_nonce: 'n0nce' };
	submitted = [];
	jest.spyOn( HTMLFormElement.prototype, 'submit' ).mockImplementation( function () {
		submitted.push( {
			action: this.getAttribute( 'action' ),
			method: this.getAttribute( 'method' ),
			target: this.target,
			rel: this.getAttribute( 'rel' ),
			fields: Object.fromEntries( Array.from( this.elements ).map( ( el ) => [ el.name, el.value ] ) ),
			inDocument: document.body.contains( this ),
			// The window the form's target names at the moment it's posted.
			frameWindowName: ( () => {
				const frame = document.querySelector( 'iframe' );
				return frame && frame.contentWindow ? frame.contentWindow.name : null;
			} )(),
		} );
	} );
	apiFetch.mockReset();
	apiFetch.mockResolvedValue( { items: [], current: null } );
	window.innerWidth = 1600;
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	ReactModal.setAppElement( container );
} );

afterEach( () => {
	removeAllFilters( 'nx_preview_toolbar_items' );
	act( () => {
		ReactDOM.unmountComponentAtNode( container );
	} );
	container.remove();
	document.body.innerHTML = '';
	document.body.style.overflow = '';
	delete window.notificationxTabs;
	jest.restoreAllMocks();
	jest.useRealTimers();
} );

const flush = async () => {
	await act( async () => {
		jest.runOnlyPendingTimers();
		await Promise.resolve();
		await Promise.resolve();
	} );
};

const open = async ( props = {} ) => {
	act( () => {
		ReactDOM.render( <PreviewModal name="preview" label="Preview" urls={ urls } errors={ {} } { ...props } />, container );
	} );
	act( () => {
		container.querySelector( '.wprf-step-btn-preview' ).click();
	} );
	await flush();
	return document.querySelector( '.nx-admin-modal-head' );
};

const decoded = ( entry ) => JSON.parse( fromBase64( entry.fields[ 'nx-preview' ] ) );

describe( 'today\'s Preview request', () => {
	it( 'posts the unsaved values and the nonce into the frame, at the default page', async () => {
		await open();
		expect( submitted ).toHaveLength( 1 );
		expect( submitted[ 0 ] ).toMatchObject( { action: HOME, method: 'post', target: 'preview-modal-iframe', rel: null, inDocument: true } );
		expect( Object.keys( submitted[ 0 ].fields ) ).toEqual( [ 'nx-preview', 'nx-preview-nonce' ] );
		expect( submitted[ 0 ].fields[ 'nx-preview-nonce' ] ).toBe( 'n0nce' );
		expect( decoded( submitted[ 0 ] ) ).toEqual( { ...mockBuilder.values, previewType: 'desktop' } );
		// The form is removed again.
		expect( document.querySelectorAll( 'form' ) ).toHaveLength( 0 );
	} );

	it( 'uses the source\'s own URL when there is one', async () => {
		mockBuilder.values.source = 'woocommerce_sales_inline';
		await open();
		expect( submitted[ 0 ].action ).toBe( urls.woocommerce_sales_inline );
	} );

	it( 'keeps the top bar: three device buttons, then the close button, centred', async () => {
		const head = await open();
		const buttons = head.querySelectorAll( ':scope > button' );
		expect( Array.from( buttons ).map( ( b ) => b.className.trim() ) ).toEqual( [
			'nx-admin-modal-preview-button active',
			'nx-admin-modal-preview-button',
			'nx-admin-modal-preview-button',
			'nx-admin-modal-close-button',
		] );
		// The new controls are one positioned group between them.
		const tools = head.querySelector( ':scope > .nx-preview-tools' );
		expect( tools ).not.toBeNull();
		expect( tools.nextElementSibling.className ).toBe( 'nx-admin-modal-close-button' );
		expect( document.querySelector( 'iframe[name="preview-modal-iframe"]' ) ).not.toBeNull();
	} );

	it( 'device buttons only resize the frame, they post nothing', async () => {
		const head = await open();
		act( () => {
			head.querySelectorAll( '.nx-admin-modal-preview-button' )[ 2 ].click();
		} );
		expect( submitted ).toHaveLength( 1 );
		expect( document.querySelector( '.ReactModal__Content' ).style.width ).toBe( '480px' );
	} );

	it( 'the close button still closes the popup', async () => {
		const head = await open();
		act( () => {
			head.querySelector( '.nx-admin-modal-close-button' ).click();
		} );
		expect( document.querySelector( '.nx-admin-modal-head' ) ).toBeNull();
	} );

	it( 'still shows the inline error when an inline source has no page', async () => {
		mockBuilder.values = { source: 'woocommerce_sales_inline', type: 'inline' };
		await open( { urls: { default: HOME }, errors: { woocommerce_sales_inline: 'No product yet' } } );
		expect( document.querySelector( '.nx-admin-modal-body' ).textContent ).toBe( 'No product yet' );
		expect( submitted ).toHaveLength( 0 );
	} );
} );

describe( 'page picker', () => {
	it( 'shows "Page: Home" for the home page and loads the pages when opened', async () => {
		const head = await open();
		const button = head.querySelector( '.nx-preview-tool-page' );
		expect( button.textContent ).toBe( 'Page: Home' );
		expect( apiFetch ).not.toHaveBeenCalled();

		apiFetch.mockResolvedValue( { items: [ page( 2, { front: true, title: 'Bakery' } ), page( 5, { status: 'draft', public: false } ) ], current: { id: 2, title: 'Bakery' } } );
		act( () => {
			button.click();
		} );
		await flush();
		expect( apiFetch ).toHaveBeenCalledWith( { path: pagesPath( '', HOME ) } );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		const items = document.querySelectorAll( '.nx-preview-page-item' );
		expect( items ).toHaveLength( 2 );
		expect( items[ 0 ].textContent ).toBe( 'Bakery (front page)' );
		expect( items[ 0 ].getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( items[ 1 ].textContent ).toBe( 'Page 5Draft' );
		// The default page's own name.
		expect( button.textContent ).toBe( 'Page: Bakery' );
	} );

	it( 're-posts the same request to the chosen page, and back to the default', async () => {
		const head = await open();
		apiFetch.mockResolvedValue( { items: [ page( 7 ) ], current: null } );
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			document.querySelector( '.nx-preview-page-item' ).click();
		} );
		expect( submitted ).toHaveLength( 2 );
		expect( submitted[ 1 ] ).toMatchObject( { action: 'http://storefy.local/page-7/', target: 'preview-modal-iframe' } );
		expect( submitted[ 1 ].fields ).toEqual( submitted[ 0 ].fields );
		expect( document.querySelector( '.nx-preview-page-picker' ) ).toBeNull();
		expect( head.querySelector( '.nx-preview-tool-page' ).textContent ).toBe( 'Page: Page 7' );

		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			document.querySelector( '.nx-preview-panel-foot .nx-preview-link' ).click();
		} );
		expect( submitted[ 2 ].action ).toBe( HOME );
		expect( head.querySelector( '.nx-preview-tool-page' ).textContent ).toBe( 'Page: Home' );
	} );

	it( 'a draft\'s preview page renaming the frame never sends the next choice to a new tab', async () => {
		const head = await open();
		apiFetch.mockResolvedValue( { items: [ page( 7, { status: 'draft' } ), page( 8 ) ], current: null } );
		const choose = async ( index ) => {
			act( () => {
				head.querySelector( '.nx-preview-tool-page' ).click();
			} );
			await flush();
			act( () => {
				document.querySelectorAll( '.nx-preview-page-item' )[ index ].click();
			} );
		};
		// WordPress core on ?preview=true pages: window.name = 'wp-preview-<id>'.
		const wpRenames = () => {
			document.querySelector( 'iframe' ).contentWindow.name = 'wp-preview-7';
		};

		await choose( 0 ); // the draft
		const draftFrame = document.querySelector( 'iframe' );
		wpRenames();
		await choose( 1 ); // a published page
		wpRenames();
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			document.querySelector( '.nx-preview-panel-foot .nx-preview-link' ).click(); // Back to default
		} );
		wpRenames();
		await choose( 0 ); // the draft again

		expect( submitted.map( ( s ) => s.action ) ).toEqual( [ HOME, 'http://storefy.local/page-7/', 'http://storefy.local/page-8/', HOME, 'http://storefy.local/page-7/' ] );
		submitted.forEach( ( s ) => {
			expect( s.target ).toBe( 'preview-modal-iframe' );
			expect( s.frameWindowName ).toBe( 'preview-modal-iframe' );
		} );
		// A fresh frame per choice, always named for the form.
		expect( document.querySelector( 'iframe' ) ).not.toBe( draftFrame );
		expect( document.querySelectorAll( 'iframe' ) ).toHaveLength( 1 );
		expect( document.querySelector( 'iframe' ).getAttribute( 'name' ) ).toBe( 'preview-modal-iframe' );
	} );

	it( 'resetFrameName gives a renamed frame window its name back', () => {
		const frame = document.createElement( 'iframe' );
		frame.name = 'preview-modal-iframe';
		document.body.appendChild( frame );
		frame.contentWindow.name = 'wp-preview-55';
		resetFrameName( frame );
		expect( frame.contentWindow.name ).toBe( 'preview-modal-iframe' );
		expect( () => resetFrameName( null ) ).not.toThrow();
	} );

	it( 'searches after a short pause and keeps only the newest answer', async () => {
		const head = await open();
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		let resolveOld;
		apiFetch.mockImplementationOnce( () => new Promise( ( r ) => ( resolveOld = r ) ) );
		apiFetch.mockResolvedValueOnce( { items: [ page( 9, { title: 'Honey oat bread' } ) ] } );
		const input = document.querySelector( '.nx-preview-page-search input' );
		const type = ( value ) => {
			const setter = Object.getOwnPropertyDescriptor( HTMLInputElement.prototype, 'value' ).set;
			setter.call( input, value );
			input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		};
		act( () => type( 'hon' ) );
		act( () => {
			jest.advanceTimersByTime( SEARCH_DELAY );
		} );
		act( () => type( 'honey' ) );
		await flush();
		expect( apiFetch ).toHaveBeenLastCalledWith( { path: pagesPath( 'honey', HOME ) } );
		await act( async () => {
			resolveOld( { items: [ page( 1, { title: 'Old answer' } ) ] } );
			await Promise.resolve();
		} );
		const titles = Array.from( document.querySelectorAll( '.nx-preview-page-title' ) ).map( ( t ) => t.textContent );
		expect( titles ).toEqual( [ 'Honey oat bread' ] );
	} );

	it( 'Escape closes the picker, not the Preview', async () => {
		const head = await open();
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		const input = document.querySelector( '.nx-preview-page-search input' );
		expect( document.activeElement ).toBe( input );
		act( () => {
			input.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', keyCode: 27, bubbles: true } ) );
		} );
		expect( document.querySelector( '.nx-preview-page-picker' ) ).toBeNull();
		expect( document.querySelector( '.nx-admin-modal-head' ) ).not.toBeNull();
		expect( document.activeElement ).toBe( head.querySelector( '.nx-preview-tool-page' ) );
	} );

	it( 'a click outside or into the frame closes the picker', async () => {
		const head = await open();
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			document.querySelector( '.nx-admin-modal-body' ).dispatchEvent( new MouseEvent( 'mousedown', { bubbles: true } ) );
		} );
		expect( document.querySelector( '.nx-preview-page-picker' ) ).toBeNull();

		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			window.dispatchEvent( new Event( 'blur' ) );
		} );
		expect( document.querySelector( '.nx-preview-page-picker' ) ).toBeNull();
	} );

	it( 'a chosen page replaces the inline error with the frame', async () => {
		mockBuilder.values = { source: 'woocommerce_sales_inline', type: 'inline' };
		const head = await open( { urls: { default: HOME }, errors: { woocommerce_sales_inline: 'No product yet' } } );
		apiFetch.mockResolvedValue( { items: [ page( 3, { type: 'product', type_label: 'Products' } ) ] } );
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		act( () => {
			document.querySelector( '.nx-preview-page-item' ).click();
		} );
		expect( document.querySelector( 'iframe[name="preview-modal-iframe"]' ) ).not.toBeNull();
		expect( submitted[ 0 ].action ).toBe( 'http://storefy.local/page-3/' );
	} );

	it( 'shows a message when the pages cannot be loaded', async () => {
		const head = await open();
		apiFetch.mockRejectedValue( new Error( 'offline' ) );
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		expect( document.querySelector( '.nx-preview-page-note' ).textContent ).toBe( 'The pages could not be loaded. Please try again.' );
	} );
} );

describe( 'open in new tab', () => {
	it( 'posts the same request to a new tab, without a handle on the builder', async () => {
		const head = await open();
		act( () => {
			head.querySelector( '.nx-preview-tool[aria-label="Open in new tab"]' ).click();
		} );
		expect( submitted ).toHaveLength( 2 );
		expect( submitted[ 1 ] ).toMatchObject( { action: HOME, target: '_blank', rel: 'noopener', method: 'post' } );
		expect( submitted[ 1 ].fields ).toEqual( submitted[ 0 ].fields );
	} );
} );

describe( 'toolbar size', () => {
	it.each( [
		[ 'desktop', 1600, 'wide' ],
		[ 'desktop', 820, 'narrow' ],
		[ 'tablet', 1600, 'narrow' ],
		[ 'phone', 1600, 'compact' ],
		[ 'desktop', 700, 'compact' ],
	] )( '%s at a %ipx window is %s', ( device, width, size ) => {
		expect( toolbarSize( popupWidth( device, width ) ) ).toBe( size );
	} );

	it( 'follows the window and the device buttons', async () => {
		window.innerWidth = 820;
		const head = await open();
		expect( head.querySelector( '.nx-preview-tools' ).className ).toBe( 'nx-preview-tools nx-preview-tools-narrow' );
		expect( head.querySelector( '.nx-preview-tool-page' ).textContent ).toBe( 'Home' );
		act( () => {
			head.querySelectorAll( '.nx-admin-modal-preview-button' )[ 2 ].click();
		} );
		expect( head.querySelector( '.nx-preview-tools' ).className ).toBe( 'nx-preview-tools nx-preview-tools-compact' );
		expect( head.querySelector( '.nx-preview-tool-page' ).textContent ).toBe( '' );
		expect( head.querySelector( '.nx-preview-tool-page' ).getAttribute( 'aria-label' ) ).toBe( 'Page: Home' );
		act( () => {
			window.innerWidth = 1600;
			head.querySelectorAll( '.nx-admin-modal-preview-button' )[ 0 ].click();
			window.dispatchEvent( new Event( 'resize' ) );
		} );
		expect( head.querySelector( '.nx-preview-tools' ).className ).toBe( 'nx-preview-tools nx-preview-tools-wide' );
	} );
} );

describe( 'helpers', () => {
	it( 'previewPayload carries the values and the per-user nonce', () => {
		const payload = previewPayload( { a: 'é' }, 'phone' );
		expect( JSON.parse( fromBase64( payload[ 'nx-preview' ] ) ) ).toEqual( { a: 'é', previewType: 'phone' } );
		delete window.notificationxTabs;
		expect( previewPayload( {}, 'desktop' )[ 'nx-preview-nonce' ] ).toBe( '' );
	} );

	it( 'createForm sets noopener only for a new tab', () => {
		expect( createForm( HOME, 'post', '_blank', {} ).getAttribute( 'rel' ) ).toBe( 'noopener' );
		expect( createForm( HOME, 'post', 'frame', {} ).getAttribute( 'rel' ) ).toBeNull();
	} );

	it( 'groups pages by type in server order and badges non-public ones', () => {
		const groups = groupPages( [ page( 1 ), page( 2, { type: 'post', type_label: 'Posts' } ), page( 3 ) ] );
		expect( groups.map( ( g ) => [ g.label, g.items.map( ( i ) => i.id ) ] ) ).toEqual( [ [ 'Pages', [ 1, 3 ] ], [ 'Posts', [ 2 ] ] ] );
		expect( statusBadges( page( 1, { status: 'future', password: true } ) ) ).toEqual( [ 'Scheduled', 'Password' ] );
		expect( statusBadges( page( 1 ) ) ).toEqual( [] );
	} );
} );

describe( 'add-on slot (nx_preview_toolbar_items)', () => {
	const ShareButton = ( { ctx } ) => (
		<>
			<button
				type="button"
				className="nx-preview-tool addon-share"
				aria-expanded={ 'share' === ctx.panel }
				onClick={ () => ctx.setPanel( 'share' === ctx.panel ? null : 'share' ) }
			>
				Share
			</button>
			{ 'share' === ctx.panel && <div className="nx-preview-panel addon-panel"><input className="addon-input" /></div> }
		</>
	);
	let seen;
	const addShare = () =>
		addFilter( 'nx_preview_toolbar_items', 'test/share', ( items, ctx ) => {
			seen = ctx;
			return [ ...items, <ShareButton ctx={ ctx } key="share" /> ];
		} );

	it( 'renders nothing extra when no add-on uses it', async () => {
		const head = await open();
		expect( Array.from( head.querySelectorAll( '.nx-preview-tools > *' ) ).map( ( el ) => el.getAttribute( 'aria-label' ) ) ).toEqual( [
			'Page: Home',
			'Open in new tab',
		] );
	} );

	it( 'gives add-ons the values, the page, the URL and the size', async () => {
		addShare();
		const head = await open();
		expect( head.querySelector( '.nx-preview-tools > .addon-share' ).previousElementSibling.getAttribute( 'aria-label' ) ).toBe( 'Open in new tab' );
		expect( seen ).toMatchObject( { values: mockBuilder.values, previewType: 'desktop', page: null, url: HOME, defaultUrl: HOME, size: 'wide', panel: null } );

		apiFetch.mockResolvedValue( { items: [ page( 7 ) ] } );
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		expect( seen.panel ).toBe( 'page' );
		act( () => {
			document.querySelector( '.nx-preview-page-item' ).click();
		} );
		expect( seen.page.id ).toBe( 7 );
		expect( seen.url ).toBe( 'http://storefy.local/page-7/' );
		expect( seen.defaultUrl ).toBe( HOME );
	} );

	it( 'keeps one panel open at a time', async () => {
		addShare();
		const head = await open();
		act( () => {
			head.querySelector( '.addon-share' ).click();
		} );
		expect( document.querySelector( '.addon-panel' ) ).not.toBeNull();
		act( () => {
			head.querySelector( '.nx-preview-tool-page' ).click();
		} );
		await flush();
		expect( document.querySelector( '.addon-panel' ) ).toBeNull();
		expect( document.querySelector( '.nx-preview-page-picker' ) ).not.toBeNull();
		act( () => {
			head.querySelector( '.addon-share' ).click();
		} );
		expect( document.querySelector( '.nx-preview-page-picker' ) ).toBeNull();
		// A click inside an add-on panel keeps it open.
		act( () => {
			document.querySelector( '.addon-input' ).dispatchEvent( new MouseEvent( 'mousedown', { bubbles: true } ) );
		} );
		expect( document.querySelector( '.addon-panel' ) ).not.toBeNull();
	} );

	it( 'Escape closes an add-on panel and returns to its button', async () => {
		addShare();
		const head = await open();
		act( () => {
			head.querySelector( '.addon-share' ).click();
		} );
		document.querySelector( '.addon-input' ).focus();
		act( () => {
			document.querySelector( '.addon-input' ).dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', keyCode: 27, bubbles: true } ) );
		} );
		expect( document.querySelector( '.addon-panel' ) ).toBeNull();
		expect( document.querySelector( '.nx-admin-modal-head' ) ).not.toBeNull();
		expect( document.activeElement ).toBe( head.querySelector( '.addon-share' ) );
	} );

	it( 'add-ons can open the new tab', async () => {
		addShare();
		await open();
		act( () => {
			seen.openInNewTab();
		} );
		expect( submitted[ 1 ] ).toMatchObject( { action: HOME, target: '_blank' } );
	} );

	it( 'ignores anything that is not a list of elements', async () => {
		addFilter( 'nx_preview_toolbar_items', 'test/bad', () => 'Share' );
		let head = await open();
		expect( head.querySelectorAll( '.nx-preview-tools > *' ) ).toHaveLength( 2 );
		act( () => {
			ReactDOM.unmountComponentAtNode( container );
		} );
		removeAllFilters( 'nx_preview_toolbar_items' );
		addFilter( 'nx_preview_toolbar_items', 'test/mixed', () => [ 'text', null, { a: 1 }, <button type="button" className="ok" key="ok" /> ] );
		head = await open();
		expect( head.querySelectorAll( '.nx-preview-tools > *' ) ).toHaveLength( 3 );
		expect( head.querySelector( '.nx-preview-tools > .ok' ) ).not.toBeNull();
	} );

	it( 'a broken add-on cannot break the Preview', async () => {
		const Broken = () => {
			throw new Error( 'boom' );
		};
		addFilter( 'nx_preview_toolbar_items', 'test/broken', ( items ) => [ ...items, <Broken key="b" /> ] );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		const head = await open();
		expect( head.querySelector( '.nx-error-boundary' ) ).not.toBeNull();
		expect( head.querySelector( '.nx-preview-tool-page' ) ).not.toBeNull();
		expect( document.querySelector( 'iframe[name="preview-modal-iframe"]' ) ).not.toBeNull();
		expect( submitted ).toHaveLength( 1 );
	} );
} );
