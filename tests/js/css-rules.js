/**
 * Reads minified CSS into a flat list of rules, for tests that check what a
 * built stylesheet contains.
 */

/**
 * @param {string} css Minified CSS.
 * @return {{media: string, selector: string, decls: string[]}[]} One entry per selector.
 */
function rules( css ) {
	css = css.replace( /\/\*[\s\S]*?\*\//g, '' );
	const out = [];
	const walk = ( text, media ) => {
		let k = 0;
		while ( k < text.length ) {
			const open = text.indexOf( '{', k );
			if ( open < 0 ) break;
			const prelude = text.slice( k, open ).trim();
			let depth = 1;
			let j = open + 1;
			while ( j < text.length && depth ) {
				if ( text[ j ] === '{' ) depth++;
				else if ( text[ j ] === '}' ) depth--;
				j++;
			}
			const body = text.slice( open + 1, j - 1 );
			if ( prelude.startsWith( '@media' ) || prelude.startsWith( '@supports' ) ) {
				walk( body, prelude );
			} else if ( ! prelude.startsWith( '@' ) ) {
				const decls = body.split( ';' ).map( ( d ) => d.trim() ).filter( Boolean );
				splitSelectors( prelude ).forEach( ( selector ) => out.push( { media: media || '', selector: selector.trim(), decls } ) );
			}
			k = j;
		}
	};
	walk( css, '' );
	return out;
}

// Split a selector list on top-level commas (not inside :is(), :not(), :has()).
function splitSelectors( prelude ) {
	const parts = [];
	let depth = 0;
	let start = 0;
	for ( let i = 0; i < prelude.length; i++ ) {
		const c = prelude[ i ];
		if ( c === '(' ) depth++;
		else if ( c === ')' ) depth--;
		else if ( c === ',' && depth === 0 ) {
			parts.push( prelude.slice( start, i ) );
			start = i + 1;
		}
	}
	parts.push( prelude.slice( start ) );
	return parts;
}

/**
 * Declarations of every rule with exactly this selector (and media query).
 */
const decls = ( all, selector, media = '' ) =>
	all.filter( ( r ) => r.selector === selector && r.media.replace( /\s/g, '' ) === media.replace( /\s/g, '' ) ).flatMap( ( r ) => r.decls );

module.exports = { rules, decls };
