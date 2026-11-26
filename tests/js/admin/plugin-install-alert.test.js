/**
 * The Notification Bar's Essential Blocks popup (PressBar::load_plugin_dependencies()):
 * its main button installs and activates the plugin through /core-install,
 * instead of only opening a plugin search in a new tab.
 */
jest.mock( '../../../nxdev/notificationx/hooks', () => ( { useNotificationXContext: () => ( {} ) } ) );
jest.mock( '../../../nxdev/notificationx/core/ToasterMsg', () => ( { ToastAlert: jest.fn() } ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// The installed-plugin memory lives in the module: load fresh copies per test.
let proAlert, apiFetch, Swal, ToastAlert;

const popup = ( extra = {} ) => ( {
	forced: true,
	showConfirmButton: true,
	showCloseButton: true,
	title: 'This design needs Essential Blocks',
	denyButtonText: 'Install Essential Blocks',
	confirmButtonText: "<a href='https://essential-blocks.com/' target='_blank'>More Info</a>",
	html: '<span>This design is built with Essential Blocks.</span>',
	install_plugin: {
		slug: 'essential-blocks',
		file: 'essential-blocks.php',
		is_installed: false,
		loading: 'Installing Essential Blocks…',
		success: 'Essential Blocks is active. You can import this design now.',
		failed: 'Essential Blocks could not be installed. Install it from <a href="http://x/wp-admin/plugin-install.php">Plugins → Add Plugin</a>, then pick this design again.',
	},
	...extra,
} );

const flush = async () => {
	for ( let i = 0; i < 10; i++ ) {
		// eslint-disable-next-line no-await-in-loop
		await Promise.resolve();
	}
	jest.runOnlyPendingTimers();
	for ( let i = 0; i < 10; i++ ) {
		// eslint-disable-next-line no-await-in-loop
		await Promise.resolve();
	}
};

// Open and not closing (Swal.isVisible() needs layout, which jsdom lacks).
const isOpen = () => !! document.querySelector( '.swal2-popup.swal2-show' );

beforeEach( () => {
	jest.useFakeTimers();
	window.scrollTo = jest.fn();
	document.body.innerHTML = '<div id="notificationx"></div>';
	jest.resetModules();
	apiFetch = require( '@wordpress/api-fetch' );
	Swal = require( 'sweetalert2' );
	( { ToastAlert } = require( '../../../nxdev/notificationx/core/ToasterMsg' ) );
	( { proAlert } = require( '../../../nxdev/notificationx/core/functions' ) );
} );

afterEach( async () => {
	Swal.close();
	await flush();
	jest.useRealTimers();
} );

describe( 'proAlert with install_plugin', () => {
	it( 'shows a real install button, not a link to the plugin search', async () => {
		proAlert( popup() ).fire();
		await flush();
		const deny = Swal.getDenyButton();
		expect( Swal.getTitle().textContent ).toBe( 'This design needs Essential Blocks' );
		expect( deny.textContent ).toBe( 'Install Essential Blocks' );
		expect( deny.querySelector( 'a' ) ).toBeNull();
		// The extra key is not passed to SweetAlert as an option.
		expect( document.body.innerHTML ).not.toContain( 'plugin-install.php?s=Essential' );
	} );

	it( 'installs and activates through /core-install, then stops asking', async () => {
		apiFetch.mockResolvedValue( true );
		const done = proAlert( popup() ).fire();
		await flush();
		Swal.getDenyButton().click();
		await flush();
		await done;

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/notificationx/v1/core-install',
			method: 'POST',
			data: { slug: 'essential-blocks', file: 'essential-blocks.php', is_installed: false },
		} );
		expect( isOpen() ).toBe( false );
		expect( ToastAlert ).toHaveBeenCalledWith( 'success', 'Essential Blocks is active. You can import this design now.' );

		// Picking another Essential Blocks theme in the same page load: no popup.
		await proAlert( popup() ).fire();
		await flush();
		expect( isOpen() ).toBe( false );
	} );

	it( 'sends is_installed for an installed but inactive plugin', async () => {
		apiFetch.mockResolvedValue( true );
		const p = popup( { denyButtonText: 'Activate Essential Blocks' } );
		p.install_plugin.is_installed = true;
		proAlert( p ).fire();
		await flush();
		Swal.getDenyButton().click();
		await flush();
		expect( apiFetch.mock.calls[ 0 ][ 0 ].data.is_installed ).toBe( true );
	} );

	it( 'keeps the popup open with the reason when the install fails', async () => {
		apiFetch.mockResolvedValue( false );
		proAlert( popup() ).fire();
		await flush();
		Swal.getDenyButton().click();
		await flush();

		expect( isOpen() ).toBe( true );
		const message = Swal.getValidationMessage();
		expect( message.textContent ).toContain( 'Essential Blocks could not be installed.' );
		expect( message.querySelector( 'a' ).getAttribute( 'href' ) ).toBe( 'http://x/wp-admin/plugin-install.php' );
		expect( Swal.getDenyButton().textContent ).toBe( 'Install Essential Blocks' );
		expect( ToastAlert ).not.toHaveBeenCalled();

		// It can be tried again, and asks again on the next pick.
		apiFetch.mockResolvedValue( true );
		Swal.getDenyButton().click();
		await flush();
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( isOpen() ).toBe( false );
	} );

	it( 'shows the route error as text, never as markup', async () => {
		apiFetch.mockRejectedValue( { message: 'Sorry, you are not allowed to do that. <img src=x onerror=alert(1)>' } );
		proAlert( popup() ).fire();
		await flush();
		Swal.getDenyButton().click();
		await flush();

		const message = Swal.getValidationMessage();
		expect( message.textContent ).toContain( 'Sorry, you are not allowed to do that. <img src=x onerror=alert(1)>' );
		expect( message.querySelector( 'img' ) ).toBeNull();
		expect( isOpen() ).toBe( true );
	} );
} );

describe( 'proAlert without install_plugin', () => {
	it( 'keeps the usual Pro popup', async () => {
		proAlert( { title: 'Opps! This is PRO Feature.', message: 'Upgrade', denyButtonText: 'Close' } ).fire();
		await flush();
		expect( Swal.getTitle().textContent ).toBe( 'Opps! This is PRO Feature.' );
		expect( Swal.getDenyButton().textContent ).toBe( 'Close' );
		Swal.getDenyButton().click();
		await flush();
		expect( apiFetch ).not.toHaveBeenCalled();
		// Unchanged: SweetAlert still warns about the builder's own keys here.
		expect( console ).toHaveWarned();
	} );
} );
