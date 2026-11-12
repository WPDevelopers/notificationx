/**
 * Headless Chrome harness for the overhang visual tests (opt-in, see README.md).
 *
 * Every card is a real builder preview: the page is the site's own response to the
 * builder's `nx-preview` POST, rendered by the built Free + Pro JS and CSS. The harness
 * only serves that HTML back to Chrome (optionally with several notifications merged
 * into one preview) and blocks writes: other POSTs get `{}`, other hosts are blocked.
 * Nothing is saved on the site.
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
export const { decode, encode } = require( './png.cjs' );

export const SITE = process.env.NX_VISUAL_SITE || 'http://storefy.local/';
export const OUT = process.env.NX_VISUAL_OUT || path.join( os.tmpdir(), 'nx-visual-overhang' );
const CHROME = process.env.NX_VISUAL_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

export const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

// `--slice=i/n` runs every n-th case starting at i, so several processes can share a run.
// `--only=text` runs only the cases whose id contains it; their results go to separate
// files that replace the earlier rows of those cases when checked (`results()`).
export const slice = ( () => {
	const arg = process.argv.find( ( a ) => a.startsWith( '--slice=' ) );
	const only = ( process.argv.find( ( a ) => a.startsWith( '--only=' ) ) || '' ).slice( 7 );
	const [ i, n ] = arg ? arg.slice( 8 ).split( '/' ).map( Number ) : [ 0, 1 ];
	return { i, n, only, file: `measure-${ only ? 'only-' : '' }${ i }.jsonl`,
		pick: ( list ) => list.filter( ( c ) => ! only || c.id.includes( only ) ).filter( ( _, k ) => k % n === i ) };
} )();

// Every measured row in OUT/dir, the latest per case id (`--only` reruns win).
export function results( dir ) {
	const files = fs.readdirSync( path.join( OUT, dir ) ).filter( ( f ) => /^measure-.*\.jsonl$/.test( f ) )
		.sort( ( a, b ) => Number( a.includes( 'only-' ) ) - Number( b.includes( 'only-' ) ) );
	const rows = new Map();
	for ( const f of files ) {
		fs.readFileSync( path.join( OUT, dir, f ), 'utf8' ).trim().split( '\n' ).filter( Boolean ).map( JSON.parse ).forEach( ( r ) => rows.set( r.id, r ) );
	}
	return [ ...rows.values() ];
}

// Corner themes per type (from themes.php, see README.md).
export const themes = () => JSON.parse( fs.readFileSync( process.env.NX_VISUAL_THEMES || path.join( OUT, 'themes.json' ), 'utf8' ) );

// The builder's values for a notification of this type/source/theme. Only the fields a
// card's look depends on; everything else falls back to the frontend defaults.
export const settingsFor = ( c, themeList ) => {
	const th = themeList[ c.source ]?.themes?.[ c.themes ] || {};
	const s = {
		...( th.defaults || {} ),
		type: c.type, source: c.source, themes: c.themes, theme: c.themes,
		position: c.position || 'bottom_left',
		show_notification_image: th.show_notification_image || 'featured_image', show_default_image: '1', image_shape: th.image_shape || 'circle',
		// Builder fields the preview reads without a check (PHP notices otherwise).
		default_avatar: 'blue-face-non-looped.gif', image_url: '', wp_reviews_product_type: 'plugin',
		is_mobile_responsive: !! c.res, responsive_themes: c.res || '',
		delay_before: 0, display_for: 120, delay_between: 5, loop: true, global_queue: false, random_order: false,
		template_adv: false, advance_edit: false, close_button: true, close_button_tab: true, close_button_mobile: true,
		previewType: c.width && c.width < 575 ? 'phone' : 'desktop', sound: 'none',
		notification_reappearance: 'show_notification_next_visit',
		animation_notification_show: c.show || 'default', animation_notification_hide: c.hide || 'default',
		combine_multiorder: true, combine_multiorder_text: 'more product', combine_multiorder_text_plural: 'more products',
		link_button: !! c.button, link_open: false,
		'notification-template': { ...( th.template || {} ) },
	};
	if ( c.button ) {
		Object.assign( s, {
			link_type: s.link_type && s.link_type !== 'none' ? s.link_type : 'custom', custom_url: '#',
			link_button_text: 'Buy Now', link_button_text_video: 'Watch Now', link_button_text_channel: 'Subscribe Now',
		} );
	}
	return s;
};

// PHP notices printed into the page (a site with display_errors on) are removed: Chrome
// shows them as text, which would count as something the card draws. Each is reported.
const NOTICE = /<br \/>\s*<b>(Warning|Notice|Deprecated)<\/b>:[\s\S]*?<br \/>/g;
export async function fetchPreview( settings ) {
	const body = 'nx-preview=' + encodeURIComponent( Buffer.from( JSON.stringify( settings ), 'utf8' ).toString( 'base64' ) );
	const res = await fetch( SITE, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body } );
	const html = await res.text();
	const notices = html.match( NOTICE ) || [];
	notices.forEach( ( n ) => console.warn( `PHP notice in the ${ settings.themes } preview: ${ n.replace( /<[^>]+>/g, '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 160 ) }` ) );
	return html.replace( NOTICE, '' );
}

// The `notificationXArr.push({...})` config in a preview page.
export function extractConfig( html ) {
	const marker = 'notificationXArr.push(';
	const start = html.indexOf( marker ) + marker.length;
	if ( start < marker.length ) {
		throw new Error( 'no notificationXArr in the page' );
	}
	let depth = 0, inStr = false, esc = false, end = -1;
	for ( let k = start; k < html.length; k++ ) {
		const ch = html[ k ];
		if ( inStr ) {
			if ( esc ) esc = false; else if ( ch === '\\' ) esc = true; else if ( ch === '"' ) inStr = false;
			continue;
		}
		if ( ch === '"' ) inStr = true;
		else if ( ch === '{' ) depth++;
		else if ( ch === '}' && --depth === 0 ) { end = k + 1; break; }
	}
	return { start, end, config: JSON.parse( html.slice( start, end ) ) };
}

export function replaceConfig( html, config ) {
	const { start, end } = extractConfig( html );
	// `</` would end the inline script early.
	return html.slice( 0, start ) + JSON.stringify( config ).replace( /<\//g, '<\\/' ) + html.slice( end );
}

/**
 * One preview page holding several notifications (a stack in one corner): the first
 * page's config gets the `active` entries of the others. `spacingV2` and `adminBar`
 * switch the corner spacing mode and the WordPress admin bar offset.
 */
