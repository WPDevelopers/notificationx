#!/usr/bin/env node
/**
 * End-to-end check that the frontend `/notice` request does not depend on the
 * large cached builder fields (`nx_builder_fields`, about 840 KB).
 *
 * A store that cannot keep that value (for example an object cache with an
 * item size limit) used to rebuild the builder fields for every notification
 * read. The frontend now reads a small field list (`nx_builder_field_names`)
 * once per request. The script sends real `/notice` requests to the site:
 *   1. with no cache at all, which builds both values,
 *   2. with the large value removed behind the plugin's back, as a store that
 *      rejected it would leave things: the response is unchanged and the large
 *      value is not rebuilt,
 *   3. with both removed: the response is unchanged, apart from field defaults
 *      that hold the build time, and both are rebuilt once.
 * It also checks that the cached field list matches the cached builder fields,
 * and that the builder still gets its fields.
 *
 *   node tests/e2e/builder-field-names-cache.js
 *
 * It creates three temporary Notification Bars ("NX E2E field list 1" to 3) so
 * the request reads several notifications, and deletes them when it finishes.
 * Besides those it writes only the two cache values, which the plugin rebuilds
 * by itself. Each `/notice` request counts one view per notification shown, as
 * a page view does.
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
const RUNS = 5;
const BARS = 3;

const wp = ( ...args ) =>
	execFileSync( 'wp', [ ...args, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} ).trim();

const wpJson = ( php, ...args ) => JSON.parse( wp( 'eval', php, ...args ).split( '\n' ).pop() );

/** POST /notice as a Cross Domain Notice page does. Resolves { status, body, ms }. */
const notice = () =>
	new Promise( ( resolve, reject ) => {
		const url = new URL( `${ SITE_URL }/wp-json/notificationx/v1/notice/?frontend=true` );
		const payload = JSON.stringify( { all_active: true, deviceType: 'desktop', addon_scripts: true } );
		const started = process.hrtime.bigint();
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
				response.on( 'end', () =>
					resolve( { status: response.statusCode, body, ms: Number( process.hrtime.bigint() - started ) / 1e6 } )
				);
			}
		);
		request.on( 'error', reject );
		request.end( payload );
	} );

// Field defaults that hold the time the builder fields were built ("in 7
// days"). A notification with no stored value gets the default, so these
// change whenever the fields are rebuilt, with or without this change.
const BUILD_TIME_DEFAULTS = [ 'expire_time', 'countdown_start_date', 'countdown_end_date' ];

/** The response without the build-time defaults. */
const withoutBuildTime = ( body ) =>
	JSON.stringify( JSON.parse( body ), ( key, value ) => ( BUILD_TIME_DEFAULTS.includes( key ) ? undefined : value ) );

const median = ( values ) => [ ...values ].sort( ( a, b ) => a - b )[ Math.floor( values.length / 2 ) ];

/** Which of the two values the store holds right now. */
const cacheState = () =>
	wpJson(
		'echo wp_json_encode( array( "large" => (bool) get_transient( "nx_builder_fields" ), "list" => is_array( get_transient( \\NotificationX\\NotificationX::FIELD_NAMES_TRANSIENT ) ) ) );'
	);

/** Remove a cached value without running the plugin's hooks. */
const dropSilently = ( transient ) =>
	wp(
		'eval',
		`global $wpdb; if ( wp_using_ext_object_cache() ) { wp_cache_delete( "${ transient }", "transient" ); } else { $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name IN ( %s, %s )", "_transient_${ transient }", "_transient_timeout_${ transient }" ) ); }`
	);

/** Create the temporary bars, enabled and shown everywhere. Returns their ids. */
const createBars = () =>
	wpJson(
		`$ids = array(); for ( $i = 1; $i <= ${ BARS }; $i++ ) { $r = ( new \\NotificationX\\Abilities\\Manage\\CreateNotification() )->execute( array( "config" => array( "title" => "NX E2E field list $i", "type" => "notification_bar", "source" => "press_bar", "themes" => "press_bar_theme-one", "press_content" => "NX E2E bar $i" ) ) ); if ( is_wp_error( $r ) ) { fwrite( STDERR, $r->get_error_message() ); exit( 1 ); } \\NotificationX\\Core\\PostType::get_instance()->update_status( array( "nx_id" => $r["nx_id"], "enabled" => true, "update_status" => true, "source" => "press_bar" ) ); $ids[] = (int) $r["nx_id"]; } echo wp_json_encode( $ids );`,
		`--user=${ ADMIN }`
	);

