#!/usr/bin/env node
/**
 * Visual and behavioural regression check for the frontend bundle.
 *
 * Renders each scenario from tests/e2e/scenarios.php in a real WordPress site
 * twice: once with a baseline frontend.js (default: the committed HEAD
 * version) and once with the working-tree build. Compares, per scenario:
 *   - the rendered NotificationX DOM (normalised for random ids),
 *   - a viewport screenshot, pixel by pixel,
 *   - requests to Google's platform.js,
 *   - consent cookies and delete-cookies requests after clicking a GDPR button,
 *   - console errors and when the notification first appeared.
 *
 * Nothing is written to the database: NotificationX preview mode renders from
 * a POSTed `nx-preview` payload.
 *
 * Requirements: a local site with NotificationX active, WP-CLI, Google Chrome.
 *
 *   npm run frontend                         # build the new bundle first
 *   node tests/e2e/compare-frontend-bundles.js
 *   node tests/e2e/compare-frontend-bundles.js gdpr-modal     # one scenario
 *
 * Environment:
 *   NX_E2E_SITE_URL   default https://nx.test
 *   NX_E2E_WP_PATH    WordPress root for WP-CLI, default ../../.. from the plugin
 *   NX_E2E_OLD_BUNDLE baseline frontend.js path, default `git show HEAD:...`
 *   NX_E2E_CHROME     Chrome binary, default the macOS app path
 *
 * Output goes to tests/e2e/out/ (git-ignored): PNGs, DOM dumps, report.json.
 * Off-site requests (analytics, fonts, CDNs) are recorded but aborted, so
 * timing and rendering are deterministic. Exit code 1 when any scenario's DOM,
 * pixels or consent side effects differ, or the new bundle adds an error. Differences you expect (a deliberate change) must be reviewed by hand.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.join( __dirname, 'out' );
const PAGE_URL = `${ SITE_URL }/?nx-e2e=1`;
const SITE_HOST = new URL( SITE_URL ).host;
const WAIT_MS = 9000; // Notifications default to a 5 s delay, plus the preview round-trip.

// Button to click after the notification appears, per scenario.
const ACTIONS = {
	'gdpr-modal': '.nx-gdpr-actions .btn-secondary',
	'gdpr-accept': '.nx-gdpr-actions .btn-primary',
	'gdpr-reject': '.nx-gdpr-actions .btn-danger',
};

// Freeze animations so screenshots are deterministic.
const FREEZE_CSS =
	'*,*::before,*::after{animation-duration:0s!important;animation-delay:0s!important;transition:none!important;caret-color:transparent!important}';

const loadScenarios = () =>
	JSON.parse(
		execFileSync( 'wp', [ 'eval-file', path.join( __dirname, 'scenarios.php' ), `--path=${ WP_PATH }` ], {
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'ignore' ],
		} )
	);

const loadBundles = () => ( {
	old: process.env.NX_E2E_OLD_BUNDLE
		? fs.readFileSync( process.env.NX_E2E_OLD_BUNDLE )
		: execFileSync( 'git', [ 'show', 'HEAD:assets/public/js/frontend.js' ], { cwd: PLUGIN, maxBuffer: 64 * 1024 * 1024 } ),
	new: fs.readFileSync( path.join( PLUGIN, 'assets/public/js/frontend.js' ) ),
} );

const normalize = ( html ) =>
	html
		.replace( /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/g, 'UUID' )
		.replace( /\d{5,}/g, 'N' );

async function render( browser, scenario, name, bundle, variant ) {
	// A fresh incognito context per run, so consent cookies never leak between runs.
	const ctx = await browser.createIncognitoBrowserContext();
	const page = await ctx.newPage();
	await page.setViewport( { width: 1280, height: 800 } );

	const requests = [];
	const errors = [];
	let bundleServed = false;

	page.on( 'console', ( m ) => {
		// Aborted off-site requests log "Failed to load resource"; those are expected.
		if ( m.type() === 'error' && ! m.text().startsWith( 'Failed to load resource' ) ) {
			errors.push( 'console: ' + m.text() );
		}
	} );
	page.on( 'pageerror', ( e ) => errors.push( 'pageerror: ' + e.message ) );
	page.on( 'response', ( r ) => {
		if ( r.status() >= 400 && ! r.url().endsWith( '/favicon.ico' ) && new URL( r.url() ).host === SITE_HOST ) {
			errors.push( `http ${ r.status() } ${ r.url() }` );
		}
	} );

	await page.setRequestInterception( true );
	page.on( 'request', ( req ) => {
		const url = req.url();
		requests.push( url );
		if ( req.isNavigationRequest() && url === PAGE_URL ) {
			return req.continue( {
				method: 'POST',
				postData: 'nx-preview=' + encodeURIComponent( scenario.b64 ),
				headers: { ...req.headers(), 'content-type': 'application/x-www-form-urlencoded' },
			} );
		}
		if ( /\/notificationx\/assets\/public\/js\/frontend\.js/.test( url ) ) {
			bundleServed = true;
			return req.respond( { status: 200, contentType: 'application/javascript', body: bundle } );
		}
		// Off-site requests (analytics, web fonts, CDNs, platform.js) are logged
		// above but not fetched: their latency made timing and font rendering
		// differ between otherwise identical runs.
		if ( new URL( url ).host !== SITE_HOST && ! url.startsWith( 'data:' ) ) {
			return req.abort();
		}
		return req.continue();
	} );

	await page.evaluateOnNewDocument( ( css ) => {
		window.__nxFirstSeen = {};
		const t0 = performance.now();
		const watch = () => {
			for ( const sel of [ '.nx-gdpr', '.notification-item', '.nx-bar', '.nx-popup-overlay' ] ) {
				if ( ! window.__nxFirstSeen[ sel ] && document.querySelector( sel ) ) {
					window.__nxFirstSeen[ sel ] = Math.round( performance.now() - t0 );
				}
			}
			requestAnimationFrame( watch );
		};
		requestAnimationFrame( watch );
		document.addEventListener( 'DOMContentLoaded', () => {
			const style = document.createElement( 'style' );
			style.textContent = css;
			document.head.appendChild( style );
		} );
	}, FREEZE_CSS );

	await page.goto( PAGE_URL, { waitUntil: 'load', timeout: 60000 } );
	await new Promise( ( r ) => setTimeout( r, WAIT_MS ) );

	let clicked = null;
	if ( ACTIONS[ name ] ) {
		clicked = await page
			.$eval( ACTIONS[ name ], ( b ) => {
				b.click();
				return b.textContent.trim();
			} )
			.catch( ( e ) => 'CLICK FAILED: ' + e.message );
		await new Promise( ( r ) => setTimeout( r, 2000 ) );
	}

	const dom = await page.evaluate( () => {
		const isNx = ( el ) => {
			if ( [ 'SCRIPT', 'STYLE', 'LINK' ].includes( el.tagName ) ) {
				return false;
			}
			const cls = typeof el.className === 'string' ? el.className : '';
			return /^(nx|notificationx)/.test( el.id || '' ) || /(^|\s)(nx-|notificationx)/.test( cls );
		};
		const all = Array.from( document.body.querySelectorAll( '*' ) ).filter( isNx );
		const roots = all.filter( ( el ) => ! all.some( ( p ) => p !== el && p.contains( el ) ) );
		return {
			html: roots.map( ( el ) => el.outerHTML ).join( '\n' ),
			firstSeen: window.__nxFirstSeen,
			ytWidgets: document.querySelectorAll( '.g-ytsubscribe' ).length,
		};
	} );
	const cookies = ( await page.cookies() )
		.filter( ( c ) => c.name.startsWith( 'nx_' ) )
		.map( ( c ) => `${ c.name }=${ decodeURIComponent( c.value ) }` )
		.sort();

	const shot = path.join( OUT, `${ name }.${ variant }.png` );
	await page.screenshot( { path: shot } );
	await ctx.close();

	const html = normalize( dom.html );
	fs.writeFileSync( path.join( OUT, `${ name }.${ variant }.html` ), html );

	return {
		html,
		htmlLength: dom.html.length,
		firstSeen: dom.firstSeen,
		ytWidgets: dom.ytWidgets,
		clicked,
		cookies,
		deleteCookieRequests: requests.filter( ( u ) => u.includes( 'delete-cookies' ) ),
		platformJsRequests: requests.filter( ( u ) => u.includes( 'apis.google.com/js/platform.js' ) ).length,
		bundleServed,
		errors,
		shot,
	};
}

async function pixelDiff( browser, a, b ) {
	const page = await browser.newPage();
	const result = await page.evaluate(
		async ( a64, b64 ) => {
			const load = ( src ) =>
				new Promise( ( ok ) => {
					const img = new Image();
					img.onload = () => ok( img );
					img.src = src;
				} );
			const pixels = ( img ) => {
				const canvas = document.createElement( 'canvas' );
				canvas.width = img.width;
				canvas.height = img.height;
				const ctx = canvas.getContext( '2d' );
				ctx.drawImage( img, 0, 0 );
				return ctx.getImageData( 0, 0, img.width, img.height ).data;
			};
			const [ ia, ib ] = await Promise.all( [ load( 'data:image/png;base64,' + a64 ), load( 'data:image/png;base64,' + b64 ) ] );
			if ( ia.width !== ib.width || ia.height !== ib.height ) {
				return { diff: -1, total: ia.width * ia.height };
			}
			const da = pixels( ia );
			const db = pixels( ib );
			let diff = 0;
			for ( let i = 0; i < da.length; i += 4 ) {
				if ( da[ i ] !== db[ i ] || da[ i + 1 ] !== db[ i + 1 ] || da[ i + 2 ] !== db[ i + 2 ] ) {
					diff++;
				}
			}
			return { diff, total: da.length / 4 };
		},
		fs.readFileSync( a ).toString( 'base64' ),
		fs.readFileSync( b ).toString( 'base64' )
	);
	await page.close();
	return result;
}

( async () => {
	fs.mkdirSync( OUT, { recursive: true } );
	const scenarios = loadScenarios();
	const bundles = loadBundles();
	const only = process.argv[ 2 ];

	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		ignoreHTTPSErrors: true,
		args: [ '--ignore-certificate-errors' ],
	} );

	const report = {};
	let failed = false;
	for ( const name of Object.keys( scenarios ) ) {
		if ( only && name !== only ) {
			continue;
		}
		const oldRun = await render( browser, scenarios[ name ], name, bundles.old, 'old' );
		const newRun = await render( browser, scenarios[ name ], name, bundles.new, 'new' );
		const px = await pixelDiff( browser, oldRun.shot, newRun.shot );

		const summary = {
			domIdentical: oldRun.html === newRun.html,
			rendered: oldRun.htmlLength > 0 && newRun.htmlLength > 0,
			pixelsDifferent: px.diff,
			cookiesIdentical: JSON.stringify( oldRun.cookies ) === JSON.stringify( newRun.cookies ),
			deleteCookiesIdentical: JSON.stringify( oldRun.deleteCookieRequests ) === JSON.stringify( newRun.deleteCookieRequests ),
			platformJs: { old: oldRun.platformJsRequests, new: newRun.platformJsRequests },
			firstSeenMs: { old: oldRun.firstSeen, new: newRun.firstSeen },
			clicked: { old: oldRun.clicked, new: newRun.clicked },
			errors: { old: oldRun.errors, new: newRun.errors },
			bundleServed: oldRun.bundleServed && newRun.bundleServed,
		};
		report[ name ] = summary;

		const ok =
			summary.domIdentical &&
			summary.rendered &&
			summary.pixelsDifferent === 0 &&
			summary.cookiesIdentical &&
			summary.deleteCookiesIdentical &&
			summary.bundleServed &&
			// The new bundle must not add errors; pre-existing ones are reported, not failed.
			newRun.errors.every( ( e ) => oldRun.errors.includes( e ) );
		failed = failed || ! ok;
		console.log(
			`${ ok ? '✔' : '✖' } ${ name.padEnd( 24 ) } dom=${ summary.domIdentical } px=${ px.diff } ` +
				`platform.js ${ summary.platformJs.old }→${ summary.platformJs.new } ` +
				`firstSeen ${ JSON.stringify( summary.firstSeenMs.old ) }→${ JSON.stringify( summary.firstSeenMs.new ) }` +
				( newRun.errors.length ? ` errors=${ JSON.stringify( newRun.errors ) }` : '' )
		);
	}

	fs.writeFileSync( path.join( OUT, 'report.json' ), JSON.stringify( report, null, 2 ) );
	await browser.close();
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
