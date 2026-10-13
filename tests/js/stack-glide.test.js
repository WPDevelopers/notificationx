/**
 * 84710: stacked cards glide (FLIP) when a card in the same corner appears or leaves.
 * jsdom has no layout, so positions come from data-top (offsetTop is mocked below).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import StackGlide, {
	computeGlides,
	canGlide,
	readPositions,
	GLIDE_MS,
} from '../../nxdev/notificationx/frontend/core/StackGlide';

const map = ( obj ) => new Map( Object.entries( obj ) );

describe( 'computeGlides', () => {
	it( 'returns the move of cards that are in the stack before and after', () => {
		const glides = computeGlides( map( { a: 100, b: 200 } ), map( { a: 60, b: 260 } ), 800 );
		expect( glides ).toEqual( [
			{ key: 'a', dy: 40 },
			{ key: 'b', dy: -60 },
		] );
	} );

	it( 'skips a new card (it plays its own entrance)', () => {
		expect( computeGlides( map( { a: 100 } ), map( { a: 60, n: 200 } ), 800 ) ).toEqual( [ { key: 'a', dy: 40 } ] );
	} );

	it( 'ignores a removed card (it played its own exit)', () => {
		expect( computeGlides( map( { a: 100, gone: 200 } ), map( { a: 130 } ), 800 ) ).toEqual( [ { key: 'a', dy: -30 } ] );
	} );

	it( 'skips cards that did not move, or moved less than 1px', () => {
		expect( computeGlides( map( { a: 100, b: 200 } ), map( { a: 100, b: 200.4 } ), 800 ) ).toEqual( [] );
	} );

	it( 'skips implausible moves larger than the cap', () => {
		expect( computeGlides( map( { a: 100 } ), map( { a: 1100 } ), 800 ) ).toEqual( [] );
	} );

	it( 'returns nothing for a single card replaced by another (Global Queue)', () => {
		expect( computeGlides( map( { a: 100 } ), map( { b: 100 } ), 800 ) ).toEqual( [] );
	} );
} );

const supportedWindow = ( reduced = false ) => {
	function KeyframeEffect() {}
	KeyframeEffect.prototype.composite = 'replace';
	return {
		Element: { prototype: { animate() {} } },
		KeyframeEffect,
		matchMedia: () => ( { matches: reduced } ),
	};
};

describe( 'canGlide', () => {
	it( 'is true when the Web Animations API supports composite', () => {
		expect( canGlide( supportedWindow() ) ).toBe( true );
	} );

	it( 'is false with reduced motion', () => {
		expect( canGlide( supportedWindow( true ) ) ).toBe( false );
	} );

	it( 'is false without element.animate', () => {
		const win = supportedWindow();
		win.Element.prototype.animate = undefined;
		expect( canGlide( win ) ).toBe( false );
	} );

	it( 'is false when composite is not supported', () => {
		const win = supportedWindow();
		delete win.KeyframeEffect.prototype.composite;
		expect( canGlide( win ) ).toBe( false );
	} );

	it( 'is false without a window', () => {
		expect( canGlide( null ) ).toBe( false );
	} );
} );

describe( 'StackGlide', () => {
	let container;
	let animate;
	const originalOffsetTop = Object.getOwnPropertyDescriptor( HTMLElement.prototype, 'offsetTop' );

	beforeAll( () => {
		Object.defineProperty( HTMLElement.prototype, 'offsetTop', {
			configurable: true,
			get() {
				return Number( this.dataset.top || 0 );
			},
		} );
	} );
	afterAll( () => {
		if ( originalOffsetTop ) {
			Object.defineProperty( HTMLElement.prototype, 'offsetTop', originalOffsetTop );
		}
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		animate = jest.fn();
		Element.prototype.animate = animate;
		window.KeyframeEffect = function KeyframeEffect() {};
		window.KeyframeEffect.prototype.composite = 'replace';
		window.matchMedia = jest.fn( () => ( { matches: false } ) );
	} );
	afterEach( () => {
		ReactDOM.unmountComponentAtNode( container );
		container.remove();
		delete Element.prototype.animate;
		delete window.KeyframeEffect;
		delete window.matchMedia;
	} );

	// cards: [ [ key, top ], ... ]
	const render = ( cards, enabled = true ) => {
		act( () => {
			ReactDOM.render(
				<StackGlide enabled={ enabled } itemKeys={ cards.map( ( [ k ] ) => k ).join( ',' ) }>
					{ ( ref ) => (
						<div className="nx-container" ref={ ref }>
							{ cards.map( ( [ k, top ] ) => (
								<div key={ k } data-nx-key={ k } data-top={ top } />
							) ) }
						</div>
					) }
				</StackGlide>,
				container
			);
		} );
	};

	it( 'renders only its child (no extra element)', () => {
		render( [ [ 'a', 0 ] ] );
		expect( container.children ).toHaveLength( 1 );
		expect( container.firstChild.className ).toBe( 'nx-container' );
	} );

	it( 'glides the cards that moved when a card leaves, adding to their own transform', () => {
		render( [ [ 'a', 0 ], [ 'b', 130 ], [ 'c', 260 ] ] );
		render( [ [ 'a', 130 ], [ 'c', 260 ] ] );
		expect( animate ).toHaveBeenCalledTimes( 1 );
		const [ keyframes, options ] = animate.mock.calls[ 0 ];
		expect( keyframes ).toEqual( [ { transform: 'translateY(-130px)' }, { transform: 'none' } ] );
		expect( options ).toMatchObject( { duration: GLIDE_MS, composite: 'add' } );
		expect( animate.mock.instances[ 0 ].dataset.nxKey ).toBe( 'a' );
	} );

	it( 'glides existing cards when a card is added, but not the new card', () => {
		render( [ [ 'a', 130 ] ] );
		render( [ [ 'a', 0 ], [ 'n', 130 ] ] );
		expect( animate ).toHaveBeenCalledTimes( 1 );
		expect( animate.mock.instances[ 0 ].dataset.nxKey ).toBe( 'a' );
		expect( animate.mock.calls[ 0 ][ 0 ][ 0 ] ).toEqual( { transform: 'translateY(130px)' } );
	} );

	it( 'does nothing with reduced motion', () => {
		window.matchMedia = jest.fn( () => ( { matches: true } ) );
		render( [ [ 'a', 0 ], [ 'b', 130 ] ] );
		render( [ [ 'b', 0 ] ] );
		expect( animate ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when disabled (shortcode, center, cookie containers)', () => {
		render( [ [ 'a', 0 ], [ 'b', 130 ] ], false );
		render( [ [ 'b', 0 ] ], false );
		expect( animate ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when the stack did not change (e.g. a re-render after resize)', () => {
		render( [ [ 'a', 0 ], [ 'b', 130 ] ] );
		render( [ [ 'a', 40 ], [ 'b', 170 ] ] );
		expect( animate ).not.toHaveBeenCalled();
	} );

	it( 'reads positions only from tracked cards', () => {
		const el = document.createElement( 'div' );
		el.innerHTML = '<div data-nx-key="a" data-top="10"></div><div class="nx-popup" data-top="99"></div>';
		expect( readPositions( el ) ).toEqual( new Map( [ [ 'a', 10 ] ] ) );
		expect( readPositions( null ) ).toEqual( new Map() );
	} );
} );
