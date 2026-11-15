/**
 * Overhang (stacked cards): checks on the built frontend.css and crossSite.css.
 *
 * Parts that stick out of a card (the link button, badges, scaled images) declare
 * --nx-overhang-top / --nx-overhang-bottom on the card, and the corner containers add them
 * to the card's margins (nxdev/notificationx/frontend/scss/_container.scss). Layout is checked
 * in a browser by tests/visual/overhang; this checks the CSS that ships:
 * - every gap adds the bottom overhang and keeps its old value when there is none,
 * - every corner adds room above each card and below the last one, in both spacing modes,
 * - each declared overhang matches the offset of the part it is for.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const CSS_DIR = path.join( __dirname, '../../assets/public/css' );
const FILES = [ 'frontend.css', 'crossSite.css' ];
const CORNERS = [ 'bottom_left', 'bottom_right', 'top_left', 'top_right' ];

// Flat list of { media, selectors, decls } from minified or pretty CSS.
function parse( css ) {
	css = css.replace( /\/\*[\s\S]*?\*\//g, '' );
	const rules = [];
	const walk = ( text, media ) => {
		let k = 0;
		while ( k < text.length ) {
			const open = text.indexOf( '{', k );
			if ( open < 0 ) break;
			const prelude = text.slice( k, open ).trim();
			let depth = 1, j = open + 1;
			while ( j < text.length && depth ) {
				if ( text[ j ] === '{' ) depth++;
				else if ( text[ j ] === '}' ) depth--;
				j++;
			}
			const body = text.slice( open + 1, j - 1 );
			if ( prelude.startsWith( '@media' ) || prelude.startsWith( '@supports' ) ) {
				walk( body, prelude );
			} else if ( ! prelude.startsWith( '@' ) ) {
				const decls = {};
				body.split( ';' ).forEach( ( d ) => {
					const at = d.indexOf( ':' );
					if ( at > 0 ) decls[ d.slice( 0, at ).trim() ] = d.slice( at + 1 ).trim();
				} );
				// Split the selector list on top-level commas only (not inside :has(), :is()).
				const selectors = [];
				let level = 0, start = 0;
				for ( let p = 0; p < prelude.length; p++ ) {
					if ( prelude[ p ] === '(' ) level++;
					else if ( prelude[ p ] === ')' ) level--;
					else if ( prelude[ p ] === ',' && ! level ) { selectors.push( prelude.slice( start, p ).trim() ); start = p + 1; }
				}
				selectors.push( prelude.slice( start ).trim() );
				rules.push( { media: media || '', selectors: selectors.map( norm ), decls } );
			}
			k = j;
		}
	};
	walk( css, '' );
	return rules;
}

// Same spelling for the minified and the source form: no spaces after commas or around `>`,
// `:after` for `::after`, and a 0px fallback for the minifier's unitless 0.
const norm = ( s ) => s.replace( /\s*,\s*/g, ',' ).replace( /\s*>\s*/g, '>' ).replace( /::(before|after)/g, ':$1' ).replace( /\s+/g, ' ' ).trim();
const value = ( v ) => ( v || '' ).replace( /\s*!important/, '' ).replace( /\s*,\s*/g, ',' ).replace( /,0\)/g, ',0px)' ).replace( /\s+/g, ' ' ).trim();

// Value of `prop` in the rules whose selector matches `test` (string: exact selector).
function find( rules, test, prop, media = null ) {
	const match = typeof test === 'string' ? ( s ) => s === norm( test ) : test;
	return rules
		.filter( ( r ) => ( media === null || r.media.includes( media ) ) && r.decls[ prop ] !== undefined && r.selectors.some( match ) )
		.map( ( r ) => value( r.decls[ prop ] ) );
}

