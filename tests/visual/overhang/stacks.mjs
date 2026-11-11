/**
 * Three stacked cards in one corner: nothing overlaps, and the gaps and the screen-edge
 * spacing start at whatever sticks out of a card.
 *
 *   node stacks.mjs measure [--slice=i/n]   every stack (cases.mjs) plus the glide runs
 *   node stacks.mjs check                   evaluates all slices
 *
 * The reference for each stack is the plain stack (nothing sticks out) of the same width,
 * corner, spacing mode and admin bar: the gap between its card boxes and their distance to
 * the screen edge. A mixed stack must show the same gap between what the cards actually
 * draw (pixels, shadows off), within 4px below (parts under 3px don't get room) and 1px above.
 *
 * Glide: the mixed stack with the Rise and Pop presets. The middle card is closed; the
 * others must glide (a composite animation runs) and end with the same gaps.
 */
import fs from 'node:fs';
import path from 'node:path';
import { OUT, slice, results, themes, settingsFor, fetchPreview, mergePreviews, harnessCss, withCss, launch, decode, inkBox, write, read, exists, sleep } from './lib.mjs';
import { stackCases, referenceOf, STACK_SETS, DESKTOP, PHONE } from './cases.mjs';

const [ cmd ] = process.argv.slice( 2 ).filter( ( x ) => ! x.startsWith( '--' ) );

const GLIDES = [];
for ( const [ show, hide ] of [ [ 'nx-anim-rise-soft', 'nx-anim-rise-soft-out' ], [ 'nx-anim-pop', 'nx-anim-pop-out' ] ] ) {
	for ( const view of [ DESKTOP, PHONE ] ) {
		for ( const position of [ 'bottom_left', 'top_right' ] ) {
			for ( const spacingV2 of [ true, false ] ) {
				GLIDES.push( { glide: true, set: 'free', cards: STACK_SETS.free.map( ( c ) => ( { ...c, show, hide } ) ), position, spacingV2, adminBar: false, ...view,
					id: `glide-${ show.replace( 'nx-anim-', '' ) }-${ view.width }-${ position }-${ spacingV2 ? 'v2' : 'legacy' }` } );
			}
		}
	}
}

async function pageFor( c, list ) {
	const rel = `html-measure/${ c.glide ? 'glide' : 'stack' }/${ c.id }.html`;
	if ( exists( rel ) ) return read( rel ).toString();
	const pages = [];
	for ( const card of c.cards ) pages.push( await fetchPreview( settingsFor( { ...card, position: c.position, width: c.width }, list ) ) );
	const html = mergePreviews( pages, { spacingV2: c.spacingV2, adminBar: c.adminBar } );
	write( rel, html );
	return html;
}

// Card boxes and, one card at a time, the box of what each card draws. The other cards get
// opacity 0 (their parts can't undo it, unlike visibility) with no transition (a card coming
// back would fade in), and only a band around the card counts, so nothing else on the page can.
async function measure( h, vp ) {
	const rects = await h.evaluate( `[...document.querySelectorAll('#notificationx-frontend0 .nx-container > *')].map(el => {
		const r = el.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, left: r.left, right: r.right, res: el.classList.contains('notification-res-item') };
	})` );
	const ink = [];
	for ( let k = 0; k < rects.length; k++ ) {
		await h.evaluate( `[...document.querySelectorAll('#notificationx-frontend0 .nx-container > *')].forEach((el, i) => { el.style.setProperty('transition', 'none', 'important'); el.style.setProperty('opacity', i === ${ k } ? '1' : '0', 'important'); })` );
		const band = { top: Math.max( 0, rects[ k ].top - 80 ), bottom: Math.min( vp.height, rects[ k ].bottom + 80 ) };
		ink.push( inkBox( decode( await h.screenshot() ), band ) );
	}
	await h.evaluate( `[...document.querySelectorAll('#notificationx-frontend0 .nx-container > *')].forEach(el => { el.style.removeProperty('opacity'); el.style.removeProperty('transition'); })` );
	return { rects, ink, vh: vp.height };
}

