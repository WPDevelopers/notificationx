#!/usr/bin/env node
/**
 * Card size presets (84708): checks that a frontend.css build only added
 * card-size rules to the previous build, so nothing existing changed.
 *
 *   node bin/check-css-append.js [--at-end] old.css new.css
 *
 * Passes when:
 * - new.css is old.css with one insertion, at a top-level rule boundary
 *   (every byte of old.css is kept, in order);
 * - with --at-end, the insertion is at the end (only the old file's trailing
 *   whitespace may follow it): use it for frontend.css, where the partial is
 *   imported last. crossSite.css bundles more CSS after the theme, so there the
 *   rules are inserted where the theme CSS ends;
 * - the insertion is only `@supports (zoom: 1) { … }` blocks, holding only
 *   rules and `@media` blocks, and every selector starts with
 *   `.notification-item.nx-card-size-`.
 *
 * Prints where the rules went, their size and rule count; exits 1 on any failure.
 */
'use strict';

const fs = require( 'fs' );

const PREFIX = '.notification-item.nx-card-size-';
const SUPPORTS = /^@supports\s*\(\s*zoom\s*:\s*1\s*\)$/;

/**
 * Splits CSS into top-level items: { prelude, body } for blocks
 * (`prelude { body }`) and { statement } for `…;` statements.
 */
function parseBlocks( css ) {
	const items = [];
	let i = 0;
	let start = 0;
	let depth = 0;
	let preludeEnd = -1;
	while ( i < css.length ) {
		const ch = css[ i ];
		if ( ch === '/' && css[ i + 1 ] === '*' ) {
			const end = css.indexOf( '*/', i + 2 );
			if ( end === -1 ) {
				throw new Error( 'unterminated comment' );
			}
			i = end + 2;
			continue;
		}
		if ( ch === '"' || ch === "'" ) {
			let j = i + 1;
			while ( j < css.length && css[ j ] !== ch ) {
				j += css[ j ] === '\\' ? 2 : 1;
			}
			i = j + 1;
			continue;
		}
		if ( ch === '{' ) {
			if ( depth === 0 ) {
				preludeEnd = i;
			}
			depth++;
		} else if ( ch === '}' ) {
			depth--;
			if ( depth < 0 ) {
				throw new Error( 'unbalanced "}"' );
			}
			if ( depth === 0 ) {
				items.push( { prelude: stripComments( css.slice( start, preludeEnd ) ).trim(), body: css.slice( preludeEnd + 1, i ) } );
				start = i + 1;
			}
		} else if ( ch === ';' && depth === 0 ) {
			items.push( { statement: stripComments( css.slice( start, i ) ).trim() } );
			start = i + 1;
		}
		i++;
	}
	if ( depth !== 0 ) {
		throw new Error( 'unbalanced "{"' );
	}
	const rest = stripComments( css.slice( start ) ).trim();
	if ( rest ) {
		items.push( { statement: rest } );
	}
	return items;
}