describe.each( FILES )( 'overhang in %s', ( file ) => {
	const css = fs.readFileSync( path.join( CSS_DIR, file ), 'utf8' );
	const rules = parse( css );

	test( 'the new-spacing gap adds the bottom overhang (12px without one)', () => {
		for ( const corner of CORNERS ) {
			for ( const item of [ 'notification-item', 'notification-res-item' ] ) {
				expect( find( rules, `.notificationx-frontend.nx-spacing-v2 .nx-container.nxc-${ corner } .${ item }:not(:last-child)`, 'margin-bottom' ) )
					.toEqual( [ 'calc(12px + var(--nx-overhang-bottom,0px))' ] );
			}
		}
	} );

	test( 'the legacy gaps add the bottom overhang (30px / 20px without one)', () => {
		expect( find( rules, '.notification-item:not(.notification-item:last-child)', 'margin-bottom' ) )
			.toContain( 'calc(30px + var(--nx-overhang-bottom,0px))' );
		expect( find( rules, '.notificationx-frontend .nx-container .notification-res-item:not(:last-child)', 'margin-bottom' ) )
			.toEqual( [ 'calc(20px + var(--nx-overhang-bottom,0px))' ] );
	} );

	test( 'every corner adds room above each card and below the last one, in both modes', () => {
		for ( const corner of CORNERS ) {
			for ( const item of [ 'notification-item', 'notification-res-item' ] ) {
				const card = `.notificationx-frontend .nx-container.nxc-${ corner }>.${ item }`;
				expect( find( rules, card, 'margin-top' ) ).toEqual( [ 'var(--nx-overhang-top,0px)' ] );
				expect( find( rules, `${ card }:last-child`, 'margin-bottom' ) ).toEqual( [ 'var(--nx-overhang-bottom,0px)' ] );
			}
		}
	} );

	test( 'every use of the overhang falls back to 0, so cards without one keep their spacing', () => {
		// Three gaps, the room above each card and below the last one.
		const uses = css.match( /var\(\s*--nx-overhang-(top|bottom)[^)]*\)/g ) || [];
		expect( uses.length ).toBe( 5 );
		uses.forEach( ( use ) => expect( use.replace( /\s/g, '' ) ).toMatch( /^var\(--nx-overhang-(top|bottom),0(px)?\)$/ ) );
	} );

	test( 'the old phone-only room for the link button is gone', () => {
		expect( find( rules, ( s ) => /\.notification-item\.button-link$/.test( s ), 'margin-top' ) ).toEqual( [] );
	} );

	// [ card selector that declares, property, value, selector of the part, its property, its value ]
	const PAIRS = [
		[ '.notification-item.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '35px', '.notification-item.button-link .notificationx-link', 'top', '-35px' ],
		[ '.notification-item.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '35px', '.notification-item.button-link .yt-notificationx-link', 'top', '-35px' ],
		[ '.notification-item.themes-conversions_conv-theme-thirteen.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '30px', '.notification-item.themes-conversions_conv-theme-thirteen.button-link .notificationx-link', 'top', '-30px' ],
		[ '.notification-item.themes-conversions_conv-theme-fifteen.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '16px', '.notification-item.themes-conversions_conv-theme-fifteen.button-link .notificationx-link', 'top', '-16px' ],
		[ '.notification-item.themes-conversions_conv-theme-sixteen.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '14px', '.notification-item.themes-conversions_conv-theme-sixteen.button-link .notificationx-link', 'top', '-14px' ],
		// Themes 12 and 14 keep the link inside the card.
		[ '.notification-item.themes-conversions_conv-theme-twelve.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '0px', '.notification-item.themes-conversions_conv-theme-twelve.button-link .notificationx-link', 'top', '50%' ],
		[ '.notification-item.themes-conversions_conv-theme-fourteen.button-link:has(.notificationx-link,.yt-notificationx-link)', '--nx-overhang-top', '0px', '.notification-item.themes-conversions_conv-theme-fourteen.button-link .notificationx-link', 'top', 'auto' ],
		[ '.notification-item.themes-conversions_conv-theme-seven', '--nx-overhang-top', '10px', '.notification-item.themes-conversions_conv-theme-seven .notificationx-image:after', 'top', '-10px' ],
		[ '.themes-conv-theme-nine', '--nx-overhang-top', '10px', '.themes-conv-theme-nine .notificationx-image:after', 'top', '-10px' ],
		[ '.themes-youtube_channel-1.button-link .notificationx-link-wrapper', 'top', '-35px', '.themes-youtube_channel-2.button-link .notificationx-link-wrapper', 'top', '-35px' ],
	];
	test.each( PAIRS )( '%s declares %s: %s for its part', ( card, prop, val, part, partProp, partVal ) => {
		expect( find( rules, card, prop ) ).toContain( val );
		expect( find( rules, part, partProp ) ).toContain( partVal );
	} );

	// Pro mobile cards (their styles live in free).
	const res = ( theme ) => `.notificationx-frontend .nx-container .notification-res-item.${ theme }`;
	const RES_PAIRS = [
		[ `${ res( 'type-res-video' ) }.button-link:has(.notificationx-link,.yt-notificationx-link)`, '--nx-overhang-top', '18px', `${ res( 'type-res-video' ) }.button-link .notificationx-link`, 'top', '-18px' ],
		[ `${ res( 'type-res-video' ) }.button-link:has(.yt-notificationx-link)`, '--nx-overhang-top', '24px', `${ res( 'type-res-video' ) }.button-link .yt-notificationx-link`, 'top', '-24px' ],
		[ `${ res( 'themes-conversions_res-theme-seven' ) }.button-link:has(.notificationx-link,.yt-notificationx-link)`, '--nx-overhang-top', '21px', `${ res( 'themes-conversions_res-theme-seven' ) }.button-link .notificationx-link`, 'top', '-21px' ],
		[ `${ res( 'themes-conversions_res-theme-three' ) }:has(.notificationx-image-badge.second-span)`, '--nx-overhang-bottom', '7px', `${ res( 'themes-conversions_res-theme-three' ) } .notificationx-image .notificationx-image-badge`, 'bottom', '-7px' ],
		[ res( 'themes-comments_res-theme-five' ), '--nx-overhang-top', '3px', `${ res( 'themes-comments_res-theme-five' ) } .notificationx-image::after`, 'top', '-3px' ],
		[ res( 'themes-comments_res-theme-six' ), '--nx-overhang-top', '3px', `${ res( 'themes-comments_res-theme-six' ) } .notificationx-image::after`, 'top', '-3px' ],
		[ res( 'themes-conversions_res-theme-nine' ), '--nx-overhang-top', '3px', `${ res( 'themes-conversions_res-theme-nine' ) } .notificationx-image:after`, 'top', '-3px' ],
		[ res( 'themes-conversions_res-theme-eleven' ), '--nx-overhang-top', '7px', `${ res( 'themes-conversions_res-theme-eleven' ) } .notificationx-image::after`, 'top', '-7px' ],
		[ res( 'themes-conversions_res-theme-six' ), '--nx-overhang-top', '4px', `${ res( 'themes-conversions_res-theme-six' ) } .notificationx-image`, 'transform', 'scale(1.2)' ],
		[ res( 'themes-conversions_res-theme-six' ), '--nx-overhang-bottom', '4px', `${ res( 'themes-conversions_res-theme-six' ) } .notificationx-image`, 'transform', 'scale(1.2)' ],
	];
	test.each( RES_PAIRS )( '%s declares %s: %s for its part', ( card, prop, val, part, partProp, partVal ) => {
		expect( find( rules, card, prop ) ).toContain( val );
		expect( find( rules, part, partProp ) ).toContain( partVal );
	} );
} );
