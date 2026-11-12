/**
 * Card size presets (84708): getCardSize() and the class and `--nx-scale`
 * variable on the Notification root.
 *
 * Default, a missing key and anything unexpected must render byte for byte as
 * before; Compact and Large may only add the class and the variable to the root.
 */
import { getCardSize, CARD_SIZE_SCALES } from '../../../nxdev/notificationx/frontend/core/functions';
import { CARD_SIZE_THEMES } from './card-size-fixtures';
import { renderCard } from './card-size-render';

const THEME = 'conversions_theme-one';

const rootOf = ( html ) => {
	const holder = document.createElement( 'div' );
	holder.innerHTML = html;
	return holder.querySelector( '.notification-item' );
};

describe( 'getCardSize', () => {
	it( 'returns compact and large', () => {
		expect( getCardSize( { card_size: 'compact', themes: THEME } ) ).toBe( 'compact' );
		expect( getCardSize( { card_size: 'large', themes: THEME } ) ).toBe( 'large' );
	} );

	it.each( [
		[ 'default' ],
		[ undefined ],
		[ null ],
		[ '' ],
		[ 'Compact' ],
		[ 'huge' ],
		[ 'toString' ],
		[ '__proto__' ],
		[ 0.8 ],
		[ 1 ],
		[ true ],
		[ [ 'compact' ] ],
		[ { compact: true } ],
	] )( 'returns null for %p', ( value ) => {
		expect( getCardSize( { card_size: value, themes: THEME } ) ).toBeNull();
	} );

	it( 'returns null without settings or a theme key', () => {
		expect( getCardSize( undefined ) ).toBeNull();
		expect( getCardSize( null ) ).toBeNull();
		expect( getCardSize( { card_size: 'compact' } ) ).toBeNull();
		expect( getCardSize( { card_size: 'compact', themes: [ THEME ] } ) ).toBeNull();
	} );

	it( 'ignores Verified Cards themes', () => {
		expect( getCardSize( { card_size: 'compact', themes: 'conversions_vc-receipt' } ) ).toBeNull();
		expect( getCardSize( { card_size: 'large', themes: 'announcements_vc-voucher' } ) ).toBeNull();
	} );

	it( 'matches the server scales', () => {
		expect( CARD_SIZE_SCALES ).toEqual( { compact: '0.8', large: '1.2' } );
	} );
} );

describe( 'Notification root', () => {
	it.each( [ [ 'default' ], [ 'huge' ], [ '' ], [ null ] ] )( 'card_size %p renders exactly like no card_size', ( value ) => {
		expect( renderCard( THEME, { card_size: value } ) ).toBe( renderCard( THEME ) );
	} );

	it( 'Default adds no class and no variable', () => {
		const root = rootOf( renderCard( THEME, { card_size: 'default' } ) );
		expect( root.className ).not.toMatch( /nx-card-size/ );
		expect( root.getAttribute( 'style' ) ).not.toMatch( /--nx-scale/ );
	} );

	it.each( [
		[ 'compact', '0.8' ],
		[ 'large', '1.2' ],
	] )( '%s adds the class and --nx-scale to the root only', ( size, scale ) => {
		const html = renderCard( THEME, { card_size: size } );
		const root = rootOf( html );
		expect( root.classList.contains( `nx-card-size-${ size }` ) ).toBe( true );
		expect( root.style.getPropertyValue( '--nx-scale' ) ).toBe( scale );
		expect( html.match( /nx-card-size-/g ) ).toHaveLength( 1 );

		// Without the added class and variable, the root and everything inside it are Default's.
		const defaultRoot = rootOf( renderCard( THEME ) );
		root.classList.remove( `nx-card-size-${ size }` );
		root.style.removeProperty( '--nx-scale' );
		expect( root.className ).toBe( defaultRoot.className );
		expect( root.getAttribute( 'style' ) ).toBe( defaultRoot.getAttribute( 'style' ) );
		expect( root.innerHTML ).toBe( defaultRoot.innerHTML );
	} );

	it( 'Compact changes only the root in every in-scope theme', () => {
		for ( const themes of CARD_SIZE_THEMES ) {
			const root = rootOf( renderCard( themes, { card_size: 'compact' } ) );
			const defaultRoot = rootOf( renderCard( themes ) );
			expect( root.classList.contains( 'nx-card-size-compact' ) ).toBe( true );
			expect( root.innerHTML ).toBe( defaultRoot.innerHTML );
		}
	} );
} );
