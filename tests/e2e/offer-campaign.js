#!/usr/bin/env node
/**
 * End-to-end check of the seasonal offer campaign (Black Friday / Cyber Monday 2026) in a real
 * browser: the offer notice on the WordPress Dashboard and the pointer beside the NotificationX
 * menu item (includes/Admin/OfferPointer.php).
 *
 *   node tests/e2e/offer-campaign.js
 *
 * Needs NotificationX Pro inactive and today's date inside the campaign. With Pro active the run
 * stops with exit code 2, unless NX_E2E_DEACTIVATE_PRO=1 is set: then it deactivates
 * NotificationX Pro for the run and activates it again when it finishes.
 *
 * What it changes on the site, and puts back when it finishes (tests/e2e/offer-campaign.php):
 * NX_E2E_USER's dismissals of the notice and the pointer, that user's admin colour scheme, the
 * notice library's cooldown transient, the shared pointer slot option, and one login session.
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER (an administrator), NX_E2E_CHROME,
 * as in compare-frontend-bundles.js, and NX_E2E_PHP_MEMORY (PHP memory limit for WP-CLI). Exit code 1 on any failure, 2 when the campaign cannot show.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const ADMIN = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.join( __dirname, 'out' );
const PRO = 'notificationx-pro/notificationx-pro.php';

const NOTICE = '#wpnotice-notificationx-nx_bfcm_2026';
const POINTER = '.wp-pointer.nx-offer-pointer';
const MENU_LINK = '#toplevel_page_nx-admin > a.menu-top';
const LINK = 'https://notificationx.com/bfcm2026/admin-notice';
const NOTICE_TEXT =
	'Black Friday Sale: Turn holiday traffic into customers with 15+ real-time social proof notifications, AI-powered features & more – now up to 40% OFF!';
const POINTER_TEXT =
	'Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers.';
const PURPLE = 'rgb(82, 82, 220)';
const BLUE = 'rgb(34, 113, 177)';

// NX_E2E_PHP_MEMORY (e.g. 1G) runs WP-CLI with that PHP memory limit, for sites whose plugins
// and theme do not fit in WP-CLI's default.
const MEMORY = process.env.NX_E2E_PHP_MEMORY;
const WP_CLI = MEMORY ? execFileSync( 'which', [ 'wp' ], { encoding: 'utf8' } ).trim() : 'wp';
const wp = ( ...args ) =>
	execFileSync(
		MEMORY ? 'php' : WP_CLI,
		[ ...( MEMORY ? [ '-d', `memory_limit=${ MEMORY }`, WP_CLI ] : [] ), ...args, `--path=${ WP_PATH }` ],
		{ encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] }
	).trim();
const state = ( ...args ) => wp( 'eval-file', path.join( __dirname, 'offer-campaign.php' ), ...args, `--user=${ ADMIN }` );
// WP-CLI may print PHP notices before the JSON.
const json = ( out ) => JSON.parse( out.slice( out.lastIndexOf( '\n' ) + 1 ) );

let failed = 0;
const check = ( ok, label ) => {
	console.log( `${ ok ? 'ok  ' : 'FAIL' } ${ label }` );
	failed += ok ? 0 : 1;
};

const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );
// The pointer shows only on NotificationX's own pages; the Dashboard has the notice.
const NX_PAGE = 'admin.php?page=nx-admin';
const admin = ( slug ) => `${ SITE_URL }/wp-admin/${ slug }`;
const open = async ( page, slug ) => {
	await page.goto( admin( slug ), { waitUntil: 'networkidle2' } );
	// The pointer opens on DOM ready and is placed on the next frame.
	await sleep( 300 );
};
const count = ( page, selector ) => page.$$eval( selector, ( nodes ) => nodes.length );
const style = ( page, selector, property ) =>
	page.$eval( selector, ( node, name ) => window.getComputedStyle( node )[ name ], property );
const text = ( page, selector ) => page.$eval( selector, ( node ) => node.textContent.replace( /\s+/g, ' ' ).trim() );
const visible = ( page, selector ) =>
	page.$$eval( selector, ( nodes ) =>
		nodes.some( ( node ) => {
			const box = node.getBoundingClientRect();
			return box.width > 0 && box.height > 0 && window.getComputedStyle( node ).display !== 'none';
		} )
	);
const box = ( page, selector ) =>
	page.$eval( selector, ( node ) => {
		const { top, left, right, bottom, width, height } = node.getBoundingClientRect();
		return { top, left, right, bottom, width, height };
	} );

async function noticeChecks( page, errors ) {
	await open( page, 'index.php' );
	check( 1 === ( await count( page, NOTICE ) ), 'notice: shown on the Dashboard' );
	if ( ! ( await count( page, NOTICE ) ) ) {
		// One WPDeveloper notice shows at a time, across every plugin that vendors the library.
		const others = await page.$$eval( '[id^="wpnotice-"]', ( nodes ) => nodes.map( ( node ) => node.id ) );
		console.log( `     shown instead: ${ others.join( ', ' ) || 'no WPDeveloper notice' }` );
		return;
	}

	check( ( await text( page, `${ NOTICE } .wpnotice-content-wrapper > p` ) ).endsWith( NOTICE_TEXT ), 'notice: campaign copy' );

	const upgrade = `${ NOTICE } .nx-notice-action-button > a.button-primary`;
	check( 'Upgrade To Pro Now' === ( await text( page, upgrade ) ), 'notice: button label' );
	check( LINK === ( await page.$eval( upgrade, ( a ) => a.getAttribute( 'href' ) ) ), 'notice: button links to the campaign page' );
	check( '_blank' === ( await page.$eval( upgrade, ( a ) => a.getAttribute( 'target' ) ) ), 'notice: button opens a new tab' );
	check(
		'I Want To Pay Full Price' === ( await text( page, `${ NOTICE } .nx-notice-action-dismiss` ) ),
		'notice: dismiss label'
	);
	check( 1 === ( await count( page, `${ NOTICE } .nx-notice-action-dismiss[data-dismiss="true"]` ) ), 'notice: dismiss link dismisses' );

	// The CSS is keyed on the notice id; with another campaign's id it renders unstyled.
	check( PURPLE === ( await style( page, NOTICE, 'borderLeftColor' ) ), 'notice: styled by its own id (border)' );
	check( PURPLE === ( await style( page, upgrade, 'backgroundColor' ) ), 'notice: styled by its own id (button)' );
	check( '14px' === ( await style( page, `${ NOTICE } .wpnotice-content-wrapper > p`, 'fontSize' ) ), 'notice: 14px text' );

	// wp-emoji.js must not swap the emoji for a Twemoji image; it is the text's own size.
	check( 1 === ( await count( page, `${ NOTICE } .nx-notice-emoji` ) ), 'notice: emoji span' );
	check( 0 === ( await count( page, `${ NOTICE } .nx-notice-emoji img` ) ), 'notice: emoji is not a Twemoji image' );
	check( '14px' === ( await style( page, `${ NOTICE } .nx-notice-emoji`, 'fontSize' ) ), 'notice: emoji is the text size' );

	// The logo loads (a broken thumbnail leaves an empty box beside the text).
	check(
		( await page.$eval( `${ NOTICE } .wpnotice-thumbnail-wrapper img`, ( img ) => img.naturalWidth ) ) > 0,
		'notice: logo loads'
	);

	// The text and the two actions sit right of the logo, actions under the text, on one row.
	const logo = await box( page, `${ NOTICE } .wpnotice-thumbnail-wrapper img` );
	const copy = await box( page, `${ NOTICE } .wpnotice-content-wrapper > p` );
	const button = await box( page, upgrade );
	const dismiss = await box( page, `${ NOTICE } .nx-notice-action-dismiss` );
	check( copy.left >= logo.right && button.top >= copy.bottom, 'notice: logo, then text, actions under the text' );
	check(
		button.right <= dismiss.left && Math.abs( button.top + button.height / 2 - ( dismiss.top + dismiss.height / 2 ) ) < 6,
		'notice: button and dismiss link on one row'
	);

	await page.screenshot( { path: path.join( OUT, 'offer-campaign-dashboard.png' ) } );
	check( ! errors.length, `notice: no script error on the Dashboard${ errors.length ? ` (${ errors.join( ' | ' ) })` : '' }` );

	for ( const slug of [ 'plugins.php', 'edit.php', 'options-general.php', 'admin.php?page=nx-admin' ] ) {
		await open( page, slug );
		check( 0 === ( await count( page, NOTICE ) ), `notice: not on ${ slug }` );
	}

	await page.setViewport( { width: 560, height: 900 } );
	await open( page, 'index.php' );
	check( ! ( await visible( page, NOTICE ) ), 'notice: hidden on a phone' );
	await page.setViewport( { width: 1400, height: 900 } );
}

async function pointerChecks( page, info ) {
	await open( page, NX_PAGE );
	check( 1 === ( await count( page, POINTER ) ) && ( await visible( page, POINTER ) ), 'pointer: shown on the NotificationX page' );
	if ( ! ( await count( page, POINTER ) ) ) {
		return;
	}

	const deals = `${ POINTER } .nx-offer-pointer__actions > a.button-primary`;
	const dismiss = `${ POINTER } .nx-offer-pointer__actions > a.close`;
	check( 'Black Friday Is On!' === ( await text( page, `${ POINTER } h3` ) ), 'pointer: title' );
	check( POINTER_TEXT === ( await text( page, `${ POINTER } .wp-pointer-content > p` ) ), 'pointer: copy' );
	check( 'View Deals' === ( await text( page, deals ) ), 'pointer: button label' );
	check( LINK === ( await page.$eval( deals, ( a ) => a.getAttribute( 'href' ) ) ), 'pointer: button links to the campaign page' );
	check( '_blank' === ( await page.$eval( deals, ( a ) => a.getAttribute( 'target' ) ) ), 'pointer: button opens a new tab' );
	check( 'Dismiss' === ( await text( page, dismiss ) ), 'pointer: Dismiss label' );
	check( String( info.priority ) === String( json( state( 'state' ) ).slot ), 'pointer: holds the shared slot' );

	// To the right of the menu, level with the NotificationX item.
	const link = await box( page, MENU_LINK );
	const pointer = await box( page, POINTER );
	const middle = link.top + link.height / 2;
	check( pointer.left >= link.right - 2, 'pointer: right of the menu' );
	check( middle > pointer.top && middle < pointer.bottom, 'pointer: level with the NotificationX menu item' );

	// The button sits left of Dismiss, on one row.
	const dealsBox = await box( page, deals );
	const dismissBox = await box( page, dismiss );
	check(
		dealsBox.right <= dismissBox.left &&
			Math.abs( dealsBox.top + dealsBox.height / 2 - ( dismissBox.top + dismissBox.height / 2 ) ) < 8,
		'pointer: button left of Dismiss, on one row'
	);

	const colours = async ( label ) => {
		check( BLUE === ( await style( page, `${ POINTER } h3`, 'backgroundColor' ) ), `pointer: blue header (${ label })` );
		check( 'rgb(255, 255, 255)' === ( await style( page, `${ POINTER } h3`, 'color' ) ), `pointer: white title (${ label })` );
		check( BLUE === ( await style( page, deals, 'backgroundColor' ) ), `pointer: blue button (${ label })` );
		check( BLUE === ( await style( page, dismiss, 'color' ) ), `pointer: blue Dismiss (${ label })` );
		check(
			'rgb(255, 255, 255)' === ( await style( page, `${ POINTER } .wp-pointer-content`, 'backgroundColor' ) ),
			`pointer: white body (${ label })`
		);
	};
	await colours( 'default scheme' );
	await page.screenshot( { path: path.join( OUT, 'offer-campaign-pointer.png' ) } );

	// A user's admin colour scheme recolours core pointers and primary buttons; not this one.
	state( 'scheme', 'sunrise' );
	await open( page, NX_PAGE );
	await colours( 'Sunrise scheme' );
	state( 'scheme', 'fresh' );

	// NotificationX's own pages show it; other screens do not.
	for ( const slug of [ NX_PAGE, 'admin.php?page=nx-settings' ] ) {
		await open( page, slug );
		check( 1 === ( await count( page, POINTER ) ) && ( await visible( page, POINTER ) ), `pointer: shown on ${ slug }` );
	}
	await page.screenshot( { path: path.join( OUT, 'offer-campaign-pointer-nx-page.png' ) } );
	for ( const slug of [ 'index.php', 'plugins.php', 'edit.php', 'users.php', 'options-general.php' ] ) {
		await open( page, slug );
		check( 0 === ( await count( page, POINTER ) ), `pointer: not on ${ slug }` );
	}

	// It stays beside the menu link while the page scrolls. A short window, so the page scrolls.
	await page.setViewport( { width: 1400, height: 600 } );
	await open( page, NX_PAGE );
	const gap = () =>
		page.evaluate(
			( pointerSelector, linkSelector ) => {
				const a = document.querySelector( linkSelector ).getBoundingClientRect();
				const b = document.querySelector( pointerSelector ).getBoundingClientRect();
				return `${ Math.round( b.top - a.top ) },${ Math.round( b.left - a.right ) }`;
			},
			POINTER,
			MENU_LINK
		);
	const before = await gap();
	const scrollable = await page.evaluate( () => document.documentElement.scrollHeight - window.innerHeight );
	if ( scrollable < 200 ) {
		console.log( 'skip pointer: the NotificationX page is too short to scroll' );
	} else {
		for ( const y of [ 250, 100000 ] ) {
			await page.evaluate( ( value ) => window.scrollTo( 0, value ), y );
			await sleep( 300 );
			check( before === ( await gap() ), `pointer: beside the menu item after scrolling to ${ y }` );
		}
	}
	await page.setViewport( { width: 1400, height: 900 } );

	// Under 783px the admin menu is off canvas: nothing to point at.
	await page.setViewport( { width: 700, height: 900 } );
	await open( page, NX_PAGE );
	check( ! ( await visible( page, POINTER ) ), 'pointer: hidden while the menu is off canvas' );
	await page.setViewport( { width: 1400, height: 900 } );

	// Dismiss closes it and sends the dismissal with its nonce. Answered here, so the site
	// records nothing and the pointer is still there afterwards.
	await open( page, NX_PAGE );
	let posted = null;
	const intercept = ( request ) => {
		const body = request.postData() || '';
		if ( request.url().includes( '/wp-admin/admin-ajax.php' ) && body.includes( `action=${ info.action }` ) ) {
			posted = new URLSearchParams( body );
			request.respond( { status: 200, contentType: 'application/json', body: JSON.stringify( { success: true } ) } );
			return;
		}
		request.continue();
	};
	await page.setRequestInterception( true );
	page.on( 'request', intercept );
	await page.click( dismiss );
	await sleep( 500 );
	check( ! ( await visible( page, POINTER ) ), 'pointer: Dismiss closes it' );
	check( posted && info.action === posted.get( 'action' ), 'pointer: Dismiss sends the dismissal' );
	check( posted && /^[a-f0-9]{10}$/.test( posted.get( 'nonce' ) || '' ), 'pointer: the dismissal carries a nonce' );
	page.off( 'request', intercept );
	await page.setRequestInterception( false );

	await open( page, NX_PAGE );
	check( await visible( page, POINTER ), 'pointer: back after a reload when nothing was recorded' );

	// The real request: a wrong nonce is refused, the right one is recorded for this user.
	const dismissRequest = ( nonce ) =>
		page.evaluate(
			async ( action, value ) => {
				const data = window.nxOfferPointer;
				const res = await fetch( data.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams( { action, nonce: value || data.nonce } ).toString(),
				} );
				return { status: res.status, body: await res.text() };
			},
			info.action,
			nonce
		);
	const refused = await dismissRequest( 'not-a-nonce' );
	check( 403 === refused.status && ! json( state( 'state' ) ).dismissed, 'pointer: a dismissal with a wrong nonce is refused' );

	const accepted = await dismissRequest( '' );
	const after = json( state( 'state' ) );
	check( 200 === accepted.status && /"success":true/.test( accepted.body ), 'pointer: a dismissal with the nonce is accepted' );
	check( true === after.dismissed, 'pointer: the dismissal is recorded for the user' );
	check( String( info.priority ) !== String( after.slot ), 'pointer: the dismissal gives the shared slot back' );

	await open( page, NX_PAGE );
	check( 0 === ( await count( page, POINTER ) ), 'pointer: gone for good after a dismissal' );
	await open( page, 'admin.php?page=nx-settings' );
	check( 0 === ( await count( page, POINTER ) ), 'pointer: gone on NotificationX pages too' );
}

( async () => {
	fs.mkdirSync( OUT, { recursive: true } );

	let reactivatePro = false;
	if ( 'active' === wp( 'plugin', 'get', PRO, '--field=status' ) ) {
		if ( '1' !== process.env.NX_E2E_DEACTIVATE_PRO ) {
			console.error(
				'NotificationX Pro is active, so the offer notice and the pointer do not show.\n' +
					'Deactivate it, or run with NX_E2E_DEACTIVATE_PRO=1 to deactivate it for this run.'
			);
			process.exit( 2 );
		}
		wp( 'plugin', 'deactivate', PRO );
		reactivatePro = true;
	}

	let info = null;
	let session = null;
	let browser = null;
	const cleanUp = () => {
		if ( session ) {
			try {
				wp( 'eval-file', path.join( __dirname, 'auth.php' ), 'destroy', session, `--user=${ ADMIN }` );
			} catch ( e ) {
				// Expires after an hour.
			}
		}
		if ( info ) {
			state( 'restore', info.snapshot );
		}
		if ( reactivatePro ) {
			wp( 'plugin', 'activate', PRO );
		}
	};

	try {
		info = json( state( 'prepare' ) );
		if ( info.pro || ! info.in_window ) {
			console.error( info.pro ? 'The site still counts as a Pro site.' : 'Today is outside the campaign dates (15 November to 14 December 2026, UTC).' );
			cleanUp();
			process.exit( 2 );
		}

		const auth = json( wp( 'eval-file', path.join( __dirname, 'auth.php' ), 'create', `--user=${ ADMIN }` ) );
		session = auth.token;

		browser = await puppeteer.launch( {
			executablePath: CHROME,
			headless: true,
			ignoreHTTPSErrors: true,
			args: [ '--ignore-certificate-errors' ],
		} );
		const page = await browser.newPage();
		await page.setViewport( { width: 1400, height: 900 } );
		await page.setCookie(
			{ name: auth.name, value: auth.value, url: SITE_URL },
			{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
		);
		// Script errors only: a local site logs failed off-site requests to the console.
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );

		await noticeChecks( page, errors );
		await pointerChecks( page, info );
	} finally {
		if ( browser ) {
			await browser.close();
		}
		cleanUp();
	}

	console.log( failed ? `\n${ failed } check(s) failed.` : '\nAll checks passed.' );
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
