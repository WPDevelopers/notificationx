/**
 * Exit Intent → Design → Themes → Custom, nothing linked: "Build With
 * Elementor" (or Install Elementor) and "Build With Gutenberg" sit side by side
 * at the same width and stack when the row is too narrow. The Notification
 * Bar's buttons and every rule that styled these buttons before keep exactly
 * their declarations (fixture taken from admin.css before the change).
 */
const fs = require( 'fs' );
const path = require( 'path' );
const crypto = require( 'crypto' );
const { rules, decls } = require( '../css-rules' );

const all = rules( fs.readFileSync( path.join( __dirname, '../../../assets/admin/css/admin.css' ), 'utf8' ) );
const before = require( '../fixtures/admin-css-builder-buttons.json' ).rules;
const md5 = ( s ) => crypto.createHash( 'md5' ).update( s ).digest( 'hex' );

// admin.css is compiled inside #notificationx.
const TAB = '#notificationx .exit_intent_custom .wprf-tab-exit_intent_custom_tab.wprf-active';
const BUILD = [
	`${ TAB }>#wprf-modal-nx-exit-intent_with_elementor`,
	`${ TAB }>#wprf-modal-nx-exit-intent_with_gutenberg`,
	`${ TAB }>.wprf-control-wrapper.wprf-name-nx-exit-intent_with_elementor_install`,
];

describe( 'Exit Intent Custom tab: Build buttons side by side', () => {
	it( 'only the hidden state fields between the two Build buttons are taken out of the row', () => {
		expect( decls( all, `${ TAB }>#wprf-modal-nx-exit-intent_with_elementor~span:has(~#wprf-modal-nx-exit-intent_with_gutenberg)` ) ).toContain( 'display:none' );
		expect( decls( all, `${ TAB }>.wprf-name-nx-exit-intent_with_elementor_install~span:has(~#wprf-modal-nx-exit-intent_with_gutenberg)` ) ).toContain( 'display:none' );
		// No rule hides every hidden field (they keep the space under the buttons).
		expect( decls( all, `${ TAB }>span` ) ).not.toContain( 'display:none' );
	} );

	it.each( BUILD )( '%s is 240px wide and never grows or shrinks', ( selector ) => {
		const d = decls( all, selector );
		expect( d ).toEqual( expect.arrayContaining( [ 'flex:0 0 240px', 'max-width:100%' ] ) );
	} );

	it.each( BUILD )( 'the button fills %s, so both buttons are the same size', ( selector ) => {
		expect( decls( all, `${ selector } .wprf-button` ) ).toContain( 'width:100%' );
		expect( decls( all, `${ selector } .wprf-control-wrapper` ) ).toContain( 'width:100%' );
	} );

	it( 'the tab still wraps, so the buttons stack when both do not fit', () => {
		expect( decls( all, TAB ) ).toEqual( expect.arrayContaining( [ 'display:flex', 'flex-wrap:wrap' ] ) );
	} );

	it( 'the new rules are scoped to the Exit Intent Custom tab', () => {
		all.filter( ( r ) => r.decls.includes( 'flex:0 0 240px' ) ).forEach( ( r ) => {
			expect( r.selector.startsWith( `${ TAB }>` ) ).toBe( true );
			expect( r.selector ).not.toMatch( /nx-bar|nx_bar|press_bar/ );
		} );
	} );
} );

describe( 'every rule that styled these buttons before is unchanged', () => {
	it( 'the fixture covers the Notification Bar buttons', () => {
		expect( before.some( ( [ , selector ] ) => selector.includes( 'nx-bar_with' ) ) ).toBe( true );
		expect( before.length ).toBeGreaterThan( 50 );
	} );

	it.each( before.map( ( [ media, selector, hash ] ) => [ selector, media, hash ] ) )( '%s %s', ( selector, media, hash ) => {
		const found = all.filter( ( r ) => r.selector === selector && r.media === media ).map( ( r ) => md5( r.decls.join( ';' ) ) );
		expect( found ).toContain( hash );
	} );
} );
