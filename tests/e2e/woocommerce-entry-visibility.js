#!/usr/bin/env node
/**
 * End-to-end check that WooCommerce sales entries follow the state of their
 * product, and that each product is loaded once per request.
 *
 * An entry is hidden when its product is gone or not published. The check
 * loaded the product for every stored entry, which was most of the time of a
 * `/notice` request for a WooCommerce notification; now each product is
 * checked once per request. The script creates a WooCommerce sales
 * notification with 40 entries (30 for two published products, 5 for a draft
 * product, 5 for a product that does not exist) and sends real `/notice`
 * requests:
 *   1. only entries of published products are in the response,
 *   2. after the draft product is published its entries appear,
 *   3. after it is trashed they are gone again,
 *   4. one request loads each product once, not once per entry.
 *
 *   node tests/e2e/woocommerce-entry-visibility.js
 *
 * Needs WooCommerce active with at least two published products; it skips
 * otherwise. It creates one temporary notification ("NX E2E woo visibility")
 * with its entries and one temporary product ("NX E2E hidden draft"), and
 * deletes both when it finishes. Each `/notice` request counts one view for
 * the temporary notification.
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
const MISSING_PRODUCT = 999999999;

const wp = ( ...args ) =>
	execFileSync( 'wp', [ ...args, `--path=${ WP_PATH }` ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'inherit' ],
	} ).trim();

const wpJson = ( php, ...args ) => JSON.parse( wp( 'eval', php, ...args ).split( '\n' ).pop() );

/** POST /notice for one notification. Resolves the titles of the entries in the response. */
const shownTitles = ( nxId ) =>
	new Promise( ( resolve, reject ) => {
		const url = new URL( `${ SITE_URL }/wp-json/notificationx/v1/notice/?frontend=true` );
		const payload = JSON.stringify( { active: [ nxId ], deviceType: 'desktop' } );
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
				response.on( 'end', () => {
					if ( 200 !== response.statusCode ) {
						reject( new Error( `/notice answered ${ response.statusCode }: ${ body.slice( 0, 200 ) }` ) );
						return;
					}
					const data = JSON.parse( body );
					const group = { ...( data.active || {} ), ...( data.global || {} ) }[ nxId ];
					resolve( Object.values( group?.entries || {} ).map( ( entry ) => entry.title ) );
				} );
			}
		);
		request.on( 'error', reject );
		request.end( payload );
	} );

/** Create the draft product, the notification and its entries. */
const setUp = () =>
	wpJson(
		`if ( ! class_exists( "WooCommerce" ) ) { echo wp_json_encode( array( "skip" => "WooCommerce is not active" ) ); return; }
		$published = get_posts( array( "post_type" => "product", "post_status" => "publish", "numberposts" => 2, "fields" => "ids", "orderby" => "ID" ) );
		if ( count( $published ) < 2 ) { echo wp_json_encode( array( "skip" => "fewer than two published products" ) ); return; }
		$draft = wp_insert_post( array( "post_type" => "product", "post_status" => "draft", "post_title" => "NX E2E hidden draft" ) );
		$r = ( new \\NotificationX\\Abilities\\Manage\\CreateNotification() )->execute( array( "config" => array( "title" => "NX E2E woo visibility", "type" => "conversions", "source" => "woocommerce", "themes" => "conversions_theme-one" ) ) );
		if ( is_wp_error( $r ) ) { wp_delete_post( $draft, true ); fwrite( STDERR, $r->get_error_message() ); exit( 1 ); }
		$nx_id = (int) $r["nx_id"];
		\\NotificationX\\Core\\PostType::get_instance()->update_status( array( "nx_id" => $nx_id, "enabled" => true, "update_status" => true, "source" => "woocommerce" ) );
		$entries = \\NotificationX\\Admin\\Entries::get_instance();
		$entries->delete_entries( $nx_id );
		$add = function ( $product_id, $title, $age, $order ) use ( $entries, $nx_id ) {
			$entries->insert_entry( array( "nx_id" => $nx_id, "source" => "woocommerce", "entry_key" => "nx-e2e-$order", "data" => array( "status" => "wc-processing", "order_id" => 900000 + $order, "product_id" => $product_id, "title" => $title, "link" => get_permalink( $product_id ), "timestamp" => time() - $age, "first_name" => "E2E", "last_name" => "Buyer", "name" => "E2E B", "email" => "buyer@example.invalid" ) ) );
		};
		for ( $i = 0; $i < 30; $i++ ) { $add( $published[ $i % 2 ], "NX E2E shown " . ( $i % 2 ? "B" : "A" ), 3600 + $i * 60, $i ); }
		for ( $i = 0; $i < 5; $i++ ) { $add( $draft, "NX E2E hidden draft", 60 + $i, 100 + $i ); }
		for ( $i = 0; $i < 5; $i++ ) { $add( ${ MISSING_PRODUCT }, "NX E2E hidden missing", 30 + $i, 200 + $i ); }
		echo wp_json_encode( array( "nx_id" => $nx_id, "draft" => (int) $draft ) );`,
		`--user=${ ADMIN }`
	);

