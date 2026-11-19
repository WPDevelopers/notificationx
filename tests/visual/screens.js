#!/usr/bin/env node
/**
 * Card size presets (84708), screenshot tool, step 2. See README.md.
 *
 *   node tests/visual/screens.js compare --cards cards.json --a old.css --b new.css --out dir
 *       [--size default] [--viewports 1440,390] [--dirs ltr,rtl] [--frame-height 300] [--base dir]
 *     Screenshots every card with stylesheet A and with stylesheet B, in local
 *     headless Chrome, and compares them pixel by pixel. Any different pixel
 *     fails (exit 1), naming the cards. Also fails a card that shows nothing.
 *
 *   node tests/visual/screens.js measure --cards cards.json --css frontend.css --out dir
 *       [--size default] [--viewports 1440,390]
 *     Writes measure.json and phase0.md: each card's root box and styles (the
 *     parts card size cannot scale) and its text sizes (what Compact would
 *     render under 10px).
 *
 * Chrome: CHROME_BIN, or Google Chrome / Chromium in the usual places.
 * No dependencies beyond Node.
 */
'use strict';

const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );
const { pathToFileURL } = require( 'url' );
const png = require( './png' );

const PLUGIN = path.resolve( __dirname, '../..' );
const FONT_CSS = path.join( PLUGIN, 'assets/public/vendor/open-sans/css/open-sans.css' );
// Chrome's headless window is at least 500px wide: narrower viewports are iframes.
const MIN_WINDOW = 500;
// Compact is 0.8: text under 12.5px renders under 10px.
const COMPACT_FLOOR = 12.5;

function parseArgs( argv ) {
	const args = { _: [] };
	for ( let i = 0; i < argv.length; i++ ) {
		if ( argv[ i ].startsWith( '--' ) ) {
			args[ argv[ i ].slice( 2 ) ] = argv[ i + 1 ];
			i++;
		} else {
			args._.push( argv[ i ] );
		}
	}
	return args;
}

function fail( message ) {
	console.error( `screens: ${ message }` );
	process.exit( 2 );
}

function chromeBin() {
	if ( process.env.CHROME_BIN ) {
		return process.env.CHROME_BIN;
	}
	const candidates = [
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/Applications/Chromium.app/Contents/MacOS/Chromium',
		'/usr/bin/google-chrome',
		'/usr/bin/google-chrome-stable',
		'/usr/bin/chromium',
		'/usr/bin/chromium-browser',
	];
	const found = candidates.find( ( c ) => fs.existsSync( c ) );
	if ( ! found ) {
		fail( 'Chrome not found; set CHROME_BIN' );
	}
	return found;
}

function chrome( out, extraArgs ) {
	const profile = fs.mkdtempSync( path.join( out, '.chrome-' ) );
	try {
		return execFileSync(
			chromeBin(),
			[
				'--headless=new',
				'--disable-gpu',
				'--hide-scrollbars',
				'--force-device-scale-factor=1',
				'--no-first-run',
				'--no-default-browser-check',
				'--disable-extensions',
				'--mute-audio',
				'--virtual-time-budget=5000',
				`--user-data-dir=${ profile }`,
				...extraArgs,
			],
			{ encoding: 'utf8', maxBuffer: 256 * 1024 * 1024, timeout: 180000, stdio: [ 'ignore', 'pipe', 'pipe' ] }
		);
	} finally {
		fs.rmSync( profile, { recursive: true, force: true } );
	}
}

const escapeAttr = ( s ) => s.replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' );

// One card as a page, the way the frontend mounts it (index.tsx, NotificationContainer.tsx).
function cardDocument( card, css, dir ) {
	return (
		`<!doctype html><html dir="${ dir }" lang="en"><head><meta charset="utf-8">` +
		`<link rel="stylesheet" href="${ pathToFileURL( FONT_CSS ) }">` +
		`<link rel="stylesheet" href="${ pathToFileURL( path.resolve( css ) ) }">` +
		// The entrance animation is switched off on both sides, so the screenshot is the card at rest.
		'<style>html,body{margin:0;padding:0;background:#fff}.notification-item{animation:none!important}</style>' +
		'</head><body><div id="notificationx-frontend" class="notificationx-frontend">' +
		`<div class="nx-container nxc-bottom_left">${ card.html }</div>` +
		'</div></body></html>'
	);
}

