#!/usr/bin/env node
/**
 * End-to-end check of the builder Preview's page picker and "Open in new tab"
 * (layout A: on the right of the popup's top bar).
 *
 * For each notification ID given, logged in as NX_E2E_USER, at a 1440px and an
 * 820px window, and with the phone frame:
 *   - the device buttons stay centred, the new controls sit right of them and
 *     at least 16px before the close button, nothing overlaps;
 *   - the picker lists pages; choosing one posts the same request (payload and
 *     nonce) into the frame at that page, and the frame renders the preview;
 *   - Escape closes the picker, not the popup;
 *   - "Open in new tab" posts the same request to a new tab, which renders it;
 *   - the request to the chosen page is still refused without the nonce.
 *
 *   node tests/e2e/builder-preview-pages.js 14
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER, NX_E2E_CHROME,
 * as in builder-preview.js. Screenshots go to tests/e2e/out/. Exit code 1 on
 * any failure.
 */
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const WP_USER = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const NOTIFICATION = '.notification-item, .nx-bar, .nx-gdpr, .nx-popup-overlay, .nx-exit-intent';
const OUT = path.join( __dirname, 'out' );
const MIN_GAP = 16;

const wpAuth = ( ...args ) =>
	execFileSync( 'wp', [ 'eval-file', path.join( __dirname, 'auth.php' ), ...args, `--user=${ WP_USER }`, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} );

const hasPreviewData = ( html ) => /"nxPreview":true/.test( html );
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

let failed = false;
const check = ( ok, label ) => {
	failed = failed || ! ok;
	console.log( `${ ok ? '✔' : '✖' } ${ label }` );
};

// Records every preview form the builder submits (the frame and new tabs).
const recordPosts = () => {
	window.__nxPosts = [];
	const submit = HTMLFormElement.prototype.submit;
	HTMLFormElement.prototype.submit = function () {
		if ( this.querySelector( 'input[name="nx-preview"]' ) ) {
			window.__nxPosts.push( { action: this.action, target: this.target, rel: this.getAttribute( 'rel' ), fields: Object.fromEntries( new FormData( this ) ) } );
		}
		return submit.call( this );
	};
};

// Top bar geometry: device buttons, the new controls, the close button.
const geometry = () => {
	const head = document.querySelector( '.nx-admin-modal-head' );
	const rect = ( el ) => {
		const r = el.getBoundingClientRect();
		return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
	};
	const devices = Array.from( head.querySelectorAll( '.nx-admin-modal-preview-button' ) ).map( rect );
	const tools = Array.from( head.querySelectorAll( '.nx-preview-tools > button' ) ).map( rect );
	return {
		head: rect( head ),
		devices,
		tools,
		toolsBox: rect( head.querySelector( '.nx-preview-tools' ) ),
		close: rect( head.querySelector( '.nx-admin-modal-close-button' ) ),
		size: head.querySelector( '.nx-preview-tools' ).className,
		pageLabel: head.querySelector( '.nx-preview-tool-page' ).textContent,
	};
};

const checkGeometry = ( g, label ) => {
	const devicesLeft = Math.min( ...g.devices.map( ( d ) => d.left ) );
	const devicesRight = Math.max( ...g.devices.map( ( d ) => d.right ) );
	const headCenter = g.head.left + g.head.width / 2;
	const toolsLeft = Math.min( ...g.tools.map( ( t ) => t.left ) );
	const toolsRight = Math.max( ...g.tools.map( ( t ) => t.right ) );
	check( Math.abs( ( devicesLeft + devicesRight ) / 2 - headCenter ) <= 1, `${ label }: device buttons centred (${ g.size })` );
	check( toolsLeft - devicesRight >= MIN_GAP, `${ label }: controls start ${ Math.round( toolsLeft - devicesRight ) }px right of the device buttons` );
	check( g.close.left - toolsRight >= MIN_GAP, `${ label }: ${ Math.round( g.close.left - toolsRight ) }px gap before the close button` );
	check( g.tools.every( ( t ) => t.top >= g.head.top && t.bottom <= g.head.bottom ), `${ label }: controls inside the top bar` );
};