const tearDown = ( { nx_id: nxId, draft } ) =>
	wp( 'eval', `\\NotificationX\\Core\\PostType::get_instance()->delete_post( ${ nxId } ); wp_delete_post( ${ draft }, true );` );

const setProductStatus = ( id, status ) => wp( 'eval', `wp_update_post( array( "ID" => ${ id }, "post_status" => "${ status }" ) );` );

/** How many products one request loads, and how many entries it read. */
const productLoads = ( nxId ) =>
	wpJson(
		`do_action( "nx_ignore_analytics" ); $loads = 0; add_action( "woocommerce_product_read", function () use ( &$loads ) { $loads++; } );
		$entries = 0; add_filter( "nx_frontend_get_entries", function ( $e ) use ( &$entries ) { $entries = count( $e ); return $e; }, 999 );
		\\NotificationX\\FrontEnd\\FrontEnd::get_instance()->get_notifications_data( array( "active" => array( ${ nxId } ) ) );
		echo wp_json_encode( array( "loads" => $loads, "entries" => $entries ) );`
	);

const failures = [];
const check = ( ok, label ) => {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) {
		failures.push( label );
	}
};

const has = ( titles, text ) => titles.some( ( title ) => String( title ).includes( text ) );

const run = async ( { nx_id: nxId, draft } ) => {
	// 1. Draft and missing products are hidden.
	let titles = await shownTitles( nxId );
	check( has( titles, 'NX E2E shown A' ) && has( titles, 'NX E2E shown B' ), `entries of published products are shown (${ titles.length } entries)` );
	check( ! has( titles, 'hidden draft' ), 'entries of the draft product are hidden' );
	check( ! has( titles, 'hidden missing' ), 'entries of a product that does not exist are hidden' );

	// 2. Publish the draft: a new request sees the new state.
	setProductStatus( draft, 'publish' );
	titles = await shownTitles( nxId );
	check( has( titles, 'hidden draft' ), 'after publishing the product its entries are shown' );
	check( ! has( titles, 'hidden missing' ), 'entries of the missing product stay hidden' );

	// 3. Trash it again.
	setProductStatus( draft, 'trash' );
	titles = await shownTitles( nxId );
	check( ! has( titles, 'hidden draft' ), 'after trashing the product its entries are hidden again' );
	check( has( titles, 'NX E2E shown A' ), 'entries of the other products are still shown' );

	// 4. Three products exist among the entries (two published, one trashed);
	//    the missing one loads nothing.
	const { loads, entries } = productLoads( nxId );
	check( entries >= 40, `the request read all stored entries (${ entries })` );
	check( loads > 0 && loads <= 3, `one request loaded each product once (${ loads } loads for ${ entries } entries)` );
};

( async () => {
	const site = setUp();
	if ( site.skip ) {
		console.log( `SKIP  ${ site.skip }` );
		return;
	}
	try {
		await run( site );
	} finally {
		tearDown( site );
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