if ( cmd === 'measure' ) {
	const list = themes();
	const work = slice.pick( [ ...stackCases(), ...GLIDES ] );
	const h = await launch( `stacks-${ slice.i }` );
	const file = path.join( OUT, `stacks/${ slice.file }` );
	fs.mkdirSync( path.dirname( file ), { recursive: true } );
	fs.writeFileSync( file, '' );
	for ( const c of work ) {
		const html = withCss( await pageFor( c, list ), harnessCss( { motion: !! c.glide, shadows: false } ) );
		await h.open( html, c );
		const count = await h.cards( c.cards.length, c.glide ? 1500 : 400 );
		const row = { id: c.id, set: c.set, position: c.position, width: c.width, spacingV2: c.spacingV2, adminBar: c.adminBar, glide: !! c.glide, count, ...( await measure( h, c ) ) };
		if ( c.glide ) {
			// Close the middle card, then look for the glide (a composite: 'add' animation).
			await h.evaluate( `document.querySelectorAll('#notificationx-frontend0 .nx-container > *')[1].querySelector('.notificationx-close').click()` );
			let glided = false;
			for ( let k = 0; k < 20 && ! glided; k++ ) {
				await sleep( 50 );
				glided = await h.evaluate( `[...document.querySelectorAll('#notificationx-frontend0 .nx-container > *')].some(el => el.getAnimations().some(a => a.effect && a.effect.composite === 'add'))` );
			}
			await sleep( 1500 );
			row.glided = glided;
			row.after = await measure( h, c );
		}
		row.errors = [ ...h.errors ];
		fs.appendFileSync( file, JSON.stringify( row ) + '\n' );
	}
	await h.close();
	console.log( `measured ${ work.length } (slice ${ slice.i }/${ slice.n })` );
}

if ( cmd === 'check' ) {
	const rows = results( 'stacks' );
	const byId = new Map( rows.map( ( r ) => [ r.id, r ] ) );
	const sorted = ( m ) => m.rects.map( ( rect, k ) => ( { rect, ink: m.ink[ k ] } ) ).sort( ( a, b ) => a.rect.top - b.rect.top );
	const failures = [];
	let checks = 0;
	const fail = ( id, msg ) => failures.push( `${ id }: ${ msg }` );
	// The plain gap below a card of this kind (Pro mobile card or regular card): legacy spacing
	// gives them 20px and 30px.
	const gapBelow = ( r, res ) => {
		const plain = byId.get( referenceOf( { ...r, set: res ? 'mobile-plain' : 'plain' } ) );
		const p = plain ? sorted( plain ) : null;
		return p ? p[ 1 ].rect.top - p[ 0 ].rect.bottom : NaN;
	};
	const evaluate = ( id, m, ref, expectCount, r ) => {
		const cards = sorted( m );
		const refCards = sorted( ref );
		if ( cards.length !== expectCount ) return fail( id, `${ cards.length } cards, expected ${ expectCount }` );
		if ( cards.some( ( c ) => ! c.ink ) ) return fail( id, 'a card drew nothing' );
		for ( let k = 0; k + 1 < cards.length; k++ ) {
			checks++;
			const refGap = gapBelow( r, cards[ k ].rect.res );
			const gap = cards[ k + 1 ].ink.top - cards[ k ].ink.bottom;
			if ( gap < 0 ) fail( id, `cards ${ k + 1 } and ${ k + 2 } overlap by ${ -gap }px` );
			else if ( gap < refGap - 4 || gap > refGap + 1 ) fail( id, `gap ${ gap }px between what cards ${ k + 1 } and ${ k + 2 } draw, expected ${ refGap }px` );
		}
		checks++;
		if ( m === ref ) return;
		if ( id.includes( 'top_' ) ) {
			const edge = refCards[ 0 ].rect.top;
			if ( cards[ 0 ].ink.top < edge - 1 ) fail( id, `top card draws ${ edge - cards[ 0 ].ink.top }px past the edge spacing (${ Math.round( edge ) }px)` );
		} else {
			const edge = refCards[ refCards.length - 1 ].rect.bottom;
			if ( cards[ cards.length - 1 ].ink.bottom > edge + 1 ) fail( id, `bottom card draws ${ cards[ cards.length - 1 ].ink.bottom - edge }px past the edge spacing (${ Math.round( m.vh - edge ) }px)` );
		}
	};
	for ( const r of rows ) {
		const ref = byId.get( referenceOf( r ) );
		if ( ! ref ) { fail( r.id, `no reference ${ referenceOf( r ) }` ); continue; }
		if ( r.errors?.length ) fail( r.id, `page errors: ${ r.errors.join( ' | ' ).slice( 0, 200 ) }` );
		evaluate( r.id, r, ref, 3, r );
		if ( r.glide ) {
			checks++;
			if ( ! r.glided ) fail( r.id, 'no glide animation after closing the middle card' );
			evaluate( `${ r.id } (after close)`, r.after, ref, 2, r );
		}
	}
	write( 'stacks/report.json', JSON.stringify( { checks, failures }, null, 1 ) );
	console.log( `${ rows.length } stacks (${ rows.filter( ( r ) => r.glide ).length } with glide), ${ checks } checks, ${ failures.length } failures` );
	failures.forEach( ( f ) => console.log( '  FAIL ' + f ) );
	process.exitCode = failures.length ? 1 : 0;
}