const openPreview = async ( page, id ) => {
	await page.goto( `${ SITE_URL }/wp-admin/admin.php?page=nx-edit&id=${ id }`, { waitUntil: 'networkidle2' } );
	const button = await page.waitForFunction(
		() => Array.from( document.querySelectorAll( '.wprf-step-btn-preview' ) ).find( ( b ) => b.offsetParent !== null ),
		{ timeout: 30000 }
	);
	await button.asElement().click();
	await page.waitForSelector( '.nx-admin-modal-head .nx-preview-tools', { timeout: 15000 } );
};

const frameRendered = async ( page ) => {
	const handle = await page.waitForSelector( 'iframe[name="preview-modal-iframe"]', { timeout: 15000 } );
	const frame = await handle.contentFrame();
	try {
		await frame.waitForFunction( () => ( window.notificationXArr || [] ).some( ( d ) => d && d.nxPreview ), { timeout: 20000 } );
		await frame.waitForSelector( NOTIFICATION, { visible: true, timeout: 15000 } );
		return frame.url();
	} catch ( e ) {
		return false;
	}
};

async function run( browser, auth, id ) {
	const ctx = await ( browser.createBrowserContext || browser.createIncognitoBrowserContext ).call( browser );
	const page = await ctx.newPage();
	await page.setCookie(
		{ name: auth.name, value: auth.value, url: SITE_URL },
		{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
	);
	await page.evaluateOnNewDocument( recordPosts );

	// Layout at both builder widths, and with the phone frame.
	for ( const [ width, label ] of [ [ 1440, '1440px' ], [ 820, '820px' ] ] ) {
		await page.setViewport( { width, height: 900 } );
		await openPreview( page, id );
		await sleep( 500 );
		checkGeometry( await page.evaluate( geometry ), `#${ id } ${ label }` );
		await page.screenshot( { path: path.join( OUT, `preview-pages-${ id }-${ width }.png` ) } );
		if ( 1440 === width ) {
			await page.click( '.nx-admin-modal-preview-button:nth-of-type(3)' );
			await sleep( 300 );
			checkGeometry( await page.evaluate( geometry ), `#${ id } phone frame` );
			await page.click( '.nx-admin-modal-preview-button:nth-of-type(1)' );
		}
	}

	await page.setViewport( { width: 1440, height: 900 } );
	await openPreview( page, id );
	const firstUrl = await frameRendered( page );
	check( !! firstUrl, `#${ id } default page renders the preview` );
	const first = await page.evaluate( () => window.__nxPosts[ 0 ] );

	// Picker.
	await page.click( '.nx-preview-tool-page' );
	await page.waitForSelector( '.nx-preview-page-item', { timeout: 15000 } );
	await page.screenshot( { path: path.join( OUT, `preview-pages-${ id }-picker.png` ) } );
	const target = await page.evaluate( ( defaultUrl ) => {
		const items = Array.from( document.querySelectorAll( '.nx-preview-page-item' ) );
		const pick = items.find( ( b ) => ! b.classList.contains( 'current' ) && ! b.querySelector( '.nx-preview-page-badge' ) );
		if ( ! pick ) {
			return null;
		}
		const title = pick.querySelector( '.nx-preview-page-title' ).firstChild.textContent;
		pick.click();
		return title;
	}, first && first.action );
	check( !! target, `#${ id } picker lists another public page` );
	await sleep( 300 );
	const second = await page.evaluate( () => window.__nxPosts[ window.__nxPosts.length - 1 ] );
	check( second && second.target === 'preview-modal-iframe' && second.action !== first.action, `#${ id } choosing "${ target }" posts to that page (${ second && second.action })` );
	check( second && second.fields[ 'nx-preview' ] === first.fields[ 'nx-preview' ] && second.fields[ 'nx-preview-nonce' ] === first.fields[ 'nx-preview-nonce' ] && !! second.fields[ 'nx-preview-nonce' ], `#${ id } same payload and nonce` );
	// The frame keeps the default page until the new POST has loaded.
	await page
		.waitForFunction(
			( url ) => {
				const frame = document.querySelector( 'iframe[name="preview-modal-iframe"]' );
				return frame && frame.contentWindow.location.href.split( '?' )[ 0 ] === url.split( '?' )[ 0 ] && 'complete' === frame.contentDocument.readyState;
			},
			{ timeout: 20000 },
			second ? second.action : ''
		)
		.catch( () => {} );
	const secondUrl = await frameRendered( page );
	await sleep( 1200 );
	check( !! secondUrl && secondUrl.split( '?' )[ 0 ] === second.action.split( '?' )[ 0 ], `#${ id } the frame renders the preview on the chosen page` );
	check( ( await page.$eval( '.nx-preview-tool-page', ( b ) => b.textContent ) ).includes( target ), `#${ id } the page button names the chosen page` );
	await page.screenshot( { path: path.join( OUT, `preview-pages-${ id }-chosen.png` ) } );

	// Escape closes the picker only.
	await page.click( '.nx-preview-tool-page' );
	await page.waitForSelector( '.nx-preview-page-picker' );
	await page.keyboard.press( 'Escape' );
	await sleep( 200 );
	check( ! ( await page.$( '.nx-preview-page-picker' ) ) && !! ( await page.$( '.nx-admin-modal-head' ) ), `#${ id } Escape closes the picker, not the popup` );

	// Open in new tab.
	const newTab = new Promise( ( resolve ) => ctx.once( 'targetcreated', ( t ) => resolve( t.page() ) ) );
	await page.click( '.nx-preview-tool[aria-label="Open in new tab"]' );
	const tab = await newTab;
	const third = await page.evaluate( () => window.__nxPosts[ window.__nxPosts.length - 1 ] );
	check( third && third.target === '_blank' && third.rel === 'noopener' && third.action === second.action, `#${ id } new tab gets the same POST to the chosen page` );
	let tabOk = false;
	try {
		await tab.waitForFunction( () => ( window.notificationXArr || [] ).some( ( d ) => d && d.nxPreview ), { timeout: 20000 } );
		await tab.waitForSelector( NOTIFICATION, { visible: true, timeout: 15000 } );
		tabOk = ! ( await tab.$( '#wpadminbar' ) );
		await tab.setViewport( { width: 1440, height: 900 } );
		await sleep( 1200 );
		await tab.screenshot( { path: path.join( OUT, `preview-pages-${ id }-new-tab.png` ) } );
	} catch ( e ) {
		tabOk = false;
	}
	check( tabOk, `#${ id } new tab renders the preview, without the admin bar` );

	// The gate still decides for any page.
	const body = new URLSearchParams( { 'nx-preview': second.fields[ 'nx-preview' ] } );
	const cookie = `${ auth.name }=${ auth.value }`;
	const replay = async ( fields, withCookie ) =>
		( await fetch( second.action, { method: 'POST', body: new URLSearchParams( { ...Object.fromEntries( body ), ...fields } ), headers: withCookie ? { cookie } : {} } ) ).text();
	check( ! hasPreviewData( await replay( {}, true ) ), `#${ id } chosen page without the nonce is refused` );
	check( ! hasPreviewData( await replay( { 'nx-preview-nonce': second.fields[ 'nx-preview-nonce' ] }, false ) ), `#${ id } chosen page with the nonce but no login is refused` );

	await ctx.close();
}

( async () => {
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
	require( 'fs' ).mkdirSync( OUT, { recursive: true } );

	const ids = process.argv.slice( 2 );
	if ( ! ids.length ) {
		throw new Error( 'Pass one or more notification IDs.' );
	}

	const auth = JSON.parse( wpAuth( 'create' ) );
	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		ignoreHTTPSErrors: true,
		args: [ '--ignore-certificate-errors' ],
	} );
	try {
		for ( const id of ids ) {
			await run( browser, auth, id );
		}
	} finally {
		await browser.close();
		wpAuth( 'destroy', auth.token );
	}
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
