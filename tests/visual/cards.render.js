/**
 * Screenshot tool, step 1: renders every in-scope theme (credit on and off,
 * for each requested card size) with the real `Notification` and writes the
 * markup to the JSON file named by NX_VISUAL_CARDS. See README.md.
 *
 * NX_VISUAL_SIZES: comma-separated card sizes (default "default").
 */
import fs from 'fs';
import path from 'path';
import { CARD_SIZE_THEMES, familyOf } from '../js/frontend/card-size-fixtures';
import { renderCard } from '../js/frontend/card-size-render';

const out = process.env.NX_VISUAL_CARDS;
const sizes = ( process.env.NX_VISUAL_SIZES || 'default' ).split( ',' ).map( ( s ) => s.trim() ).filter( Boolean );

it( 'writes the cards for the screenshot tool', () => {
	expect( out ).toBeTruthy();
	const cards = [];
	for ( const size of sizes ) {
		for ( const credit of [ 'on', 'off' ] ) {
			for ( const themes of CARD_SIZE_THEMES ) {
				const extra = { card_size: size };
				if ( credit === 'off' ) {
					extra.disable_powered_by = true;
				}
				cards.push( { themes, family: familyOf( themes ), credit, size, html: renderCard( themes, extra ) } );
			}
		}
	}
	fs.mkdirSync( path.dirname( out ), { recursive: true } );
	fs.writeFileSync( out, JSON.stringify( cards, null, '\t' ) );
	expect( cards.length ).toBe( sizes.length * 2 * CARD_SIZE_THEMES.length );
} );
