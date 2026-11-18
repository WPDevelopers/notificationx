#!/usr/bin/env node
/**
 * E2E check for the entries a WooCommerce sales notification rebuilds on save
 * and on Regenerate (issue #218).
 *
 * Saving a notification or clicking Regenerate deletes its entries and reads
 * them again from WooCommerce. The start of the "Display From The Last" window
 * used to be 12 hours late when it fell between 00:00 and 00:59 UTC and
 * 12 hours early when it fell between 13:00 and 23:59 UTC.
 *
 * This runner creates real orders of known ages, saves a notification through
 * the REST API the builder uses, and checks which orders end up stored and
 * which reach a visitor. It picks the "hours" part of the window from the
 * current time so that the start falls in the 00:xx hour, and then in the
 * 18:xx hour, whatever the time of day:
 *
 * - An order 6 hours inside the window is kept, one 6 hours outside is not.
 * - "Display The Last" above 10 stores more than 10 orders, and below 10 stores
 *   only that many, newest first (unchanged behaviour).
 * - Saving again with the first settings, and Regenerate, bring every order back.
 * - A visitor gets the entries from the public `notice` route and sees the popup.
 *
 * Needs WooCommerce active and no Processing / Completed order from the last
 * 31 days on the site (they would count towards the limit). Everything it
 * creates (one product, 16 orders, two notifications, one login session) is
 * deleted when it finishes.
 *
 * Usage:
 *   node tests/e2e/woo-orders-rebuild.js
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER, NX_E2E_CHROME as in
 * the other runners (see README.md). NX_E2E_PHP_MEMORY (e.g. 2G) runs WP-CLI
 * with that PHP memory limit.
 */

const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const WP_USER = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PHP_MEMORY = process.env.NX_E2E_PHP_MEMORY || '';
const REST = `${ SITE_URL }/wp-json/notificationx/v1`;

const HOUR = 60;
const DAY = 24 * HOUR;
// Order ages in minutes. Twelve orders three hours apart, the newest 10 minutes old.
const RECENT = Array.from( { length: 12 }, ( _, i ) => 10 + i * 3 * HOUR );
// A window of 1 day plus this many hours starts in the given UTC hour.
const hoursToStartIn = ( hour ) => ( new Date().getUTCHours() - hour + 24 ) % 24;
// Start in 00:xx (was 12 hours late) and in 18:xx (was 12 hours early), each
// with an order 6 hours inside the window and one 6 hours outside it.
const WINDOWS = [ 0, 18 ].map( ( hour ) => {
	const hours = hoursToStartIn( hour );
	const minutes = DAY + hours * HOUR;
	return { hour, hours, minutes, inside: minutes - 6 * HOUR + 1, outside: minutes + 6 * HOUR + 1 };
} );
const AGES = [ ...new Set( [ ...RECENT, ...WINDOWS.flatMap( ( w ) => [ w.inside, w.outside ] ) ] ) ];

const wp = ( ...args ) => {
	const all = [ ...args, `--path=${ WP_PATH }` ];
	const options = { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] };
	if ( PHP_MEMORY ) {
		const bin = execFileSync( 'which', [ 'wp' ], { encoding: 'utf8' } ).trim();
		return execFileSync( 'php', [ '-d', `memory_limit=${ PHP_MEMORY }`, bin, ...all ], options ).trim();
	}
	return execFileSync( 'wp', all, options ).trim();
};
const wpFile = ( file, ...args ) => wp( 'eval-file', path.join( __dirname, file ), ...args, `--user=${ WP_USER }` );
const data = ( ...args ) => wpFile( 'woo-orders-rebuild.php', ...args );

let failed = 0;
const check = ( ok, label, detail = '' ) => {
	failed += ok ? 0 : 1;
	console.log( `${ ok ? '✔' : '✖' } ${ label }${ ! ok && detail ? ` (${ detail })` : '' }` );
};

const sorted = ( ids ) => [ ...ids ].sort( ( a, b ) => a - b );
const same = ( a, b ) => JSON.stringify( sorted( a ) ) === JSON.stringify( sorted( b ) );

