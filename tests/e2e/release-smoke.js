#!/usr/bin/env node
/**
 * Release smoke test: the checks a release needs that the other runners don't cover.
 *
 *   - Security: the WPScan proofs of concept, as a visitor who is not logged in.
 *     A private post and a shortcode through `nx-preview`, and `send-rating`.
 *   - Admin: every NotificationX admin page mounts without a script error.
 *   - Sources: for every registered source (free and Pro) a notification is
 *     created through the REST API, read back, opened in the builder, previewed
 *     and deleted.
 *   - Front end: saved Notification Bar and Popup notifications show for a
 *     visitor who is not logged in.
 *
 *   node tests/e2e/release-smoke.js                  # everything
 *   node tests/e2e/release-smoke.js press_bar brevo  # only these sources
 *
 * Writes to the site's database: one login session, one private post and one
 * notification per source, all removed when the run ends. Never sends the
 * authorised `send-rating` request, because that one emails support.
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER, NX_E2E_CHROME,
 * as in compare-frontend-bundles.js. Exit code 1 on any failure.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const WP_USER = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const REST = `${ SITE_URL }/wp-json/notificationx/v1`;
const SECRET = 'NXE2ESECRETMARKER';
const MARKER = 'NXE2ESHORTCODEMARKER';
const ADMIN_PAGES = [ 'nx-admin', 'nx-dashboard', 'nx-edit', 'nx-analytics', 'nx-settings', 'nx-builder' ];
const FATAL = /critical error on this website|Fatal error|Something went wrong\. Please reload/i;
// Saved notifications checked on the front end, with the element each one renders.
const FRONT_END = {
	press_bar: ( id ) => `#nx-bar-${ id }`,
	popup_notification: () => '.nx-popup-overlay',
};

const wp = ( ...args ) =>
	execFileSync( 'wp', [ ...args, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'ignore' ],
	} ).trim();
const wpAuth = ( ...args ) => wp( 'eval-file', path.join( __dirname, 'auth.php' ), ...args, `--user=${ WP_USER }` );
const b64 = ( data ) => Buffer.from( JSON.stringify( data ) ).toString( 'base64' );

let failed = 0;
const check = ( ok, label, detail = '' ) => {
	failed += ok ? 0 : 1;
	console.log( `${ ok ? '✔' : '✖' } ${ label }${ ! ok && detail ? ` (${ detail })` : '' }` );
};

// POST to the home page as preview mode expects it.
const preview = async ( payload, fields = {}, cookie = null ) => {
	const res = await fetch( `${ SITE_URL }/`, {
		method: 'POST',
		body: new URLSearchParams( { 'nx-preview': payload, ...fields } ),
		headers: cookie ? { cookie } : {},
	} );
	return { status: res.status, html: await res.text() };
};

async function checkSecurity( auth, cookie ) {
	const postId = wp( 'post', 'create', '--post_title=NX E2E', `--post_content=${ SECRET }`, '--post_status=private', '--porcelain' );
	try {
		const base = { source: 'press_bar', type: 'notification_bar', theme: 'bar-theme-one', themes: 'bar-theme-one' };
		const privatePost = b64( { ...base, gutenberg_id: Number( postId ) } );
		const shortcode = b64( { ...base, press_content: `${ MARKER }[audio src="https://example.com/x.mp3"]` } );
		const leaked = ( html ) => html.includes( SECRET );
		const ran = ( html ) => html.includes( MARKER ) || html.includes( 'wp-audio-shortcode' );

		check( ! leaked( ( await preview( privatePost ) ).html ), 'visitor cannot read a private post through nx-preview' );
		check( ! ran( ( await preview( shortcode ) ).html ), 'visitor cannot run a shortcode through nx-preview' );
		check( ! ran( ( await preview( shortcode, { 'nx-preview-nonce': 'deadbeef00' } ) ).html ), 'visitor with a made-up nonce is refused' );
		check( ! ran( ( await preview( shortcode, { 'nx-preview-nonce': auth.nonce } ) ).html ), 'visitor with an admin nonce is refused' );
		check( ! ran( ( await preview( shortcode, {}, cookie ) ).html ), 'admin without a nonce is refused' );
		// Control: without this the refusals above could pass on a broken request.
		check( ran( ( await preview( shortcode, { 'nx-preview-nonce': auth.nonce }, cookie ) ).html ), 'admin with a valid nonce gets the preview' );
	} finally {
		wp( 'post', 'delete', postId, '--force' );
	}

	const optionBefore = wp( 'eval', `var_export( get_option( 'nx_feedback_shared', 'unset' ) );` );
	for ( const body of [ {}, { rating: 5, review: 'e2e' } ] ) {
		const res = await fetch( `${ REST }/send-rating`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( body ),
		} );
		check( 401 === res.status, `visitor send-rating ${ JSON.stringify( body ) } is refused`, `HTTP ${ res.status }` );
	}
	check( wp( 'eval', `var_export( get_option( 'nx_feedback_shared', 'unset' ) );` ) === optionBefore, 'visitor send-rating leaves nx_feedback_shared alone' );
}

// Opens a wp-admin page as the logged-in user. Returns what went wrong, if anything.
async function adminPage( browser, auth, url, ready ) {
	const ctx = await browser.createIncognitoBrowserContext();
	const page = await ctx.newPage();
	const problems = [];
	page.on( 'pageerror', ( e ) => problems.push( `pageerror: ${ e.message.slice( 0, 160 ) }` ) );
	await page.setViewport( { width: 1400, height: 900 } );
	await page.setCookie(
		{ name: auth.name, value: auth.value, url: SITE_URL },
		{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
	);
	try {
		await page.goto( `${ SITE_URL }/wp-admin/${ url }`, { waitUntil: 'networkidle2', timeout: 60000 } );
		await page.waitForFunction( ready, { timeout: 30000 } );
	} catch ( e ) {
		problems.push( e.message.slice( 0, 160 ) );
	}
	const text = await page.evaluate( () => document.body.innerText ).catch( () => '' );
	if ( FATAL.test( text ) ) {
		problems.push( text.match( FATAL )[ 0 ] );
	}
	await ctx.close();
	return problems;
}

const mounted = () => document.querySelector( '#notificationx' )?.childElementCount > 0;
const builderMounted = () => !! document.querySelector( '#notificationx .wprf-tab-menu-wrapper, #notificationx .wprf-tabs-wrapper, #notificationx .wprf-tab-content' );

async function checkAdminPages( browser, auth ) {
	for ( const slug of ADMIN_PAGES ) {
		const problems = await adminPage( browser, auth, `admin.php?page=${ slug }`, mounted );
		check( ! problems.length, `admin page ${ slug }`, problems.join( '; ' ) );
	}
}

async function checkSource( browser, auth, cookie, { source, type, themes }, created ) {
	const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': auth.rest, cookie };
	const showOnFrontEnd = !! FRONT_END[ source ];
	const res = await fetch( `${ REST }/nx`, {
		method: 'POST',
		headers,
		body: JSON.stringify( { source, type, themes, title: `E2E ${ source }`, enabled: showOnFrontEnd } ),
	} );
	const saved = await res.json().catch( () => ( {} ) );
	if ( ! res.ok || ! saved.nx_id ) {
		check( false, `${ source } create`, `HTTP ${ res.status } ${ saved.code || '' } ${ saved.message || '' }` );
		return;
	}
	created.push( saved.nx_id );

	const read = await fetch( `${ REST }/nx/${ saved.nx_id }`, { headers } );
	const post = await read.json().catch( () => null );
	const problems = read.ok && post?.source === source ? [] : [ `read back HTTP ${ read.status }` ];

	problems.push( ...await adminPage( browser, auth, `admin.php?page=nx-edit&id=${ saved.nx_id }`, builderMounted ) );

	const shown = await preview( b64( post || saved ), { 'nx-preview-nonce': auth.nonce }, cookie );
	// Inline notifications print no notificationX data, only the preview styles.
	const isPreview = shown.html.includes( '"nxPreview":true' ) || shown.html.includes( 'notificationx-woo-shortcode-inline-wrapper' );
	if ( 200 !== shown.status || FATAL.test( shown.html ) || ! isPreview ) {
		problems.push( `preview HTTP ${ shown.status }` );
	}

	if ( showOnFrontEnd ) {
		const ctx = await browser.createIncognitoBrowserContext();
		const page = await ctx.newPage();
		await page.goto( `${ SITE_URL }/`, { waitUntil: 'domcontentloaded' } );
		await page.waitForSelector( FRONT_END[ source ]( saved.nx_id ), { visible: true, timeout: 20000 } ).catch( () => problems.push( 'not shown to a visitor' ) );
		await ctx.close();
	}

	check( ! problems.length, `${ source } create, read, builder, preview${ showOnFrontEnd ? ', front end' : '' }`, problems.join( '; ' ) );
}

( async () => {
	// Self-signed local certificates.
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
	fs.mkdirSync( path.join( __dirname, 'out' ), { recursive: true } );

	const only = process.argv.slice( 2 );
	const sources = JSON.parse( wp( 'eval-file', path.join( __dirname, 'sources.php' ), `--user=${ WP_USER }` ) )
		.filter( ( s ) => ! only.length || only.includes( s.source ) );
	const auth = JSON.parse( wpAuth( 'create' ) );
	const cookie = `${ auth.name }=${ auth.value }`;
	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		ignoreHTTPSErrors: true,
		args: [ '--ignore-certificate-errors' ],
	} );
	const created = [];

	try {
		await checkSecurity( auth, cookie );
		await checkAdminPages( browser, auth );
		for ( const source of sources ) {
			await checkSource( browser, auth, cookie, source, created );
		}
	} finally {
		for ( const id of created ) {
			const res = await fetch( `${ REST }/nx/${ id }`, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': auth.rest, cookie },
			} );
			check( res.ok, `removed notification #${ id }`, `HTTP ${ res.status }` );
		}
		await browser.close();
		wpAuth( 'destroy', auth.token );
	}

	console.log( `${ sources.length } sources, ${ failed } failed checks` );
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
