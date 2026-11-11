#!/usr/bin/env node
/**
 * End-to-end check of Exit Intent "Stop Showing After End Date"
 * (free issue #211, Pro issue #65). Covers QA guide cases T1–T8 and T11:
 * docs/qa-guide/exit-intent-hide-after-end.md. T6 (Elementor design) runs only
 * when Elementor is active; otherwise it is reported as skipped.
 *
 * Logged in as NX_E2E_USER this:
 *   - creates Exit Intent notifications through the REST API:
 *       expired  - toggle on, end date 1 hour ago
 *       running  - toggle on, end date RUN_SECONDS from now
 *       off      - toggle off, end date 1 hour ago
 *       fresh    - Theme Five, no end date, disabled (builder checks only)
 *       elementor - an Elementor design (seed theme-five), toggle on, end date
 *                  RUN_SECONDS from now (only when Elementor is active)
 *   - Builder: the Customize tab has the toggle, the end date field shows only
 *     when the toggle is on and has the same value as the Theme Five field, and
 *     saving a campaign without picking a date stores about 7 days from now
 *     (it used to store the time of saving).
 *   - Front end, as a visitor who is not logged in, exit by moving the mouse
 *     out through the top of the page:
 *       expired never opens, running and off open;
 *       running closes when its end time arrives, without a dismissal entry;
 *       a page loaded before the end time does not open running after it;
 *       a new visit after the end time does not get running at all.
 *   - Builder preview of the expired campaign still shows the popup (T11).
 *   - deletes the notifications, the Elementor design and the login session.
 *
 *   npm run frontend && node tests/e2e/exit-intent-end.js
 *
 * Takes about RUN_SECONDS + 30 seconds. Environment: NX_E2E_SITE_URL,
 * NX_E2E_WP_PATH, NX_E2E_USER, NX_E2E_CHROME, as in compare-frontend-bundles.js.
 * Exit code 1 on any failure.
 */
const path = require( 'path' );
const fs = require( 'fs' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const WP_USER = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const REST = `${ SITE_URL }/wp-json/notificationx/v1`;
const RUN_SECONDS = 40;
const DAY = 24 * 3600 * 1000;

const wpAuth = ( ...args ) =>
	execFileSync( 'wp', [ 'eval-file', path.join( __dirname, 'auth.php' ), ...args, `--user=${ WP_USER }`, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} );

let failed = 0;
const check = ( ok, label, detail = '' ) => {
	failed += ok ? 0 : 1;
	console.log( `${ ok ? '✔' : '✖' } ${ label }${ ! ok && detail ? ` — ${ detail }` : '' }` );
};

const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, Math.max( 0, ms ) ) );
const iso = ( ms ) => new Date( ms ).toISOString();

// "November 18, 2026 12:39 pm", as the date field button shows it.
const parseFieldDate = ( text ) => {
	const m = /([A-Za-z]+) (\d+), (\d{4}) (\d+):(\d+) ?(am|pm)?/i.exec( text || '' );
	if ( ! m ) {
		return NaN;
	}
	let hour = Number( m[ 4 ] ) % 12;
	if ( /pm/i.test( m[ 6 ] || '' ) ) {
		hour += 12;
	}
	return new Date( `${ m[ 1 ] } ${ m[ 2 ] }, ${ m[ 3 ] } ${ hour }:${ m[ 5 ] }` ).getTime();
};

async function create( headers, fields ) {
	const res = await fetch( `${ REST }/nx`, {
		method: 'POST',
		headers,
		body: JSON.stringify( {
			source: 'exit_intent_custom',
			type: 'exit_intent',
			themes: 'exit_intent_custom_theme-three',
			enabled: true,
			...fields,
		} ),
	} );
	const saved = await res.json().catch( () => ( {} ) );
	if ( ! res.ok || ! saved.nx_id ) {
		throw new Error( `create ${ fields.title }: HTTP ${ res.status } ${ saved.message || '' }` );
	}
	return saved.nx_id;
}

