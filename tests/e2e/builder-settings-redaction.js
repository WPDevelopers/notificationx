#!/usr/bin/env node
/**
 * End-to-end check that users who cannot edit settings get no settings form and
 * no credentials in the admin app, and that the app still works for them.
 *
 * Creates two temporary users and deletes them when it finishes:
 *   - nx_e2e_viewer:  author with `read_notificationx` ("View Notification")
 *   - nx_e2e_creator: contributor with `read_notificationx` + `edit_notificationx`
 * For each, and for NX_E2E_USER (an administrator):
 *   - opens the NotificationX pages the user can reach and fails on any page
 *     error or console error,
 *   - follows the in-app link to the settings screen (a client-side route that
 *     WordPress never checks) and expects the app to send the user back,
 *   - searches `window.notificationxTabs` and GET /builder for the integration
 *     API key and the admin email.
 * The administrator must still see the key on the settings screen.
 *
 *   node tests/e2e/builder-settings-redaction.js [notification id]
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER, NX_E2E_CHROME,
 * as in compare-frontend-bundles.js. Exit code 1 on any failure.
 */
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const puppeteer = require( path.join( PLUGIN, 'node_modules/puppeteer-core' ) );

const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const ADMIN = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const wp = ( ...args ) =>
	execFileSync( 'wp', [ ...args, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} ).trim();

const TEMP_USERS = [
	{ login: 'nx_e2e_viewer', role: 'author', caps: [ 'read_notificationx' ] },
	{ login: 'nx_e2e_creator', role: 'contributor', caps: [ 'read_notificationx', 'edit_notificationx' ] },
];

const createUser = ( { login, role, caps } ) => {
	wp( 'user', 'create', login, `${ login }@example.invalid`, `--role=${ role }`, '--porcelain' );
	caps.forEach( ( cap ) => wp( 'user', 'add-cap', login, cap ) );
};
const deleteUser = ( login ) => {
	try {
		wp( 'user', 'delete', login, '--yes' );
	} catch ( e ) {
		// Not created.
	}
};

// Login sessions to end when the run finishes.
const sessions = [];

// Not counted, because they are not caused by the settings payload and show up
// on dev before this change too:
//   - off-site requests failing on a local site,
//   - React development builds logging "Warning: ..." through console.error
//     (ReactDOM.render on WP's React 18),
//   - an icon with `height="auto"` on the list and settings screens,
//   - the Dashboard calling an analytics route a view-only user may not use
//     (`rest_forbidden`).
const isAppError = ( e ) =>
	! /net::ERR_|Failed to load resource|^Warning: |<svg> attribute height: Expected length, "auto"|"code":"rest_forbidden"/.test( e );

// console.error( someObject ) prints as "JSHandle@object"; read the value.
const consoleText = async ( m ) => {
	const parts = await Promise.all(
		m.args().map( ( a ) => a.jsonValue().then( ( v ) => ( 'string' === typeof v ? v : JSON.stringify( v ) ) ).catch( () => String( a ) ) )
	);
	return parts.join( ' ' ) || m.text();
};

