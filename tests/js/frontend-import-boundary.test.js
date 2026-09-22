/**
 * Guards the frontend bundle against admin code.
 *
 * frontend.js is loaded on every page that shows a notification. In 3.3.1 a
 * few frontend files imported helpers from nxdev/notificationx/core/, which
 * dragged in the admin hooks, @wordpress/date and the full moment-timezone
 * database (~700 KB), plus sweetalert2 and react-toastify. See
 * docs/features/frontend-performance/.
 *
 * This test walks the import graph from each frontend webpack entry and fails
 * if it reaches a local file outside the allowed folders, or a package that
 * only the admin app should use.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '../..' );
const NX = path.join( ROOT, 'nxdev/notificationx' );

// The entries declared in webpack.frontend.config.js.
const ENTRIES = [
	'frontend/index.tsx',
	'frontend/crossSite.tsx',
	'frontend/flashing-tab.ts',
].map( ( p ) => path.join( NX, p ) );

// Local folders the frontend may import from.
const ALLOWED_DIRS = [ 'frontend', 'shared', 'icons' ].map( ( d ) => path.join( NX, d ) + path.sep );

// Packages that must never reach the frontend bundle.
const FORBIDDEN_PACKAGES = [
	'sweetalert2',
	'react-toastify',
	'moment-timezone',
	'@wordpress/date',
	'@wordpress/components',
	'@wordpress/data',
	'quickbuilder',
	'react-router',
	'react-router-dom',
	'react-select',
];

const EXTENSIONS = [ '.ts', '.tsx', '.js', '.jsx' ];
const IMPORT_RE = /(?:import\s[^'"]*?from\s*|import\s*\(\s*|import\s+|require\s*\(\s*|export\s[^'"]*?from\s*)['"]([^'"]+)['"]/g;

const resolveLocal = ( fromFile, request ) => {
	const base = path.resolve( path.dirname( fromFile ), request );
	const candidates = [
		base,
		...EXTENSIONS.map( ( ext ) => base + ext ),
		...EXTENSIONS.map( ( ext ) => path.join( base, 'index' + ext ) ),
	];
	return candidates.find( ( c ) => fs.existsSync( c ) && fs.statSync( c ).isFile() );
};

const packageName = ( request ) =>
	request.startsWith( '@' ) ? request.split( '/' ).slice( 0, 2 ).join( '/' ) : request.split( '/' )[ 0 ];

const walk = () => {
	const seen = new Set();
	const localViolations = [];
	const packageViolations = [];
	const stack = [ ...ENTRIES ];

	while ( stack.length ) {
		const file = stack.pop();
		if ( seen.has( file ) ) {
			continue;
		}
		seen.add( file );

		const source = fs.readFileSync( file, 'utf8' );
		for ( const match of source.matchAll( IMPORT_RE ) ) {
			const request = match[ 1 ];
			if ( /\.(s?css|svg|png|jpe?g|gif|json)$/.test( request ) ) {
				continue;
			}
			if ( request.startsWith( '.' ) ) {
				const resolved = resolveLocal( file, request );
				if ( ! resolved ) {
					continue; // Dynamic template paths such as moment/locale are not local.
				}
				if ( ! ALLOWED_DIRS.some( ( dir ) => resolved.startsWith( dir ) ) ) {
					localViolations.push( `${ path.relative( ROOT, file ) } -> ${ path.relative( ROOT, resolved ) }` );
				}
				stack.push( resolved );
			} else if ( FORBIDDEN_PACKAGES.includes( packageName( request ) ) ) {
				packageViolations.push( `${ path.relative( ROOT, file ) } -> ${ request }` );
			}
		}
	}
	return { seen, localViolations, packageViolations };
};

describe( 'frontend import boundary', () => {
	const { seen, localViolations, packageViolations } = walk();

	it( 'walks the real frontend source', () => {
		// Sanity check that the walker follows imports at all.
		expect( seen.size ).toBeGreaterThan( 50 );
		expect( [ ...seen ].some( ( f ) => f.endsWith( 'frontend/core/GDPR.tsx' ) ) ).toBe( true );
	} );

	it( 'imports local files only from frontend/, shared/ and icons/', () => {
		expect( localViolations ).toEqual( [] );
	} );

	it( 'never imports admin-only packages', () => {
		expect( packageViolations ).toEqual( [] );
	} );

	it( 'keeps shared/helpers.ts free of imports', () => {
		const source = fs.readFileSync( path.join( NX, 'shared/helpers.ts' ), 'utf8' );
		expect( source.match( IMPORT_RE ) ).toBeNull();
	} );
} );
