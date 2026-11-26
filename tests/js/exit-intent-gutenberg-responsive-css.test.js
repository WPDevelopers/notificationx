/**
 * Exit Intent block designs on tablets and phones (frontend.css and
 * crossSite.css): from 768px the desktop layout stays (core stacks Columns
 * below 782px); below 768px buttons go one per row, full width. Only inside
 * the block-design popup.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { rules, decls } = require( './css-rules' );

const CSS_DIR = path.join( __dirname, '../../assets/public/css' );
const TABLET = '@media (min-width:768px) and (max-width:781px)';
const PHONE = '@media (max-width:767px)';
const COLS = '.nx-exit-intent-gutenberg-body .wp-block-columns:not(.is-not-stacked-on-mobile)';
const BTNS = '.nx-exit-intent-gutenberg-body .wp-block-buttons';

describe.each( [ 'frontend.css', 'crossSite.css' ] )( '%s', ( file ) => {
	const all = rules( fs.readFileSync( path.join( CSS_DIR, file ), 'utf8' ) );

	it( 'tablets keep columns side by side', () => {
		expect( decls( all, COLS, TABLET ) ).toContain( 'flex-wrap:nowrap!important' );
		expect( decls( all, `${ COLS }>.wp-block-column`, TABLET ) ).toEqual( expect.arrayContaining( [ 'flex-basis:0!important', 'flex-grow:1' ] ) );
		expect( decls( all, `${ COLS }>.wp-block-column[style*=flex-basis]`, TABLET ) ).toContain( 'flex-grow:0' );
	} );

	it( 'phones put buttons one per row, full width', () => {
		expect( decls( all, BTNS, PHONE ) ).toEqual( expect.arrayContaining( [ 'flex-direction:column', 'align-items:stretch' ] ) );
		expect( decls( all, `${ BTNS }>.wp-block-button`, PHONE ) ).toContain( 'width:100%' );
		expect( decls( all, `${ BTNS }>.wp-block-button .wp-block-button__link`, PHONE ) ).toEqual( expect.arrayContaining( [ 'width:100%', 'box-sizing:border-box' ] ) );
	} );

	it( 'the rules stay inside the block-design popup', () => {
		all.filter( ( r ) => r.media.replace( /\s/g, '' ) === TABLET.replace( /\s/g, '' ) || ( r.media.replace( /\s/g, '' ) === PHONE.replace( /\s/g, '' ) && r.selector.includes( 'wp-block' ) ) ).forEach( ( r ) => {
			expect( r.selector.startsWith( '.nx-exit-intent-gutenberg-body ' ) ).toBe( true );
		} );
	} );
} );