export function mergePreviews( pages, { spacingV2, adminBar } = {} ) {
	const base = extractConfig( pages[ 0 ] ).config;
	base.active = {};
	pages.forEach( ( html, k ) => {
		const active = extractConfig( html ).config.active;
		base.active[ `nx-stack-${ k }` ] = Object.values( active )[ 0 ];
	} );
	base.total = pages.length;
	if ( spacingV2 !== undefined ) base.spacing_v2 = spacingV2;
	let out = replaceConfig( pages[ 0 ], base );
	if ( adminBar ) out = out.replace( /<body([^>]*)class="/, '<body$1class="admin-bar ' );
	return out;
}

// Page styles: hide the site's own content on a flat colour; optionally switch off motion
// and shadows (measuring), or keep shadows (pixel comparison).
export const harnessCss = ( { motion = false, shadows = true, containerTop = null } = {} ) => `<style id="nx-visual">
html, body { background: #ff00ff !important; }
body > *:not(.notificationx-frontend) { display: none !important; }
${ motion ? '' : '*, *::before, *::after { animation: none !important; transition: none !important; }' }
${ shadows ? '' : '*, *::before, *::after { box-shadow: none !important; text-shadow: none !important; filter: none !important; backdrop-filter: none !important; }' }
${ containerTop === null ? '' : `#notificationx-frontend0 .nx-container.nx-container { top: ${ containerTop }px !important; bottom: auto !important; }` }
</style>`;
export const withCss = ( html, css ) => html.replace( '</head>', css + '</head>' );

export async function launch( name ) {
	const userDir = path.join( OUT, `chrome-${ name }-${ process.pid }` );
	fs.rmSync( userDir, { recursive: true, force: true } );
	const chrome = spawn( CHROME, [ '--headless=new', '--remote-debugging-port=0', `--user-data-dir=${ userDir }`,
		'--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--disable-sync',
		'--disable-component-update', '--hide-scrollbars', '--mute-audio', '--font-render-hinting=none',
		// The served page has no address, so Chrome treats it as public and would block
		// its requests to a local site (Local Network Access).
		'--disable-features=LocalNetworkAccessChecks,LocalNetworkAccessChecksWarn,PrivateNetworkAccessSendPreflights,PrivateNetworkAccessRespectPreflightResults,BlockInsecurePrivateNetworkRequests',
		'about:blank' ], { stdio: 'ignore' } );
	const portFile = path.join( userDir, 'DevToolsActivePort' );
	for ( let k = 0; k < 200 && ! fs.existsSync( portFile ); k++ ) await sleep( 50 );
	const port = fs.readFileSync( portFile, 'utf8' ).split( '\n' )[ 0 ];
	const list = await ( await fetch( `http://127.0.0.1:${ port }/json/list` ) ).json();
	const ws = new WebSocket( list.find( ( t ) => t.type === 'page' ).webSocketDebuggerUrl );
	await new Promise( ( r ) => ws.addEventListener( 'open', r, { once: true } ) );

	let seq = 0;
	const pending = new Map();
	const listeners = [];
	ws.addEventListener( 'message', ( ev ) => {
		const m = JSON.parse( ev.data );
		if ( m.id && pending.has( m.id ) ) {
			const { res, rej } = pending.get( m.id );
			pending.delete( m.id );
			m.error ? rej( new Error( JSON.stringify( m.error ) ) ) : res( m.result );
		} else if ( m.method ) {
			listeners.forEach( ( l ) => l( m ) );
		}
	} );
	const send = ( method, params = {} ) => new Promise( ( res, rej ) => {
		const id = ++seq;
		pending.set( id, { res, rej } );
		ws.send( JSON.stringify( { id, method, params } ) );
		setTimeout( () => { if ( pending.has( id ) ) { pending.delete( id ); rej( new Error( 'CDP timeout: ' + method ) ); } }, 30000 );
	} );

	let pageHtml = '';
	const errors = [];
	const site = new URL( SITE );
	listeners.push( async ( m ) => {
		if ( m.method === 'Fetch.requestPaused' ) {
			const { requestId, request } = m.params;
			const u = new URL( request.url );
			try {
				if ( u.protocol === 'data:' || u.protocol === 'blob:' ) return await send( 'Fetch.continueRequest', { requestId } );
				if ( u.host !== site.host ) return await send( 'Fetch.failRequest', { requestId, errorReason: 'BlockedByClient' } );
				if ( u.pathname.startsWith( '/__nx_visual__/' ) ) {
					return await send( 'Fetch.fulfillRequest', { requestId, responseCode: 200,
						responseHeaders: [ { name: 'Content-Type', value: 'text/html; charset=UTF-8' } ], body: Buffer.from( pageHtml ).toString( 'base64' ) } );
				}
				if ( request.method === 'GET' || request.method === 'HEAD' ) return await send( 'Fetch.continueRequest', { requestId } );
				return await send( 'Fetch.fulfillRequest', { requestId, responseCode: 200,
					responseHeaders: [ { name: 'Content-Type', value: 'application/json' } ], body: Buffer.from( '{}' ).toString( 'base64' ) } );
			} catch ( e ) {
				try { await send( 'Fetch.continueRequest', { requestId } ); } catch ( _ ) {}
			}
		}
		if ( m.method === 'Runtime.exceptionThrown' ) {
			errors.push( ( m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text || '' ).split( '\n' )[ 0 ] );
		}
	} );
	await send( 'Fetch.enable', { patterns: [ { urlPattern: '*', requestStage: 'Request' } ] } );
	await send( 'Page.enable' );
	await send( 'Runtime.enable' );

	// DOMContentLoaded, not load: the site's pages keep loading media (and blocked fonts)
	// long after the cards are up. cards() then waits for the cards themselves.
	const loaded = () => new Promise( ( r ) => {
		const f = ( m ) => { if ( m.method === 'Page.domContentEventFired' ) done(); };
		const done = () => { const k = listeners.indexOf( f ); if ( k >= 0 ) listeners.splice( k, 1 ); clearTimeout( to ); r(); };
		const to = setTimeout( done, 15000 );
		listeners.push( f );
	} );
	const evaluate = async ( expression ) => {
		const r = await send( 'Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true } );
		if ( r.exceptionDetails ) throw new Error( r.exceptionDetails.exception?.description || r.exceptionDetails.text );
		return r.result.value;
	};

	return {
		send, evaluate, errors,
		async open( html, { width, height, mobile = width < 575, reducedMotion = false } ) {
			errors.length = 0;
			await send( 'Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile } );
			await send( 'Emulation.setEmulatedMedia', { features: [ { name: 'prefers-reduced-motion', value: reducedMotion ? 'reduce' : 'no-preference' } ] } );
			pageHtml = html;
			const p = loaded();
			await send( 'Page.navigate', { url: SITE.replace( /\/$/, '' ) + '/__nx_visual__/' + Date.now() } );
			await p;
		},
		// Resolves when `count` cards are on screen with fonts and images loaded.
		async cards( count = 1, settle = 400 ) {
			return evaluate( `(async () => {
				const sel = '#notificationx-frontend0 .nx-container > *';
				for (let k = 0; k < 75 && document.querySelectorAll(sel).length < ${ count }; k++) await new Promise(r => setTimeout(r, 200));
				const cards = [...document.querySelectorAll(sel)];
				await document.fonts.ready;
				await Promise.all(cards.flatMap(c => [...c.querySelectorAll('img')]).map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; setTimeout(r, 3000); })));
				await new Promise(r => setTimeout(r, ${ settle }));
				return cards.length;
			})()` );
		},
		async screenshot( clip ) {
			const { data } = await send( 'Page.captureScreenshot', clip ? { format: 'png', clip: { ...clip, scale: 1 } } : { format: 'png' } );
			return Buffer.from( data, 'base64' );
		},
		async close() {
			try { ws.close(); } catch ( _ ) {}
			chrome.kill( 'SIGTERM' );
			await sleep( 300 );
			fs.rmSync( userDir, { recursive: true, force: true } );
		},
	};
}

// Box of everything drawn over the flat page colour, in CSS px (deviceScaleFactor 1),
// optionally only between band.top and band.bottom.
export function inkBox( png, band = null ) {
	const { width, height, data } = png;
	const y0 = band ? Math.floor( band.top ) : 0, y1 = band ? Math.ceil( band.bottom ) : height;
	let t = height, b = -1, l = width, r = -1;
	for ( let y = y0; y < y1; y++ ) {
		for ( let x = 0; x < width; x++ ) {
			const k = ( y * width + x ) * 4;
			if ( Math.abs( data[ k ] - 255 ) + data[ k + 1 ] + Math.abs( data[ k + 2 ] - 255 ) > 90 ) {
				if ( y < t ) t = y;
				if ( y > b ) b = y;
				if ( x < l ) l = x;
				if ( x > r ) r = x;
			}
		}
	}
	return b < 0 ? null : { top: t, bottom: b + 1, left: l, right: r + 1 };
}

export function diffPixels( a, b ) {
	if ( a.width !== b.width || a.height !== b.height ) return Infinity;
	let n = 0;
	for ( let k = 0; k < a.data.length; k += 4 ) {
		if ( a.data[ k ] !== b.data[ k ] || a.data[ k + 1 ] !== b.data[ k + 1 ] || a.data[ k + 2 ] !== b.data[ k + 2 ] || a.data[ k + 3 ] !== b.data[ k + 3 ] ) n++;
	}
	return n;
}

export const write = ( rel, data ) => {
	const file = path.join( OUT, rel );
	fs.mkdirSync( path.dirname( file ), { recursive: true } );
	fs.writeFileSync( file, data );
	return file;
};
export const read = ( rel ) => fs.readFileSync( path.join( OUT, rel ) );
export const exists = ( rel ) => fs.existsSync( path.join( OUT, rel ) );