( async () => {
	// Self-signed local certificates.
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

	const auth = JSON.parse( wpFile( 'auth.php', 'create' ) );
	const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': auth.rest, cookie: `${ auth.name }=${ auth.value }` };
	const created = [];
	let setup = null;
	let browser = null;

	const api = async ( method, route, body ) => {
		const res = await fetch( `${ REST }${ route }`, { method, headers, body: body ? JSON.stringify( body ) : undefined } );
		const json = await res.json().catch( () => null );
		if ( ! res.ok ) {
			throw new Error( `${ method } ${ route }: HTTP ${ res.status } ${ json?.message || '' }` );
		}
		return json;
	};
	// What the builder does on Update: read the notification, change fields, send it back.
	const update = async ( id, fields ) => api( 'POST', `/nx/${ id }`, { ...await api( 'GET', `/nx/${ id }` ), ...fields } );
	const stored = ( id ) => JSON.parse( data( 'entries', String( id ) ) );
	// Entries a visitor gets, from the route the front end calls.
	const served = async ( id ) => {
		const res = await fetch( `${ REST }/notice`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { active: [ id ] } ),
		} );
		const json = await res.json().catch( () => ( {} ) );
		return [ 'active', 'global' ].reduce( ( n, key ) => n + ( json?.[ key ]?.[ id ]?.entries?.length || 0 ), 0 );
	};

	try {
		setup = JSON.parse( data( 'setup', AGES.join( ',' ) ) );
		if ( setup.foreign > 0 ) {
			throw new Error( `${ setup.foreign } other Processing / Completed order(s) from the last 31 days: they would use up the limit. Run on a site without recent orders.` );
		}
		const idOf = ( age ) => setup.orders.find( ( o ) => o.age === age ).id;
		const all = AGES.map( idOf );
		const newestFive = [ ...AGES ].sort( ( a, b ) => a - b ).slice( 0, 5 ).map( idOf );

		const expect = async ( id, label, orders ) => {
			const got = stored( id );
			check( same( got, orders ), `${ label }: ${ orders.length } orders stored`, `stored ${ got.length }: ${ sorted( got ).join( ',' ) }` );
			const shown = await served( id );
			check( shown === orders.length, `${ label }: ${ orders.length } entries served to a visitor`, `served ${ shown }` );
		};

		// WooCommerce Sales type.
		const sales = await api( 'POST', '/nx', {
			type: 'woocommerce_sales',
			source: 'woocommerce_sales',
			themes: 'woocommerce_sales_theme-one',
			title: 'E2E woo orders rebuild',
			enabled: true,
			display_last: 20,
			display_from: 30,
			display_from_hour: 0,
			display_from_minute: 0,
		} );
		created.push( sales.nx_id );

		await expect( sales.nx_id, 'publish, last 20, 30 days', all );

		for ( const w of WINDOWS ) {
			const label = `update, window 1 day ${ w.hours } hours (starts ${ String( w.hour ).padStart( 2, '0' ) }:xx UTC)`;
			await update( sales.nx_id, { display_from: 1, display_from_hour: w.hours } );
			await expect( sales.nx_id, label, AGES.filter( ( age ) => age < w.minutes ).map( idOf ) );
			const kept = stored( sales.nx_id );
			check( kept.includes( idOf( w.inside ) ), `${ label }: order 6 hours inside is kept` );
			check( ! kept.includes( idOf( w.outside ) ), `${ label }: order 6 hours outside is dropped` );
		}

		await update( sales.nx_id, { display_from: 30, display_from_hour: 0, display_last: 5 } );
		await expect( sales.nx_id, 'update, last 5, 30 days', newestFive );

		await update( sales.nx_id, { display_from: 30, display_from_hour: 0, display_last: 20 } );
		await expect( sales.nx_id, 'update back to last 20, 30 days', all );

		await api( 'GET', `/regenerate/${ sales.nx_id }` );
		await expect( sales.nx_id, 'Regenerate', all );

		// A visitor sees the popup.
		browser = await puppeteer.launch( {
			executablePath: CHROME,
			headless: true,
			ignoreHTTPSErrors: true,
			args: [ '--ignore-certificate-errors' ],
		} );
		const ctx = await browser.createIncognitoBrowserContext();
		const page = await ctx.newPage();
		await page.goto( `${ SITE_URL }/`, { waitUntil: 'domcontentloaded' } );
		const visible = await page.waitForSelector( `#notificationx-${ sales.nx_id }`, { visible: true, timeout: 45000 } ).then( () => true, () => false );
		check( visible, 'popup shown to a visitor on the home page' );
		await ctx.close();

		// Sales Notification type with the WooCommerce source: same extension, own source id.
		const conversions = await api( 'POST', '/nx', {
			type: 'conversions',
			source: 'woocommerce',
			themes: 'conversions_theme-one',
			title: 'E2E woo orders rebuild (conversions)',
			enabled: true,
			display_last: 20,
			display_from: 30,
			display_from_hour: 0,
			display_from_minute: 0,
		} );
		created.push( conversions.nx_id );
		await expect( conversions.nx_id, 'conversions publish, last 20, 30 days', all );
		await api( 'GET', `/regenerate/${ conversions.nx_id }` );
		await expect( conversions.nx_id, 'conversions Regenerate', all );
	} catch ( error ) {
		check( false, 'run completed', error.message );
	} finally {
		if ( browser ) {
			await browser.close();
		}
		if ( setup ) {
			data( 'teardown', String( setup.product ), setup.orders.map( ( o ) => o.id ).join( ',' ) || '-', created.join( ',' ) || '-' );
		}
		wpFile( 'auth.php', 'destroy', auth.token );
	}

	console.log( failed ? `\n${ failed } check(s) failed.` : '\nAll checks passed.' );
	process.exit( failed ? 1 : 0 );
} )();