const deleteBars = ( ids ) =>
	wp( 'eval', `foreach ( array( ${ ids.join( ',' ) } ) as $id ) { \\NotificationX\\Core\\PostType::get_instance()->delete_post( $id ); }` );

const failures = [];
const check = ( ok, label ) => {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) {
		failures.push( label );
	}
};

const series = async () => {
	const results = [];
	for ( let i = 0; i < RUNS; i++ ) {
		results.push( await notice() );
	}
	return results;
};

const run = async () => {
	// 1. No cache at all.
	wp( 'transient', 'delete', 'nx_builder_fields' );
	check( ! cacheState().large && ! cacheState().list, 'clearing the builder fields clears the field list too' );

	const cold = await notice();
	check( 200 === cold.status, `/notice answers 200 with no cache (${ cold.status })` );
	const groups = JSON.parse( cold.body );
	const shown = [ 'global', 'active', 'pressbar', 'gdpr', 'shortcode', 'popup', 'exit_intent' ].reduce(
		( total, group ) => total + Object.keys( groups[ group ] || {} ).length,
		0
	);
	check( shown >= BARS, `the response shows the ${ BARS } temporary bars (${ shown } notifications)` );
	let state = cacheState();
	check( state.large && state.list, 'the first request caches the builder fields and the field list' );

	const warm = await series();
	check( warm.every( ( r ) => r.body === cold.body ), 'warm responses equal the first response' );

	// 2. The store did not keep the large value.
	dropSilently( 'nx_builder_fields' );
	state = cacheState();
	check( ! state.large && state.list, 'large value removed, field list still cached' );

	const withoutLarge = await series();
	check( withoutLarge.every( ( r ) => r.body === cold.body ), 'responses are unchanged without the large value' );
	state = cacheState();
	check( ! state.large, 'the frontend did not rebuild the large value' );
	check( state.list, 'the field list is still cached' );

	// 3. Nothing is kept.
	dropSilently( 'nx_builder_field_names' );
	const withoutBoth = await notice();
	check(
		withoutBuildTime( withoutBoth.body ) === withoutBuildTime( cold.body ),
		'response is unchanged with neither value cached (build-time defaults aside)'
	);
	state = cacheState();
	check( state.large && state.list, 'one request rebuilt both values' );

	// Both values were built by the same request, so the field list must be
	// exactly what the cached builder fields give. (A build in another context
	// differs: some defaults hold the build time or the request scheme.)
	const built = wpJson(
		'$nx = \\NotificationX\\NotificationX::get_instance(); $tabs = $nx->get_tab(); echo wp_json_encode( array( "same" => $nx->get_field_names() === $nx->_get_field_names( $tabs["tabs"] ), "fields" => count( $nx->get_field_names() ), "builder" => ! empty( $tabs["tabs"] ) ) );'
	);
	check( built.same && built.fields > 0, `the cached field list matches the cached builder fields (${ built.fields } fields)` );
	check( built.builder, 'the builder still gets its fields' );

	const ms = ( results ) => `${ median( results.map( ( r ) => r.ms ) ).toFixed( 0 ) } ms`;
	console.log(
		`\nTime to complete /notice: no cache ${ cold.ms.toFixed( 0 ) } ms, warm ${ ms( warm ) }, without the large value ${ ms( withoutLarge ) }, neither cached ${ withoutBoth.ms.toFixed( 0 ) } ms (${ shown } notifications shown)`
	);

};

( async () => {
	const bars = createBars();
	try {
		await run();
	} finally {
		deleteBars( bars );
	}
	if ( failures.length ) {
		console.error( `\n${ failures.length } check(s) failed.` );
		process.exit( 1 );
	}
	console.log( '\nAll checks passed.' );
} )().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