const read = async ( headers, id ) => ( await fetch( `${ REST }/nx/${ id }`, { headers } ) ).json();

async function adminPage( browser, auth, id ) {
	const page = await browser.newPage();
	await page.setViewport( { width: 1400, height: 1000 } );
	await page.setCookie(
		{ name: auth.name, value: auth.value, url: SITE_URL },
		{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
	);
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( e.message ) );
	await page.goto( `${ SITE_URL }/wp-admin/admin.php?page=nx-edit&id=${ id }`, { waitUntil: 'networkidle2' } );
	await page.waitForSelector( '.wprf-tab-nav-item.customize_tab', { timeout: 30000 } );
	return { page, errors };
}

const openTab = async ( page, tab ) => {
	await page.click( `.wprf-tab-nav-item.${ tab }` );
	await sleep( 600 );
};

// Text of the visible date field, or null.
const visibleDate = ( page ) =>
	page.evaluate( () => {
		const field = Array.from( document.querySelectorAll( '.wprf-name-exit_intent_countdown_end' ) ).find( ( w ) => w.offsetParent !== null );
		return field ? field.querySelector( '.wprf-control-datetime' ).textContent.trim() : null;
	} );

async function checkPreview( browser, auth, id ) {
	const { page } = await adminPage( browser, auth, id );
	const button = await page.waitForFunction(
		() => Array.from( document.querySelectorAll( '.wprf-step-btn-preview' ) ).find( ( b ) => b.offsetParent !== null ),
		{ timeout: 30000 }
	);
	await button.asElement().click();
	const frameHandle = await page.waitForSelector( 'iframe[name="preview-modal-iframe"]', { timeout: 15000 } );
	const frame = await frameHandle.contentFrame();
	const shown = await frame.waitForSelector( '.nx-exit-intent-popup, .nx-exit-intent-elementor', { visible: true, timeout: 20000 } ).then( () => true ).catch( () => false );
	await frameHandle.screenshot( { path: path.join( __dirname, 'out', 'exit-intent-end-preview.png' ) } );
	check( shown, 'T11 builder preview: an expired campaign with the toggle on still previews' );
	await page.close();
}

async function checkBuilder( browser, auth, headers, ids ) {
	// Toggle on: the toggle is checked and the end date is in Customize.
	{
		const { page, errors } = await adminPage( browser, auth, ids.expired );
		await openTab( page, 'customize_tab' );
		const toggle = await page.$eval( '#exit_intent_hide_after_end', ( i ) => i.checked ).catch( () => null );
		check( toggle === true, 'T2 builder: "Stop Showing After End Date" is on for a campaign saved with it on' );
		check( !! await visibleDate( page ), 'T2 builder: End Date & Time shows in Customize when the toggle is on' );
		check( ! errors.length, 'builder: no script error', errors.join( '; ' ) );
		await page.close();
	}

	// Toggle off: no end date field in Customize.
	{
		const { page } = await adminPage( browser, auth, ids.off );
		await openTab( page, 'customize_tab' );
		const toggle = await page.$eval( '#exit_intent_hide_after_end', ( i ) => i.checked ).catch( () => null );
		check( toggle === false, 'T1 builder: the toggle is off for a campaign saved with it off' );
		check( null === await visibleDate( page ), 'T1 builder: End Date & Time is hidden while the toggle is off' );
		await page.close();
	}

	// Theme Five without a date: the default is about 7 days ahead, Content and
	// Customize show one value, and saving stores it.
	{
		const { page, errors } = await adminPage( browser, auth, ids.fresh );
		await openTab( page, 'content_tab' );
		const contentText = await visibleDate( page );
		const contentDate = parseFieldDate( contentText );
		check(
			contentDate > Date.now() + 5 * DAY && contentDate < Date.now() + 8 * DAY,
			'T7 builder: Theme Five "Sale End Date & Time" defaults to about 7 days from now',
			`field shows "${ contentText }"`
		);

		await openTab( page, 'customize_tab' );
		await page.click( 'label.wprf-input-label[for="exit_intent_hide_after_end"]' );
		await sleep( 400 );
		const customizeText = await visibleDate( page );
		check( customizeText === contentText, 'T8 builder: Customize End Date & Time has the same value as the Content tab', `"${ customizeText }" vs "${ contentText }"` );

		await page.click( '.nx-save' );
		await page.waitForResponse( ( r ) => r.url().includes( `/notificationx/v1/nx/${ ids.fresh }` ) && 'POST' === r.request().method(), { timeout: 20000 } ).catch( () => null );
		await sleep( 1000 );
		const saved = await read( headers, ids.fresh );
		const savedEnd = Date.parse( saved.exit_intent_countdown_end );
		check(
			savedEnd > Date.now() + 5 * DAY,
			'T7 builder: saving without picking a date stores about 7 days from now, not the time of saving',
			`saved "${ saved.exit_intent_countdown_end }"`
		);
		check( true === saved.exit_intent_hide_after_end || 'true' === String( saved.exit_intent_hide_after_end ), 'T8 builder: the toggle is saved', `saved ${ saved.exit_intent_hide_after_end }` );
		check( ! errors.length, 'builder: no script error while saving', errors.join( '; ' ) );
		await page.close();
	}
}