const stripComments = ( s ) => s.replace( /\/\*[\s\S]*?\*\//g, '' );

// Splits a selector list on top-level commas (not inside :is(), :not(), […]).
function splitSelectors( prelude ) {
	const parts = [];
	let depth = 0;
	let current = '';
	for ( const ch of prelude ) {
		if ( ch === '(' || ch === '[' ) {
			depth++;
		} else if ( ch === ')' || ch === ']' ) {
			depth--;
		}
		if ( ch === ',' && depth === 0 ) {
			parts.push( current.trim() );
			current = '';
		} else {
			current += ch;
		}
	}
	parts.push( current.trim() );
	return parts;
}

function checkRules( body, where, errors, stats ) {
	for ( const item of parseBlocks( body ) ) {
		if ( item.statement !== undefined ) {
			errors.push( `${ where }: unexpected statement "${ item.statement.slice( 0, 80 ) }"` );
		} else if ( item.prelude.startsWith( '@media' ) ) {
			checkRules( item.body, `${ where } ${ item.prelude }`, errors, stats );
		} else if ( item.prelude.startsWith( '@' ) ) {
			errors.push( `${ where }: unexpected at-rule "${ item.prelude }"` );
		} else {
			stats.rules++;
			for ( const selector of splitSelectors( item.prelude ) ) {
				if ( ! selector.startsWith( PREFIX ) ) {
					errors.push( `${ where }: selector "${ selector }" does not start with "${ PREFIX }"` );
				}
			}
		}
	}
}

const excerpt = ( css, at ) => JSON.stringify( css.slice( Math.max( 0, at - 60 ), at + 60 ) );

function check( oldCss, newCss, { atEnd = false } = {} ) {
	const errors = [];
	const stats = { appendedBytes: 0, supportsBlocks: 0, rules: 0, at: 0, atEnd: true };

	// The insertion point: the end of the common prefix. Then the rest of the
	// old file must reappear unchanged at the end of the new one.
	let at = 0;
	while ( at < oldCss.length && at < newCss.length && oldCss[ at ] === newCss[ at ] ) {
		at++;
	}
	const rest = oldCss.slice( at );
	const added = newCss.length - oldCss.length;
	if ( added < 0 || ! newCss.endsWith( rest ) ) {
		errors.push(
			`the old file is not kept: first difference at byte ${ at }:\n` +
				`  old: …${ excerpt( oldCss, at ) }\n` +
				`  new: …${ excerpt( newCss, at ) }`
		);
		return { errors, stats };
	}
	const appended = newCss.slice( at, at + added );
	stats.appendedBytes = Buffer.byteLength( appended );
	stats.at = at;
	stats.atEnd = rest.trim() === '';
	if ( ! appended.trim() ) {
		return { errors, stats };
	}

	// It must sit between top-level rules, not inside a block or a declaration.
	const before = stripComments( oldCss.slice( 0, at ) ).trim();
	let boundary = before === '' || /[};]$/.test( before );
	if ( boundary ) {
		try {
			parseBlocks( oldCss.slice( 0, at ) );
		} catch ( e ) {
			boundary = false;
		}
	}
	if ( ! boundary ) {
		errors.push( `the rules were inserted inside a block, at byte ${ at }: …${ excerpt( oldCss, at ) }` );
		return { errors, stats };
	}
	if ( atEnd && ! stats.atEnd ) {
		errors.push( `the rules were inserted at byte ${ at }, not at the end: …${ excerpt( oldCss, at ) }` );
	}

	let items;
	try {
		items = parseBlocks( appended );
	} catch ( e ) {
		errors.push( `the appended CSS does not parse: ${ e.message }` );
		return { errors, stats };
	}
	for ( const item of items ) {
		if ( item.statement !== undefined ) {
			errors.push( `appended statement outside @supports (zoom: 1): "${ item.statement.slice( 0, 80 ) }"` );
		} else if ( ! SUPPORTS.test( item.prelude ) ) {
			errors.push( `appended block outside @supports (zoom: 1): "${ item.prelude.slice( 0, 80 ) }"` );
		} else {
			stats.supportsBlocks++;
			checkRules( item.body, '@supports (zoom: 1)', errors, stats );
		}
	}
	return { errors, stats };
}

if ( require.main === module ) {
	const args = process.argv.slice( 2 );
	const atEnd = args.includes( '--at-end' );
	const [ oldPath, newPath ] = args.filter( ( a ) => a !== '--at-end' );
	if ( ! oldPath || ! newPath ) {
		console.error( 'usage: node bin/check-css-append.js [--at-end] old.css new.css' );
		process.exit( 2 );
	}
	const { errors, stats } = check( fs.readFileSync( oldPath, 'utf8' ), fs.readFileSync( newPath, 'utf8' ), { atEnd } );
	console.log(
		`added ${ stats.appendedBytes } bytes ${ stats.atEnd ? 'at the end' : `at byte ${ stats.at } (where the theme CSS ends)` }: ` +
			`${ stats.supportsBlocks } @supports (zoom: 1) block(s), ${ stats.rules } rule(s)`
	);
	if ( errors.length ) {
		errors.forEach( ( e ) => console.error( `FAIL: ${ e }` ) );
		process.exit( 1 );
	}
	console.log( 'PASS: the old CSS is kept byte for byte; every added rule is card-size scoped inside @supports (zoom: 1)' );
}

module.exports = { check, parseBlocks, splitSelectors };
