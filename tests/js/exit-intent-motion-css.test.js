/**
 * Exit Intent Show / Hide animations: the backdrop fade in frontend.css and
 * crossSite.css. It only applies with an animation class (never with "None"),
 * and the reduced-motion rule covers the overlay and everything in it.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { rules, decls } = require( './css-rules' );

const CSS_DIR = path.join( __dirname, '../../assets/public/css' );

describe.each( [ 'frontend.css', 'crossSite.css' ] )( '%s', ( file ) => {
	const css = fs.readFileSync( path.join( CSS_DIR, file ), 'utf8' );
	const all = rules( css );

	it( 'the backdrop fades in with an entrance', () => {
		expect( decls( all, '.nx-exit-intent-overlay.nx-exit-intent-anim-in' ) ).toContain( 'animation:nxExitIntentBackdropIn .3s ease-out both' );
		expect( css ).toMatch( /@keyframes nxExitIntentBackdropIn\{0%\{opacity:0\}to\{opacity:1\}\}/ );
	} );

	it( 'the backdrop fades out with an exit, and lets clicks through while leaving', () => {
		const out = decls( all, '.nx-exit-intent-overlay.nx-exit-intent-anim-out' );
		expect( out ).toEqual( expect.arrayContaining( [ 'animation:nxExitIntentBackdropOut .5s ease-in both', 'pointer-events:none' ] ) );
		expect( css ).toMatch( /@keyframes nxExitIntentBackdropOut\{0%\{opacity:1\}to\{opacity:0\}\}/ );
	} );

	it( 'nothing animates the overlay without an animation class', () => {
		decls( all, '.nx-exit-intent-overlay' ).forEach( ( d ) => expect( d ).not.toMatch( /^animation/ ) );
	} );

	it( 'reduced motion shortens the overlay and the card', () => {
		const media = '@media (prefers-reduced-motion:reduce)';
		[ '.nx-exit-intent-overlay', '.nx-exit-intent-overlay *' ].forEach( ( selector ) => {
			expect( decls( all, selector, media ) ).toEqual( expect.arrayContaining( [ 'animation-duration:1ms!important', 'animation-delay:0s!important' ] ) );
		} );
	} );
} );