// A visitor who is not logged in. `trigger` exits through the top of the page.
async function visit( browser ) {
	const ctx = await browser.createIncognitoBrowserContext();
	const page = await ctx.newPage();
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( e.message ) );
	await page.goto( `${ SITE_URL }/?nx-e2e=${ Date.now() }`, { waitUntil: 'networkidle0' } );
	await page.waitForFunction( () => !! document.querySelector( '#notificationx-frontend-root, .notificationx-frontend' ) || window.notificationXArr, { timeout: 15000 } ).catch( () => null );
	await sleep( 1000 );
	return {
		page,
		errors,
		ctx,
		trigger: async () => {
			await page.evaluate( () => document.dispatchEvent( new MouseEvent( 'mouseleave', { clientY: 0, bubbles: true } ) ) );
			await sleep( 800 );
		},
		shown: ( id ) => page.evaluate( ( nxId ) => !! document.querySelector( `.nx-exit-intent-${ nxId }` ), id ),
		// The Elementor wrapper has no per-campaign class; the run has one design.
		elementorShown: () => page.evaluate( () => !! document.querySelector( '.nx-exit-intent-elementor' ) ),
		dismissed: ( id ) => page.evaluate( ( nxId ) => Object.keys( sessionStorage ).some( ( k ) => k.startsWith( `notificationx_exit_intent_${ nxId }_` ) ), id ),
	};
}

async function checkFrontEnd( browser, ids, runEnd ) {
	const first = await visit( browser );
	const late = await visit( browser );
	await first.trigger();

	check( ! await first.shown( ids.expired ), 'T2 front end: an expired campaign with the toggle on does not open' );
	check( await first.shown( ids.running ), 'T3 front end: a campaign with a future end date opens' );
	check( await first.shown( ids.off ), 'T1 front end: an expired campaign with the toggle off still opens' );
	if ( ids.elementor ) {
		check( await first.elementorShown(), 'T6 front end: the Elementor design opens before its end date' );
	}

	const left = runEnd - Date.now();
	check( left > 3000, 'timing: the first visit happened before the end time', `${ left } ms left` );
	await sleep( left + 1500 );

	check( ! await first.shown( ids.running ), 'T4 front end: an open popup closes when the end time arrives' );
	check( ! await first.dismissed( ids.running ), 'T4 front end: closing at the end time is not a dismissal' );
	check( await first.shown( ids.off ), 'T4 front end: the toggle-off popup stays open' );
	if ( ids.elementor ) {
		check( ! await first.elementorShown(), 'T6 front end: the open Elementor design closes at the end time' );
	}

	await late.trigger();
	check( ! await late.shown( ids.running ), 'T5 front end: a page loaded before the end time does not open it after' );

	const after = await visit( browser );
	await after.trigger();
	check( ! await after.shown( ids.running ), 'T4 front end: a new visit after the end time does not open it' );
	check( await after.shown( ids.off ), 'T1 front end: the toggle-off popup opens on a new visit' );
	if ( ids.elementor ) {
		check( ! await after.elementorShown(), 'T6 front end: a new visit after the end time does not get the Elementor design' );
	}

	for ( const v of [ first, late, after ] ) {
		check( ! v.errors.length, 'front end: no script error', v.errors.join( '; ' ) );
		await v.ctx.close();
	}
}

