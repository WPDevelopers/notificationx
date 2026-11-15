#!/usr/bin/env node
/**
 * End-to-end check of the builder "Preview" button and the preview access gate.
 *
 * For each notification ID given, logged in as NX_E2E_USER:
 *   - opens the builder (admin.php?page=nx-edit&id=<id>), clicks Preview,
 *   - checks the POST into the preview iframe carries `nx-preview-nonce`,
 *   - checks the iframe renders a notification.
 * Then replays the same POST without a login, and with a login but no nonce,
 * and checks neither response contains preview data.
 *
 *   node tests/e2e/builder-preview.js 179 163
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
const WP_USER = process.env.NX_E2E_USER || 'admin';
const CHROME = process.env.NX_E2E_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const NOTIFICATION = '.notification-item, .nx-bar, .nx-gdpr, .nx-popup-overlay, .nx-exit-intent';

const wpAuth = ( ...args ) =>
	execFileSync( 'wp', [ 'eval-file', path.join( __dirname, 'auth.php' ), ...args, `--user=${ WP_USER }`, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} );

// Server-rendered preview data: the payload is echoed into the localized
// notificationX data only when preview mode accepted it.
const hasPreviewData = ( html ) => /"nxPreview":true/.test( html );

async function previewNotification( browser, auth, id ) {
	const ctx = await browser.createIncognitoBrowserContext();
	const page = await ctx.newPage();
	await page.setViewport( { width: 1400, height: 900 } );
	await page.setCookie(
		{ name: auth.name, value: auth.value, url: SITE_URL },
		{ name: auth.admin.name, value: auth.admin.value, url: `${ SITE_URL }/wp-admin/` }
	);

	// Record the fields of the form the builder submits into the preview
	// iframe. The request body itself is too large for puppeteer's postData().
	await page.evaluateOnNewDocument( () => {
		const submit = HTMLFormElement.prototype.submit;
		HTMLFormElement.prototype.submit = function () {
			if ( this.target === 'preview-modal-iframe' ) {
				window.__nxPreviewPost = Object.fromEntries( new FormData( this ) );
			}
			return submit.call( this );
		};
	} );

	await page.goto( `${ SITE_URL }/wp-admin/admin.php?page=nx-edit&id=${ id }`, { waitUntil: 'networkidle2' } );
	// Every builder tab has its own Preview button; only the current tab's is visible.
	const button = await page.waitForFunction(
		() => Array.from( document.querySelectorAll( '.wprf-step-btn-preview' ) ).find( ( b ) => b.offsetParent !== null ),
		{ timeout: 30000 }
	);
	await button.asElement().click();

	const frameHandle = await page.waitForSelector( 'iframe[name="preview-modal-iframe"]', { timeout: 15000 } );
	const frame = await frameHandle.contentFrame();
	let rendered = false;
	try {
		// A notification that renders in preview mode, not one live on the site.
		await frame.waitForFunction( () => ( window.notificationXArr || [] ).some( ( d ) => d && d.nxPreview ), { timeout: 15000 } );
		await frame.waitForSelector( NOTIFICATION, { visible: true, timeout: 15000 } );
		// Let the entrance animation finish so the screenshot shows the notification.
		await new Promise( ( r ) => setTimeout( r, 1500 ) );
		rendered = true;
	} catch ( e ) {
		rendered = false;
	}
	const fields = await page.evaluate( () => window.__nxPreviewPost || null );
	const previewPost = fields ? new URLSearchParams( fields ) : null;
	await frameHandle.screenshot( { path: path.join( __dirname, 'out', `builder-preview-${ id }.png` ) } );
	await ctx.close();

	return { previewPost, rendered };
}

// Replays a preview POST with the given cookie header and fields.
const replay = async ( payload, fields, cookie ) => {
	const body = new URLSearchParams( { 'nx-preview': payload, ...fields } );
	const res = await fetch( `${ SITE_URL }/`, {
		method: 'POST',
		body,
		headers: cookie ? { cookie } : {},
	} );
	return res.text();
};

( async () => {
	// Self-signed local certificates.
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
	require( 'fs' ).mkdirSync( path.join( __dirname, 'out' ), { recursive: true } );

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

	let failed = false;
	const check = ( ok, label ) => {
		failed = failed || ! ok;
		console.log( `${ ok ? '✔' : '✖' } ${ label }` );
	};

	try {
		for ( const id of ids ) {
			const { previewPost, rendered } = await previewNotification( browser, auth, id );
			check( !! previewPost, `#${ id } builder POSTed to the preview iframe` );
			if ( ! previewPost ) {
				continue;
			}
			check( !! previewPost.get( 'nx-preview-nonce' ), `#${ id } preview POST carries nx-preview-nonce` );
			check( rendered, `#${ id } preview iframe rendered a notification` );

			const payload = previewPost.get( 'nx-preview' );
			const cookie = `${ auth.name }=${ auth.value }`;
			const nonce = previewPost.get( 'nx-preview-nonce' );
			check( hasPreviewData( await replay( payload, { 'nx-preview-nonce': nonce }, cookie ) ), `#${ id } replay with login + nonce is a preview` );
			check( ! hasPreviewData( await replay( payload, {}, null ) ), `#${ id } replay without login or nonce is refused` );
			check( ! hasPreviewData( await replay( payload, { 'nx-preview-nonce': nonce }, null ) ), `#${ id } replay with nonce but no login is refused` );
			check( ! hasPreviewData( await replay( payload, {}, cookie ) ), `#${ id } replay with login but no nonce is refused` );
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