function framesPage( cards, css, dir, viewport, frameHeight, script = '' ) {
	const frames = cards
		.map( ( card ) => `<iframe scrolling="no" srcdoc="${ escapeAttr( cardDocument( card, css, dir ) ) }"></iframe>` )
		.join( '' );
	return (
		'<!doctype html><html><head><meta charset="utf-8"><style>' +
		`html,body{margin:0;padding:0;background:#fff}iframe{display:block;border:0;width:${ viewport }px;height:${ frameHeight }px}` +
		`</style></head><body>${ frames }${ script }</body></html>`
	);
}

// A fixed test image (the fixtures use img.png): a 120×120 gradient.
function writeImage( out ) {
	const size = 120;
	const data = Buffer.alloc( size * size * 4 );
	for ( let y = 0; y < size; y++ ) {
		for ( let x = 0; x < size; x++ ) {
			const i = ( y * size + x ) * 4;
			data[ i ] = 40 + x;
			data[ i + 1 ] = 90 + y;
			data[ i + 2 ] = 200;
			data[ i + 3 ] = 255;
		}
	}
	fs.writeFileSync( path.join( out, 'img.png' ), png.encode( { width: size, height: size, data } ) );
}

function loadCards( args ) {
	if ( ! args.cards || ! fs.existsSync( args.cards ) ) {
		fail( '--cards <cards.json> is required (see README.md)' );
	}
	const size = args.size || 'default';
	const cards = JSON.parse( fs.readFileSync( args.cards, 'utf8' ) ).filter( ( c ) => c.size === size );
	if ( ! cards.length ) {
		fail( `no cards with size "${ size }" in ${ args.cards }` );
	}
	return cards;
}