( async () => {
	// Self-signed local certificates.
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
	fs.mkdirSync( path.join( __dirname, 'out' ), { recursive: true } );

	const auth = JSON.parse( wpAuth( 'create' ) );
	const cookie = `${ auth.name }=${ auth.value }`;
	const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': auth.rest, cookie };
	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		ignoreHTTPSErrors: true,
		args: [ '--ignore-certificate-errors' ],
	} );
	const created = [];
	let elementorId = 0;

	try {
		const now = Date.now();
		const runEnd = now + RUN_SECONDS * 1000;
		const ids = {};
		const make = async ( key, fields ) => {
			ids[ key ] = await create( headers, { title: `E2E exit intent end ${ key }`, ...fields } );
			created.push( ids[ key ] );
		};
		await make( 'expired', { exit_intent_hide_after_end: true, exit_intent_countdown_end: iso( now - 3600 * 1000 ) } );
		await make( 'running', { exit_intent_hide_after_end: true, exit_intent_countdown_end: iso( runEnd ) } );
		await make( 'off', { exit_intent_hide_after_end: false, exit_intent_countdown_end: iso( now - 3600 * 1000 ) } );
		await make( 'fresh', { themes: 'exit_intent_custom_theme-five', enabled: false } );

		const elementorActive = /elementor/.test( execFileSync( 'wp', [ 'plugin', 'list', '--status=active', '--field=name', `--path=${ WP_PATH }` ], { encoding: 'utf8' } ) );
		if ( elementorActive ) {
			const res = await fetch( `${ REST }/exit-intent/elementor/import`, { method: 'POST', headers, body: JSON.stringify( { theme_id: 'theme-five' } ) } );
			const body = await res.json().catch( () => ( {} ) );
			elementorId = body?.data?.context?.elementor_id || 0;
			check( !! elementorId, 'T6 setup: Elementor design created from the theme-five seed', `HTTP ${ res.status }` );
			if ( elementorId ) {
				await make( 'elementor', { themes: null, elementor_id: elementorId, exit_intent_hide_after_end: true, exit_intent_countdown_end: iso( runEnd ) } );
			}
		} else {
			console.log( '- T6 skipped: Elementor is not active' );
		}

		const back = await read( headers, ids.running );
		check( Date.parse( back.exit_intent_countdown_end ) === Math.floor( runEnd / 1000 ) * 1000 || Date.parse( back.exit_intent_countdown_end ) === runEnd, 'REST: the end date is stored as sent', `read back "${ back.exit_intent_countdown_end }"` );

		// Front end first: it waits for the running campaign's end time.
		await checkFrontEnd( browser, ids, runEnd );
		await checkBuilder( browser, auth, headers, ids );
		await checkPreview( browser, auth, ids.expired );
	} finally {
		for ( const id of created ) {
			const res = await fetch( `${ REST }/nx/${ id }`, { method: 'DELETE', headers: { 'X-WP-Nonce': auth.rest, cookie } } );
			check( res.ok, `removed notification #${ id }`, `HTTP ${ res.status }` );
		}
		if ( elementorId ) {
			// Deleting the notification leaves its Elementor design behind.
			const res = await fetch( `${ REST }/exit-intent/elementor/remove`, { method: 'POST', headers, body: JSON.stringify( { elementor_id: elementorId } ) } );
			check( res.ok, `removed Elementor design #${ elementorId }`, `HTTP ${ res.status }` );
		}
		await browser.close();
		wpAuth( 'destroy', auth.token );
	}

	console.log( `${ failed } failed checks` );
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
