/**
 * Before/after screenshots: cards without anything sticking out must not move by a pixel.
 *
 *   node baseline.mjs capture before [--slice=i/n]   (on the old build: stores each page's HTML)
 *   node baseline.mjs capture after  [--slice=i/n]   (on the new build: reuses the stored HTML)
 *   node baseline.mjs diff before after
 *
 * The stored HTML keeps the content identical between runs (preview entries hold random
 * numbers); only the CSS and JS the page loads differ. Full-viewport screenshots, real
 * corner positions, shadows on, motion off.
 */
import fs from 'node:fs';
import path from 'node:path';
import { OUT, slice, themes, settingsFor, fetchPreview, mergePreviews, harnessCss, withCss, launch, decode, diffPixels, write, read, exists } from './lib.mjs';
import { singleCases, stackCases } from './cases.mjs';

const [ cmd, a, b ] = process.argv.slice( 2 ).filter( ( x ) => ! x.startsWith( '--' ) );

async function pageFor( kind, c, list ) {
	const rel = `html/${ kind }/${ c.id }.html`;
	if ( exists( rel ) ) return read( rel ).toString();
	let html;
	if ( kind === 'single' ) {
		html = await fetchPreview( settingsFor( c, list ) );
	} else {
		const pages = [];
		for ( const card of c.cards ) pages.push( await fetchPreview( settingsFor( { ...card, position: c.position, width: c.width }, list ) ) );
		html = mergePreviews( pages, { spacingV2: c.spacingV2, adminBar: c.adminBar } );
	}
	write( rel, html );
	return html;
}

const DECLARED = `(() => [...document.querySelectorAll('#notificationx-frontend0 .nx-container > *')].map(el => {
	const cs = getComputedStyle(el);
	return { top: cs.getPropertyValue('--nx-overhang-top').trim(), bottom: cs.getPropertyValue('--nx-overhang-bottom').trim() };
}))()`;

if ( cmd === 'capture' ) {
	const list = themes();
	const work = slice.pick( [ ...singleCases( list ).map( ( c ) => [ 'single', c ] ), ...stackCases().map( ( c ) => [ 'stack', c ] ) ] );
	const h = await launch( `baseline-${ a }-${ slice.i }` );
	const log = path.join( OUT, `${ a }/log-${ slice.i }.jsonl` );
	fs.mkdirSync( path.dirname( log ), { recursive: true } );
	fs.writeFileSync( log, '' );
	for ( const [ kind, c ] of work ) {
		const html = withCss( await pageFor( kind, c, list ), harnessCss( { motion: false, shadows: true } ) );
		await h.open( html, c );
		const count = await h.cards( kind === 'stack' ? c.cards.length : 1 );
		write( `${ a }/${ kind }/${ c.id }.png`, await h.screenshot() );
		fs.appendFileSync( log, JSON.stringify( { kind, id: c.id, count, declared: await h.evaluate( DECLARED ), errors: [ ...h.errors ] } ) + '\n' );
	}
	await h.close();
	console.log( `captured ${ work.length } (${ a }, slice ${ slice.i }/${ slice.n })` );
}

if ( cmd === 'diff' ) {
	const logs = ( label ) => fs.readdirSync( path.join( OUT, label ) ).filter( ( f ) => f.startsWith( 'log-' ) )
		.flatMap( ( f ) => fs.readFileSync( path.join( OUT, label, f ), 'utf8' ).trim().split( '\n' ).filter( Boolean ).map( JSON.parse ) );
	const after = new Map( logs( b ).map( ( r ) => [ `${ r.kind }/${ r.id }`, r ] ) );
	const rows = [];
	for ( const r of logs( a ) ) {
		const key = `${ r.kind }/${ r.id }`;
		const n = diffPixels( decode( read( `${ a }/${ key }.png` ) ), decode( read( `${ b }/${ key }.png` ) ) );
		const declared = ( after.get( key )?.declared || [] ).some( ( d ) => ( d.top && d.top !== '0px' ) || ( d.bottom && d.bottom !== '0px' ) );
		rows.push( { key, diff: n, declared, cards: [ r.count, after.get( key )?.count ] } );
	}
	write( `diff-${ a }-${ b }.json`, JSON.stringify( rows, null, 1 ) );
	const plain = rows.filter( ( r ) => ! r.declared );
	const changedPlain = plain.filter( ( r ) => r.diff !== 0 );
	const missing = rows.filter( ( r ) => r.cards[ 0 ] !== r.cards[ 1 ] || ! r.cards[ 0 ] );
	console.log( `compared ${ rows.length }: ${ plain.length } without overhang (${ changedPlain.length } changed), ${ rows.length - plain.length } with overhang (${ rows.filter( ( r ) => r.declared && r.diff ).length } changed as expected)` );
	changedPlain.forEach( ( r ) => console.log( `  FAIL ${ r.key }: ${ r.diff } different pixels` ) );
	missing.forEach( ( r ) => console.log( `  FAIL ${ r.key }: card count ${ r.cards.join( ' -> ' ) }` ) );
	process.exitCode = changedPlain.length || missing.length ? 1 : 0;
}
