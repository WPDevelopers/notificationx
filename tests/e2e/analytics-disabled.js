#!/usr/bin/env node
/**
 * End-to-end check that the frontend writes no views while "Enable Analytics"
 * is off, and counts them again when it is switched back on.
 *
 * Views are counted inside the `/notice` request. With analytics off nothing
 * reads them (the Analytics page, the dashboard widget, the report email and
 * the click route are off), but one row was still updated per notification on
 * every request. The script sends real `/notice` requests for a temporary
 * Notification Bar:
 *   1. analytics on: each request adds one view,
 *   2. analytics off: the bar is still in the response and no view is added,
 *   3. analytics on again: counting resumes.
 *
 *   node tests/e2e/analytics-disabled.js
 *
 * It creates one temporary Notification Bar ("NX E2E analytics") and deletes
 * it, with its views, when it finishes. It changes the "Enable Analytics"
 * setting during the run and puts the original value back.
 *
 * Environment: NX_E2E_SITE_URL, NX_E2E_WP_PATH, NX_E2E_USER, as in
 * compare-frontend-bundles.js. Exit code 1 on any failure.
 */
const path = require( 'path' );
const https = require( 'https' );
const http = require( 'http' );
const { execFileSync } = require( 'child_process' );

const PLUGIN = path.resolve( __dirname, '../..' );
const SITE_URL = ( process.env.NX_E2E_SITE_URL || 'https://nx.test' ).replace( /\/$/, '' );
const WP_PATH = process.env.NX_E2E_WP_PATH || path.resolve( PLUGIN, '../../..' );
const ADMIN = process.env.NX_E2E_USER || 'admin';

const wp = ( ...args ) =>
	execFileSync( 'wp', [ ...args, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} ).trim();

const wpJson = ( php, ...args ) => JSON.parse( wp( 'eval', php, ...args ).split( '\n' ).pop() );

/** POST /notice for one bar. Resolves { status, body }. */
const notice = ( nxId ) =>
	new Promise( ( resolve, reject ) => {
		const url = new URL( `${ SITE_URL }/wp-json/notificationx/v1/notice/?frontend=true` );
		const payload = JSON.stringify( { pressbar: [ nxId ], deviceType: 'desktop' } );
		const request = ( 'https:' === url.protocol ? https : http ).request(
			url,
			{
				method: 'POST',
				rejectUnauthorized: false, // Local sites use a self-signed certificate.
				headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength( payload ) },
			},
			( response ) => {
				let body = '';
				response.on( 'data', ( chunk ) => ( body += chunk ) );
				response.on( 'end', () => resolve( { status: response.statusCode, body } ) );
			}
		);
		request.on( 'error', reject );
		request.end( payload );
	} );

const createBar = () =>
	wpJson(
		'$r = ( new \\NotificationX\\Abilities\\Manage\\CreateNotification() )->execute( array( "config" => array( "title" => "NX E2E analytics", "type" => "notification_bar", "source" => "press_bar", "themes" => "press_bar_theme-one", "press_content" => "NX E2E analytics bar" ) ) ); if ( is_wp_error( $r ) ) { fwrite( STDERR, $r->get_error_message() ); exit( 1 ); } \\NotificationX\\Core\\PostType::get_instance()->update_status( array( "nx_id" => $r["nx_id"], "enabled" => true, "update_status" => true, "source" => "press_bar" ) ); echo wp_json_encode( (int) $r["nx_id"] );',
		`--user=${ ADMIN }`
	);

const deleteBar = ( nxId ) => wp( 'eval', `\\NotificationX\\Core\\PostType::get_instance()->delete_post( ${ nxId } );` );

const getEnabled = () =>
	wpJson( 'echo wp_json_encode( (bool) \\NotificationX\\Admin\\Settings::get_instance()->get( "settings.enable_analytics", true ) );' );

const setEnabled = ( enabled ) =>
	wp( 'eval', `\\NotificationX\\Admin\\Settings::get_instance()->set( "settings.enable_analytics", ${ enabled ? 'true' : 'false' } );` );

/** Views stored for the bar, and how many stats rows it has. */
const stats = ( nxId ) =>
	wpJson(
		`global $wpdb; $t = \\NotificationX\\Core\\Database::$table_stats; echo wp_json_encode( array( "views" => (int) $wpdb->get_var( "SELECT SUM(views) FROM $t WHERE nx_id = ${ nxId }" ), "rows" => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE nx_id = ${ nxId }" ) ) );`
	);

/** The response without the flag that tells the frontend whether to send clicks. */
const withoutFlag = ( body ) => {
	const data = JSON.parse( body );
	delete data.settings.enable_analytics;
	return JSON.stringify( data );
};

const failures = [];
const check = ( ok, label ) => {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) {
		failures.push( label );
	}
};

const run = async ( nxId ) => {
	// 1. Analytics on.
	setEnabled( true );
	const on = await notice( nxId );
	check( 200 === on.status && Boolean( JSON.parse( on.body ).pressbar?.[ nxId ] ), 'analytics on: the bar is in the response' );
	await notice( nxId );
	let seen = stats( nxId );
	check( 2 === seen.views, `analytics on: two requests count two views (${ seen.views })` );

	// 2. Analytics off.
	setEnabled( false );
	const off = await notice( nxId );
	await notice( nxId );
	check( Boolean( JSON.parse( off.body ).pressbar?.[ nxId ] ), 'analytics off: the bar is still in the response' );
	check( false === JSON.parse( off.body ).settings.enable_analytics, 'analytics off: the response tells the frontend not to send clicks' );
	check( withoutFlag( off.body ) === withoutFlag( on.body ), 'analytics off: the rest of the response is unchanged' );
	seen = stats( nxId );
	check( 2 === seen.views && 1 === seen.rows, `analytics off: two more requests add no view and no row (${ seen.views } views, ${ seen.rows } row)` );

	// 3. Analytics on again.
	setEnabled( true );
	await notice( nxId );
	seen = stats( nxId );
	check( 3 === seen.views, `analytics on again: counting resumes (${ seen.views })` );
};

( async () => {
	const original = getEnabled();
	const nxId = createBar();
	try {
		await run( nxId );
	} finally {
		setEnabled( original );
		deleteBar( nxId );
	}
	check( getEnabled() === original, `"Enable Analytics" is back to ${ original }` );
	if ( failures.length ) {
		console.error( `\n${ failures.length } check(s) failed.` );
		process.exit( 1 );
	}
	console.log( '\nAll checks passed.' );
} )().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
