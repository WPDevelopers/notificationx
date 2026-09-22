#!/usr/bin/env node
/**
 * Checks the built frontend bundles for size and for admin-only code.
 *
 * Run after `npm run frontend` (production build into assets/):
 *
 *   npm run check:bundle
 *   node bin/check-frontend-bundle.js --dir=nxbuild   # check a dev build
 *
 * frontend.js loads on every page that shows a notification, and caching
 * plugins may be forced to load it eagerly. See
 * docs/features/frontend-performance/ for the history and the budget.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const zlib = require( 'zlib' );

const KB = 1024;

// Raw (unminified size on disk) budgets. 3.3.1 shipped 1,285,035 bytes;
// after the B1 import cleanup the bundle is ~456 KB. The headroom is for
// normal feature growth; raise it deliberately, not to silence a failure.
const BUDGETS = {
	'public/js/frontend.js': 550 * KB,
	'public/js/crossSite.js': 550 * KB,
};

// Strings that only appear when an admin-only library is bundled.
const FORBIDDEN_MARKERS = {
	'swal2-container': 'sweetalert2 (admin alerts)',
	Toastify__: 'react-toastify (admin toasts)',
	'Africa/Abidjan': 'moment-timezone data (pulled by @wordpress/date)',
};

const dirArg = process.argv.find( ( a ) => a.startsWith( '--dir=' ) );
const baseDir = path.resolve( __dirname, '..', dirArg ? dirArg.slice( 6 ) : 'assets' );

let failed = false;

for ( const [ file, budget ] of Object.entries( BUDGETS ) ) {
	const full = path.join( baseDir, file );
	if ( ! fs.existsSync( full ) ) {
		console.error( `✖ ${ file }: not found in ${ baseDir }. Build first (npm run frontend).` );
		failed = true;
		continue;
	}

	const source = fs.readFileSync( full );
	const gzip = zlib.gzipSync( source ).length;
	const sizeText = `${ ( source.length / KB ).toFixed( 1 ) } KB raw, ${ ( gzip / KB ).toFixed( 1 ) } KB gzip`;

	if ( source.length > budget ) {
		console.error( `✖ ${ file }: ${ sizeText } exceeds budget of ${ budget / KB } KB` );
		failed = true;
	} else {
		console.log( `✔ ${ file }: ${ sizeText } (budget ${ budget / KB } KB)` );
	}

	const text = source.toString( 'utf8' );
	for ( const [ marker, library ] of Object.entries( FORBIDDEN_MARKERS ) ) {
		if ( text.includes( marker ) ) {
			console.error( `✖ ${ file }: contains "${ marker }", i.e. ${ library }. A frontend file imports admin code.` );
			failed = true;
		}
	}
}

if ( failed ) {
	console.error( '\nSee docs/features/frontend-performance/03-guardrails.md' );
	process.exit( 1 );
}
