/**
 * The frontend runtime must never import @wordpress/hooks: filters go through
 * nxApplyFilters() (frontend/core/hooks.ts), and on Cross Domain Notice sites
 * there is no wp-hooks script at all (see docs/api/frontend-js-hooks.md).
 * The package is not in frontend-import-boundary.test.js's forbidden list, so
 * this walks the same graph from each frontend webpack entry and checks it.
 *
 * Only import/require/export-from statements count: the comments in
 * core/hooks.ts and core/addons.ts that mention the package do not.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '../..' );
const NX = path.join( ROOT, 'nxdev/notificationx' );
const ENTRIES = [ 'frontend/index.tsx', 'frontend/crossSite.tsx' ].map( ( p ) => path.join( NX, p ) );
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

const walk = () => {
	const seen = new Set();
	const imports = []; // [ file, request ]
	const stack = [ ...ENTRIES ];
	while ( stack.length ) {
		const file = stack.pop();
		if ( seen.has( file ) ) continue;
		seen.add( file );
		const source = fs.readFileSync( file, 'utf8' );
		for ( const match of source.matchAll( IMPORT_RE ) ) {
			const request = match[ 1 ];
			imports.push( [ file, request ] );
			if ( /\.(s?css|svg|png|jpe?g|gif|json)$/.test( request ) ) continue;
			if ( request.startsWith( '.' ) ) {
				const resolved = resolveLocal( file, request );
				if ( resolved ) stack.push( resolved );
			}
		}
	}
	return { seen, imports };
};

describe( 'frontend runtime and @wordpress/hooks', () => {
	const { seen, imports } = walk();
	const rel = ( f ) => path.relative( ROOT, f );

	it( 'walks the real frontend source, including the Verified Cards', () => {
		expect( seen.size ).toBeGreaterThan( 40 );
		[
			'frontend/themes/vc/VerifiedCard.tsx',
			'frontend/themes/vc/useVcLineScroll.ts',
			'frontend/themes/vc/vcScrollPlan.ts',
			'frontend/themes/vc/VcCredit.tsx',
			'frontend/themes/vc/isVcTheme.ts',
		].forEach( ( f ) => expect( seen.has( path.join( NX, f ) ) ).toBe( true ) );
	} );

	it( 'never imports @wordpress/hooks from any reachable file', () => {
		const offenders = imports
			.filter( ( [ , request ] ) => request === '@wordpress/hooks' || request.startsWith( '@wordpress/hooks/' ) )
			.map( ( [ file, request ] ) => `${ rel( file ) } -> ${ request }` );
		expect( offenders ).toEqual( [] );
	} );

	it( 'keeps the Verified Card files off core/index (import cycle)', () => {
		const vcDir = path.join( NX, 'frontend/themes/vc' ) + path.sep;
		const coreIndex = EXTENSIONS.map( ( ext ) => path.join( NX, 'frontend/core/index' + ext ) );
		const offenders = imports
			.filter( ( [ file, request ] ) => file.startsWith( vcDir ) && request.startsWith( '.' ) )
			.filter( ( [ file, request ] ) => coreIndex.includes( resolveLocal( file, request ) ) )
			.map( ( [ file, request ] ) => `${ rel( file ) } -> ${ request }` );
		expect( offenders ).toEqual( [] );
	} );
} );
