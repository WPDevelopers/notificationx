/**
 * Exit Intent "Build With Gutenberg": the CSS that ships in frontend.css and
 * crossSite.css for the block-editor popup — scrolling for long designs, the ×
 * placed with logical properties (RTL) and inside the card on phones — and the
 * Elementor popup's rules left exactly as they were.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const CSS_DIR = path.join( __dirname, '../../assets/public/css' );

// Flat list of { media, selector, decls } from minified CSS.
function rules( css ) {
	css = css.replace( /\/\*[\s\S]*?\*\//g, '' );
	const out = [];
	const walk = ( text, media ) => {
		let k = 0;
		while ( k < text.length ) {
			const open = text.indexOf( '{', k );
			if ( open < 0 ) break;
			const prelude = text.slice( k, open ).trim();
			let depth = 1;
			let j = open + 1;
			while ( j < text.length && depth ) {
				if ( text[ j ] === '{' ) depth++;
				else if ( text[ j ] === '}' ) depth--;
				j++;
			}
			const body = text.slice( open + 1, j - 1 );
			if ( prelude.startsWith( '@media' ) || prelude.startsWith( '@supports' ) ) {
				walk( body, prelude );
			} else if ( ! prelude.startsWith( '@' ) ) {
				const decls = body.split( ';' ).map( ( d ) => d.trim() ).filter( Boolean );
				prelude.split( ',' ).forEach( ( selector ) => out.push( { media: media || '', selector: selector.trim(), decls } ) );
			}
			k = j;
		}
	};
	walk( css, '' );
	return out;
}

const decls = ( all, selector, media = '' ) =>
	all.filter( ( r ) => r.selector === selector && r.media.replace( /\s/g, '' ) === media.replace( /\s/g, '' ) ).flatMap( ( r ) => r.decls );

describe.each( [ 'frontend.css', 'crossSite.css' ] )( '%s', ( file ) => {
	const all = rules( fs.readFileSync( path.join( CSS_DIR, file ), 'utf8' ) );

	it( 'the popup takes its width from the design, 600px by default', () => {
		expect( decls( all, '.nx-exit-intent-gutenberg' ) ).toContain( 'max-width:var(--nx-exit-width,600px)' );
	} );

	it( 'long designs scroll inside the popup', () => {
		const body = decls( all, '.nx-exit-intent-gutenberg .nx-exit-intent-gutenberg-body' );
		expect( body ).toContain( 'max-height:calc(100vh - 32px)' );
		expect( body ).toContain( 'max-height:calc(100dvh - 32px)' );
		expect( body ).toContain( 'overflow-y:auto' );
		expect( body ).toContain( 'overscroll-behavior:contain' );
		expect( body ).toContain( 'border-radius:var(--nx-exit-radius,0)' );
	} );

	it( 'the × uses logical properties, so it moves to the left in RTL', () => {
		const close = decls( all, '.nx-exit-intent-gutenberg .nx-exit-intent-close' );
		expect( close ).toContain( 'inset-inline-end:-16px' );
		expect( close.filter( ( d ) => /^(right|left):/.test( d ) ) ).toEqual( [] );
	} );

	it( 'on phones the × sits inside the card as a 44px target', () => {
		const close = decls( all, '.nx-exit-intent-gutenberg .nx-exit-intent-close', '@media (max-width:480px)' );
		expect( close ).toEqual( expect.arrayContaining( [ 'top:8px', 'inset-inline-end:8px', 'width:44px', 'height:44px' ] ) );
	} );

	it( 'Gutenberg rules never target the Elementor or built-in popups', () => {
		all.filter( ( r ) => r.selector.includes( 'nx-exit-intent-gutenberg' ) ).forEach( ( r ) => {
			expect( r.selector ).toMatch( /^\.nx-exit-intent-(gutenberg|has-gutenberg)/ );
			expect( r.selector ).not.toMatch( /elementor|nx-exit-intent-popup|nx-exit-intent-theme/ );
		} );
	} );

	it( 'the Elementor close button keeps its rules', () => {
		const close = decls( all, '.nx-exit-intent-elementor .nx-exit-intent-close' );
		expect( close ).toEqual( expect.arrayContaining( [ 'top:-16px', 'right:-16px', 'width:32px', 'height:32px' ] ) );
		expect( close.filter( ( d ) => d.startsWith( 'inset-inline' ) ) ).toEqual( [] );
		expect( decls( all, '.nx-exit-intent-elementor' ) ).toContain( 'max-width:var(--nx-exit-width,540px)' );
	} );
} );

describe( 'admin.css: the Gutenberg controls look like the Elementor ones', () => {
	const css = fs.readFileSync( path.join( __dirname, '../../assets/admin/css/admin.css' ), 'utf8' );
	it( 'two design cards per row in the picker', () => {
		expect( css ).toMatch( /\.wprf-name-gutenberg_exit_theme \.wprf-control \.wprf-row \.wprf-column\.wprf-column-2\{width:50%/ );
	} );
	it( 'Edit and Build With Gutenberg are light pills with the block-editor glyph', () => {
		// The build inlines the glyph (gutenberg-icon.svg) as a data URI.
		expect( css ).toMatch( /wprf-name-gutenberg_edit_link \.wprf-button\{[^}]*background-image:url\(data:image\/svg\+xml/ );
		expect( css ).toMatch( /wprf-name-gutenberg_edit_link \.wprf-button\{[^}]*padding-left:40px/ );
	} );
	it( 'Remove is a red link', () => {
		expect( css ).toMatch( /wprf-name-nx-exit-intent_with_gutenberg-remove \.wprf-button\{[^}]*color:#fb2a04/ );
	} );
} );