async function openAs( browser, login, pages, secrets, check ) {
	const auth = JSON.parse( wp( 'eval-file', path.join( __dirname, 'auth.php' ), 'create', `--user=${ login }` ) );
	const ctx = await browser.createIncognitoBrowserContext();
	try {
		const page = await ctx.newPage();
		await page.setViewport( { width: 1400, height: 900 } );
		await page.setCookie(
			{ name: auth.name, value: auth.value, url: SITE_URL },
			{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
		);
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		page.on( 'console', async ( m ) => m.type() === 'error' && errors.push( await consoleText( m ) ) );

		for ( const slug of pages ) {
			errors.length = 0;
			await page.goto( `${ SITE_URL }/wp-admin/admin.php?page=${ slug }`, { waitUntil: 'networkidle2' } );
			const rendered = await page.evaluate( () => !! document.querySelector( '#notificationx .notificationx-admin, #notificationx > *' ) );
			check( rendered, `${ login }: ${ slug } renders` );
			const appErrors = errors.filter( isAppError );
			check( ! appErrors.length, `${ login }: ${ slug } has no JS errors${ appErrors.length ? ` (${ appErrors.join( ' | ' ) })` : '' }` );

			const localized = await page.evaluate( () => JSON.stringify( window.notificationxTabs || null ) );
			if ( 'null' !== localized ) {
				secrets.forEach( ( { label, value, expected } ) =>
					check( localized.includes( value ) === expected, `${ login }: ${ slug } notificationxTabs ${ expected ? 'has' : 'has no' } ${ label }` )
				);
			}
		}

		const builder = await page.evaluate(
			async ( url, nonce ) => {
				const res = await fetch( url, { headers: { 'X-WP-Nonce': nonce }, credentials: 'same-origin' } );
				return { status: res.status, body: await res.text() };
			},
			`${ SITE_URL }/wp-json/notificationx/v1/builder`,
			auth.rest
		);
		check( 200 === builder.status, `${ login }: GET /builder is 200` );
		secrets.forEach( ( { label, value, expected } ) =>
			check( builder.body.includes( value ) === expected, `${ login }: GET /builder ${ expected ? 'has' : 'has no' } ${ label }` )
		);

		return page;
	} finally {
		sessions.push( { login, token: auth.token } );
	}
}

// The settings screen is also a client-side route of the admin app. WordPress
// only checks the capability when the page is loaded directly, so follow the
// route from inside the app and expect it to leave the settings screen.
async function followSettingsRoute( page, login, expectSettings, check ) {
	await page.goto( `${ SITE_URL }/wp-admin/admin.php?page=nx-admin`, { waitUntil: 'networkidle2' } );
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( e.message ) );
	page.on( 'console', async ( m ) => m.type() === 'error' && errors.push( await consoleText( m ) ) );
	const followed = await page.evaluate( () => {
		if ( ! document.querySelector( '#notificationx > *' ) ) {
			return false;
		}
		// What a react-router <Link> does: change the URL without a reload. The
		// router (history v4) re-reads the location on popstate.
		window.history.pushState( {}, '', `${ window.location.pathname }?page=nx-settings` );
		window.dispatchEvent( new PopStateEvent( 'popstate', { state: {} } ) );
		return true;
	} );
	if ( ! followed ) {
		check( false, `${ login }: admin app mounted before following the settings route` );
		return;
	}
	await new Promise( ( r ) => setTimeout( r, 3000 ) );
	const onSettings = await page.evaluate( () => /page=nx-settings/.test( window.location.search ) && !! document.querySelector( '.nx-settings' ) );
	const appErrors = errors.filter( isAppError );
	check( ! appErrors.length, `${ login }: settings route has no JS errors${ appErrors.length ? ` (${ appErrors.join( ' | ' ) })` : '' }` );
	check( onSettings === expectSettings, `${ login }: settings route ${ expectSettings ? 'shows' : 'leaves' } the settings screen` );
}

( async () => {
	// Self-signed local certificates.
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

	const id = process.argv[ 2 ] || wp( 'db', 'query', 'SELECT nx_id FROM wp_nx_posts ORDER BY nx_id DESC LIMIT 1', '--skip-column-names' );
	const apiKey = wp( 'eval', 'echo \\NotificationX\\Core\\Rest\\Integration::get_api_key();' );
	const adminEmail = wp( 'option', 'get', 'admin_email' );
	const hidden = ( expected ) => [
		{ label: 'integration API key', value: apiKey, expected },
		{ label: 'admin email', value: adminEmail, expected },
	];

	let failed = false;
	const check = ( ok, label ) => {
		failed = failed || ! ok;
		console.log( `${ ok ? '✔' : '✖' } ${ label }` );
	};

	TEMP_USERS.forEach( ( u ) => deleteUser( u.login ) );
	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		ignoreHTTPSErrors: true,
		args: [ '--ignore-certificate-errors' ],
	} );
	try {
		TEMP_USERS.forEach( createUser );

		const viewer = await openAs( browser, 'nx_e2e_viewer', [ 'nx-admin', 'nx-dashboard' ], hidden( false ), check );
		await followSettingsRoute( viewer, 'nx_e2e_viewer', false, check );

		const creator = await openAs( browser, 'nx_e2e_creator', [ 'nx-admin', 'nx-edit', `nx-edit&id=${ id }`, 'nx-builder' ], hidden( false ), check );
		await followSettingsRoute( creator, 'nx_e2e_creator', false, check );

		// The administrator keeps the full settings form, key included. The admin
		// email is only checked as absent above: the admin may never have set it
		// into a settings field.
		const admin = await openAs( browser, ADMIN, [ 'nx-admin', 'nx-settings' ], [ { label: 'integration API key', value: apiKey, expected: true } ], check );
		await followSettingsRoute( admin, ADMIN, true, check );
	} finally {
		await browser.close();
		sessions.forEach( ( { login, token } ) => {
			try {
				wp( 'eval-file', path.join( __dirname, 'auth.php' ), 'destroy', token, `--user=${ login }` );
			} catch ( e ) {
				// The temporary users are deleted below anyway.
			}
		} );
		TEMP_USERS.forEach( ( u ) => deleteUser( u.login ) );
	}
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	TEMP_USERS.forEach( ( u ) => deleteUser( u.login ) );
	process.exit( 1 );
} );
