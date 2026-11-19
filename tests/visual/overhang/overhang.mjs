/**
 * Every corner theme: what sticks out of the card is covered by its declared overhang.
 *
 *   node overhang.mjs measure [--slice=i/n]
 *   node overhang.mjs check
 *
 * For each card (Button off and on, 1400px and 390px, every Pro mobile card) the top and
 * bottom overhang is measured twice, from the elements (getBoundingClientRect, clipped by
 * overflow) and from the pixels drawn (shadows off, so pseudo-elements count), and the larger
 * is compared with --nx-overhang-top / --nx-overhang-bottom:
 *   - an overhang of 3px or more must be declared: measured <= declared + 1;
 *   - nothing may be declared that isn't there: declared <= measured + 1.
 */
import fs from 'node:fs';
import path from 'node:path';
import { OUT, slice, results, themes, settingsFor, fetchPreview, harnessCss, withCss, launch, decode, write, read, exists } from './lib.mjs';
import { singleCases } from './cases.mjs';

const [ cmd ] = process.argv.slice( 2 ).filter( ( x ) => ! x.startsWith( '--' ) );
const MIN = 3;

const DOM = `(() => {
	const card = document.querySelector('#notificationx-frontend0 .nx-container > *');
	if (!card) return null;
	const R = card.getBoundingClientRect();
	const visible = el => { const c = getComputedStyle(el); return c.display !== 'none' && c.visibility !== 'hidden' && +c.opacity > 0; };
	let top = R.top, bottom = R.bottom, topEl = null, bottomEl = null;
	for (const el of card.querySelectorAll('*')) {
		if (!visible(el)) continue;
		const r = el.getBoundingClientRect();
		if (r.width < 1 || r.height < 1) continue;
		let t = r.top, b = r.bottom;
		for (let a = el.parentElement; a && a !== card.parentElement; a = a.parentElement) {
			const c = getComputedStyle(a);
			if (c.overflowY !== 'visible') { const ar = a.getBoundingClientRect(); t = Math.max(t, ar.top); b = Math.min(b, ar.bottom); }
		}
		if (b <= t) continue;
		const name = el.tagName.toLowerCase() + (typeof el.className === 'string' && el.className.trim() ? '.' + el.className.trim().split(/\\s+/).slice(0, 2).join('.') : '');
		if (t < top) { top = t; topEl = name; }
		if (b > bottom) { bottom = b; bottomEl = name; }
	}
	const cs = getComputedStyle(card);
	const px = v => parseFloat(v) || 0;
	return { rect: { top: R.top, bottom: R.bottom, left: R.left, right: R.right }, dom: { top: R.top - top, bottom: bottom - R.bottom }, topEl, bottomEl,
		declared: { top: px(cs.getPropertyValue('--nx-overhang-top')), bottom: px(cs.getPropertyValue('--nx-overhang-bottom')) },
		classes: card.className, vw: innerWidth };
})()`;

// Ink only in a band around the card, so nothing else the page draws can count.
function inkOverhang( png, rect ) {
	const { width, height, data } = png;
	const y0 = Math.max( 0, Math.floor( rect.top - 80 ) ), y1 = Math.min( height, Math.ceil( rect.bottom + 80 ) );
	let t = Infinity, b = -Infinity;
	for ( let y = y0; y < y1; y++ ) {
		for ( let x = 0; x < width; x++ ) {
			const k = ( y * width + x ) * 4;
			if ( Math.abs( data[ k ] - 255 ) + data[ k + 1 ] + Math.abs( data[ k + 2 ] - 255 ) > 90 ) {
				if ( y < t ) t = y;
				if ( y + 1 > b ) b = y + 1;
			}
		}
	}
	return t === Infinity ? null : { top: rect.top - t, bottom: b - rect.bottom };
}

if ( cmd === 'measure' ) {
	const list = themes();
	const work = slice.pick( singleCases( list ) );
	const h = await launch( `overhang-${ slice.i }` );
	const file = path.join( OUT, `overhang/${ slice.file }` );
	fs.mkdirSync( path.dirname( file ), { recursive: true } );
	fs.writeFileSync( file, '' );
	for ( const c of work ) {
		const rel = `html-measure/single/${ c.id }.html`;
		let html = exists( rel ) ? read( rel ).toString() : null;
		if ( ! html ) {
			html = await fetchPreview( settingsFor( c, list ) );
			write( rel, html );
		}
		// The card well inside the screen, so nothing it draws is cut off.
		await h.open( withCss( html, harnessCss( { motion: false, shadows: false, containerTop: c.width < 575 ? 330 : 420 } ) ), c );
		const count = await h.cards( 1 );
		const m = count ? await h.evaluate( DOM ) : null;
		const ink = m ? inkOverhang( decode( await h.screenshot() ), m.rect ) : null;
		fs.appendFileSync( file, JSON.stringify( { id: c.id, view: c.view, button: !! c.button, count, ...m, ink, errors: [ ...h.errors ] } ) + '\n' );
	}
	await h.close();
	console.log( `measured ${ work.length } (slice ${ slice.i }/${ slice.n })` );
}

if ( cmd === 'check' ) {
	const rows = results( 'overhang' );
	const failures = [];
	const table = [];
	for ( const r of rows ) {
		if ( ! r.count || ! r.rect ) { failures.push( `${ r.id }: no card rendered` ); continue; }
		if ( r.errors?.length ) failures.push( `${ r.id }: page errors: ${ r.errors.join( ' | ' ).slice( 0, 200 ) }` );
		for ( const side of [ 'top', 'bottom' ] ) {
			const measured = Math.round( Math.max( r.dom[ side ], r.ink ? r.ink[ side ] : 0, 0 ) * 10 ) / 10;
			const declared = r.declared[ side ];
			if ( measured >= MIN || declared ) table.push( { id: r.id, side, measured, declared } );
			if ( measured >= MIN && measured > declared + 1 ) failures.push( `${ r.id }: ${ side } sticks out ${ measured }px, declared ${ declared }px (${ r[ side + 'El' ] || 'pseudo-element or border' })` );
			if ( declared > measured + 1 ) failures.push( `${ r.id }: ${ side } declared ${ declared }px, only ${ measured }px sticks out` );
		}
	}
	write( 'overhang/report.json', JSON.stringify( { cases: rows.length, failures, table }, null, 1 ) );
	console.log( `${ rows.length } cards, ${ table.length } sides with an overhang or a declared value, ${ failures.length } failures` );
	failures.forEach( ( f ) => console.log( '  FAIL ' + f ) );
	process.exitCode = failures.length ? 1 : 0;
}