// Copies a stylesheet into the output folder, with its relative url()s pointed at
// --base (default assets/public/css/, where frontend.css lives), so a copy of an
// old build kept anywhere loads the same images.
function stageCss( src, base, dest ) {
	const css = fs
		.readFileSync( src, 'utf8' )
		.replace( /url\((['"]?)(?!data:|https?:|file:|\/\/|#)([^'")]+)\1\)/g, ( m, quote, url ) => `url(${ quote }${ pathToFileURL( path.resolve( base, url ) ).href }${ quote })` );
	fs.writeFileSync( dest, css );
	return dest;
}

const cssBase = ( args ) => path.resolve( args.base || path.join( PLUGIN, 'assets/public/css' ) );

const list = ( value, fallback ) => ( value || fallback ).split( ',' ).map( ( s ) => s.trim() ).filter( Boolean );

function screenshot( out, name, html, width, height ) {
	const page = path.join( out, `${ name }.html` );
	const shot = path.join( out, `${ name }.png` );
	fs.writeFileSync( page, html );
	fs.rmSync( shot, { force: true } );
	chrome( out, [ `--window-size=${ width },${ height }`, `--screenshot=${ shot }`, pathToFileURL( page ).href ] );
	if ( ! fs.existsSync( shot ) ) {
		fail( `Chrome wrote no screenshot for ${ name }` );
	}
	const image = png.decode( fs.readFileSync( shot ) );
	if ( image.width !== width || image.height !== height ) {
		fail( `${ name }: screenshot is ${ image.width }×${ image.height }, expected ${ width }×${ height }` );
	}
	return image;
}

function compare( args ) {
	const out = path.resolve( args.out || fail( '--out <dir> is required' ) );
	for ( const side of [ 'a', 'b' ] ) {
		if ( ! args[ side ] || ! fs.existsSync( args[ side ] ) ) {
			fail( `--${ side } <stylesheet> is required` );
		}
	}
	const cards = loadCards( args );
	const frameHeight = parseInt( args[ 'frame-height' ] || '300', 10 );
	fs.mkdirSync( out, { recursive: true } );
	writeImage( out );
	const css = {
		a: stageCss( args.a, cssBase( args ), path.join( out, 'a.css' ) ),
		b: stageCss( args.b, cssBase( args ), path.join( out, 'b.css' ) ),
	};

	const report = [];
	let failed = false;
	for ( const viewport of list( args.viewports, '1440,390' ).map( Number ) ) {
		for ( const dir of list( args.dirs, 'ltr,rtl' ) ) {
			for ( const credit of [ 'on', 'off' ] ) {
				const group = cards.filter( ( c ) => c.credit === credit );
				const name = `${ viewport }-${ dir }-credit-${ credit }`;
				const width = Math.max( viewport, MIN_WINDOW );
				const height = group.length * frameHeight;
				const a = screenshot( out, `${ name }-a`, framesPage( group, css.a, dir, viewport, frameHeight ), width, height );
				const b = screenshot( out, `${ name }-b`, framesPage( group, css.b, dir, viewport, frameHeight ), width, height );

				const diff = Buffer.from( a.data );
				let total = 0;
				const perCard = group.map( ( card, index ) => {
					let different = 0;
					let inked = 0;
					for ( let y = index * frameHeight; y < ( index + 1 ) * frameHeight; y++ ) {
						for ( let x = 0; x < width; x++ ) {
							const i = ( y * width + x ) * 4;
							const same =
								a.data[ i ] === b.data[ i ] &&
								a.data[ i + 1 ] === b.data[ i + 1 ] &&
								a.data[ i + 2 ] === b.data[ i + 2 ] &&
								a.data[ i + 3 ] === b.data[ i + 3 ];
							if ( a.data[ i ] < 250 || a.data[ i + 1 ] < 250 || a.data[ i + 2 ] < 250 ) {
								inked++;
							}
							if ( same ) {
								// Faded copy of A, so the differences stand out.
								diff[ i ] = 255 - ( ( 255 - a.data[ i ] ) >> 2 );
								diff[ i + 1 ] = 255 - ( ( 255 - a.data[ i + 1 ] ) >> 2 );
								diff[ i + 2 ] = 255 - ( ( 255 - a.data[ i + 2 ] ) >> 2 );
							} else {
								different++;
								diff[ i ] = 255;
								diff[ i + 1 ] = 0;
								diff[ i + 2 ] = 0;
							}
							diff[ i + 3 ] = 255;
						}
					}
					total += different;
					return { themes: card.themes, different, inked };
				} );
				const blank = perCard.filter( ( c ) => c.inked < 500 ).map( ( c ) => c.themes );
				const changed = perCard.filter( ( c ) => c.different ).map( ( c ) => `${ c.themes } (${ c.different }px)` );
				if ( total ) {
					fs.writeFileSync( path.join( out, `${ name }-diff.png` ), png.encode( { width, height, data: diff } ) );
				}
				if ( total || blank.length ) {
					failed = true;
				}
				report.push( { name, cards: group.length, differentPixels: total, changed, blank } );
				console.log(
					`${ name.padEnd( 22 ) } ${ String( group.length ).padStart( 2 ) } cards  ${ String( total ).padStart( 7 ) } different px` +
						( changed.length ? `  CHANGED: ${ changed.join( ', ' ) }` : '' ) +
						( blank.length ? `  BLANK: ${ blank.join( ', ' ) }` : '' )
				);
			}
		}
	}
	fs.writeFileSync( path.join( out, 'report.json' ), JSON.stringify( report, null, '\t' ) );
	const totalPixels = report.reduce( ( sum, r ) => sum + r.differentPixels, 0 );
	console.log( `${ failed ? 'FAIL' : 'PASS' }: ${ report.length } screenshots per side, ${ totalPixels } different pixels in total` );
	process.exit( failed ? 1 : 0 );
}

// Runs inside the measuring page: every frame's root, inner part and text.
function measureInPage() {
	const ROOT_PROPS = [
		'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
		'border-top-width', 'border-top-left-radius', 'border-top-right-radius',
		'border-bottom-right-radius', 'border-bottom-left-radius',
		'min-width', 'max-width', 'width', 'height', 'min-height', 'box-shadow', 'background-color', 'overflow',
	];
	const INNER_PROPS = [ 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'border-top-left-radius', 'background-color', 'min-width', 'width' ];
	const label = ( el ) => {
		const cls = [ ...el.classList ];
		return el.tagName.toLowerCase() + ( cls.length ? '.' + cls.join( '.' ) : '' );
	};
	const pathTo = ( el, root ) => {
		const parts = [];
		for ( let node = el; node && node !== root; node = node.parentElement ) {
			parts.unshift( label( node ) );
		}
		return parts.join( ' > ' );
	};
	const pick = ( win, el, props ) => {
		const style = win.getComputedStyle( el );
		const out = {};
		props.forEach( ( p ) => ( out[ p ] = style.getPropertyValue( p ) ) );
		return out;
	};
	const box = ( el ) => {
		const r = el.getBoundingClientRect();
		return { x: Math.round( r.left ), y: Math.round( r.top ), w: Math.round( r.width * 10 ) / 10, h: Math.round( r.height * 10 ) / 10 };
	};
	return Promise.all(
		[ ...document.querySelectorAll( 'iframe' ) ].map( async ( frame ) => {
			const doc = frame.contentDocument;
			const win = frame.contentWindow;
			await doc.fonts.ready;
			const root = doc.querySelector( '.notification-item' );
			if ( ! root ) {
				return { error: 'no .notification-item' };
			}
			const inner = root.querySelector( '.notificationx-inner' );
			const texts = [];
			root.querySelectorAll( '*' ).forEach( ( el ) => {
				const own = [ ...el.childNodes ].filter( ( n ) => n.nodeType === 3 ).map( ( n ) => n.textContent.trim() ).join( ' ' ).trim();
				const r = el.getBoundingClientRect();
				if ( ! own || ! r.width || ! r.height || win.getComputedStyle( el ).visibility === 'hidden' ) {
					return;
				}
				texts.push( { path: pathTo( el, root ), size: parseFloat( win.getComputedStyle( el ).fontSize ), text: own.slice( 0, 40 ) } );
			} );
			const close = root.querySelector( '.notificationx-close' );
			return {
				root: { box: box( root ), style: pick( win, root, ROOT_PROPS ) },
				inner: inner ? { box: box( inner ), style: pick( win, inner, INNER_PROPS ) } : null,
				close: close ? box( close ) : null,
				texts,
			};
		} )
	);
}

const MEASURE_SCRIPT =
	'<script>window.addEventListener("load",function(){(' +
	measureInPage.toString() +
	')().then(function(r){var p=document.createElement("pre");p.id="nx-measure";' +
	'p.textContent=btoa(unescape(encodeURIComponent(JSON.stringify(r))));document.body.appendChild(p);});});</script>';

const px = ( v ) => parseFloat( v ) || 0;

function measure( args ) {
	const out = path.resolve( args.out || fail( '--out <dir> is required' ) );
	if ( ! args.css || ! fs.existsSync( args.css ) ) {
		fail( '--css <stylesheet> is required' );
	}
	const cards = loadCards( args );
	const frameHeight = parseInt( args[ 'frame-height' ] || '300', 10 );
	fs.mkdirSync( out, { recursive: true } );
	writeImage( out );
	const css = stageCss( args.css, cssBase( args ), path.join( out, 'measure.css' ) );

	const results = [];
	for ( const viewport of list( args.viewports, '1440,390' ).map( Number ) ) {
		const name = `measure-${ viewport }`;
		const page = path.join( out, `${ name }.html` );
		fs.writeFileSync( page, framesPage( cards, css, 'ltr', viewport, frameHeight, MEASURE_SCRIPT ) );
		const dom = chrome( out, [
			`--window-size=${ Math.max( viewport, MIN_WINDOW ) },${ cards.length * frameHeight }`,
			'--dump-dom',
			pathToFileURL( page ).href,
		] );
		const match = dom.match( /<pre id="nx-measure">([^<]*)<\/pre>/ );
		if ( ! match ) {
			fail( `${ name }: the page did not report its measurements` );
		}
		const measured = JSON.parse( Buffer.from( match[ 1 ], 'base64' ).toString( 'utf8' ) );
		measured.forEach( ( m, i ) => results.push( { viewport, themes: cards[ i ].themes, family: cards[ i ].family, credit: cards[ i ].credit, ...m } ) );
	}
	fs.writeFileSync( path.join( out, 'measure.json' ), JSON.stringify( results, null, '\t' ) );
	fs.writeFileSync( path.join( out, 'phase0.md' ), phase0( results ) );
	console.log( `measured ${ results.length } cards: ${ path.join( out, 'measure.json' ) }, ${ path.join( out, 'phase0.md' ) }` );
}

// The Phase 0 list: what zoom on the inner part won't scale, and Compact's small text.
function phase0( results ) {
	const lines = [ '# Card size, Phase 0 list (generated by `screens.js measure`)', '' ];
	const errors = results.filter( ( r ) => r.error );
	if ( errors.length ) {
		lines.push( `**Errors:** ${ errors.map( ( e ) => `${ e.themes } (${ e.viewport }px): ${ e.error }` ).join( '; ' ) }`, '' );
	}
	const ok = results.filter( ( r ) => ! r.error );

	lines.push( '## Root-level styles (on `.notification-item`, outside the zoomed part)', '' );
	lines.push( '| Theme | Viewport | Root box | Padding (t r b l) | Border | Radius | Min-width | Inner box |', '|---|---|---|---|---|---|---|---|' );
	const seen = new Set();
	ok.filter( ( r ) => r.credit === 'on' ).forEach( ( r ) => {
		const s = r.root.style;
		const padding = [ s[ 'padding-top' ], s[ 'padding-right' ], s[ 'padding-bottom' ], s[ 'padding-left' ] ];
		const radius = [ s[ 'border-top-left-radius' ], s[ 'border-top-right-radius' ], s[ 'border-bottom-right-radius' ], s[ 'border-bottom-left-radius' ] ];
		const flagged = padding.some( px ) || px( s[ 'border-top-width' ] ) || radius.some( px );
		const key = `${ r.themes }|${ r.viewport }`;
		if ( seen.has( key ) ) {
			return;
		}
		seen.add( key );
		lines.push(
			`| ${ r.themes }${ flagged ? ' **⚑**' : '' } | ${ r.viewport } | ${ r.root.box.w }×${ r.root.box.h } | ${ padding.join( ' ' ) } | ${ s[ 'border-top-width' ] } | ${ [ ...new Set( radius ) ].join( ' / ' ) } | ${ s[ 'min-width' ] } | ${
				r.inner ? `${ r.inner.box.w }×${ r.inner.box.h }` : '—'
			} |`
		);
	} );
	lines.push( '', '⚑ = root padding, border or radius: these stay unscaled under zoom and need a scoped fix in P4–P6.', '' );

	lines.push( `## Text under ${ COMPACT_FLOOR }px (renders under 10px in Compact)`, '' );
	lines.push( '| Selector path (from the root) | Size | Themes | Example |', '|---|---|---|---|' );
	const small = new Map();
	ok.forEach( ( r ) =>
		r.texts
			.filter( ( t ) => t.size < COMPACT_FLOOR )
			.forEach( ( t ) => {
				const key = `${ t.path }|${ t.size }`;
				const entry = small.get( key ) || { path: t.path, size: t.size, themes: new Set(), text: t.text };
				entry.themes.add( r.themes );
				small.set( key, entry );
			} )
	);
	[ ...small.values() ]
		.sort( ( x, y ) => x.size - y.size || x.path.localeCompare( y.path ) )
		.forEach( ( e ) => lines.push( `| \`${ e.path }\` | ${ e.size }px | ${ [ ...e.themes ].join( ', ' ) } | ${ e.text.replace( /\|/g, '\\|' ) } |` ) );
	lines.push( '' );
	return lines.join( '\n' );
}

const args = parseArgs( process.argv.slice( 2 ) );
if ( args._[ 0 ] === 'compare' ) {
	compare( args );
} else if ( args._[ 0 ] === 'measure' ) {
	measure( args );
} else {
	fail( 'usage: screens.js compare|measure … (see README.md)' );
}
