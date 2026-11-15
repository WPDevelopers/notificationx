/**
 * The seasonal offer pointer (assets/admin/js/offer-pointer.js).
 *
 * The copy arrives as plain text and every node is built with `.text()` / `.attr()`, so a title
 * or a link can never inject markup. jQuery is not a dependency of the test suite; the few
 * methods the script calls are stood in for over the real DOM.
 */
const {
	POINTER_CLASS,
	boot,
	isTargetVisible,
	pointerButtons,
	pointerContent,
	pointerOffset,
	pointerOptions,
	pointerPosition,
} = require( '../../../assets/admin/js/offer-pointer' );

/** The part of jQuery the helpers use, over jsdom. */
const $ = ( html ) => {
	const template = document.createElement( 'template' );
	template.innerHTML = html;

	const wrap = ( el ) => ( {
		el,
		text( value ) {
			el.textContent = value;
			return this;
		},
		attr( map ) {
			Object.entries( map ).forEach( ( [ key, value ] ) =>
				el.setAttribute( key, value )
			);
			return this;
		},
		append( ...children ) {
			children.forEach( ( child ) => el.appendChild( child.el ) );
			return this;
		},
		on( type, handler ) {
			el.addEventListener( type, handler );
			return this;
		},
		html() {
			return el.innerHTML;
		},
	} );

	return wrap( template.content.firstChild );
};

const DATA = {
	title: 'Black Friday Is On!',
	text: 'Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers.',
	button: 'View Deals',
	dismiss: 'Dismiss',
	url: 'https://notificationx.com/bfcm2026/admin-notice',
	edge: 'left',
};

describe( 'pointerContent', () => {
	it( 'is a heading and one paragraph', () => {
		expect( pointerContent( $, DATA ) ).toBe(
			`<h3>${ DATA.title }</h3><p>${ DATA.text }</p>`
		);
	} );

	it( 'never turns copy into markup', () => {
		const html = pointerContent( $, {
			title: '<img src=x onerror=alert(1)>',
			text: '<script>alert(1)</script>',
		} );

		expect( html ).not.toContain( '<img' );
		expect( html ).not.toContain( '<script' );
		expect( html ).toContain( '&lt;img' );
	} );

	it( 'survives missing data', () => {
		expect( pointerContent( $, undefined ) ).toBe( '<h3></h3><p></p>' );
		expect( pointerContent( $, { title: null } ) ).toBe(
			'<h3></h3><p></p>'
		);
	} );
} );

describe( 'pointerButtons', () => {
	it( 'is one row: the campaign link, then Dismiss', () => {
		const row = pointerButtons( $, DATA, () => {} ).el;
		const [ deals, dismiss ] = row.children;

		expect( row.className ).toBe( 'nx-offer-pointer__actions' );
		expect( row.children ).toHaveLength( 2 );

		expect( deals.textContent ).toBe( 'View Deals' );
		expect( deals.getAttribute( 'href' ) ).toBe( DATA.url );
		expect( deals.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( deals.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );
		expect( deals.className ).toBe( 'button button-primary' );

		expect( dismiss.textContent ).toBe( 'Dismiss' );
		expect( dismiss.className ).toBe( 'close' );
		expect( dismiss.getAttribute( 'role' ) ).toBe( 'button' );
	} );

	it( 'closes on Dismiss without following the link', () => {
		const onClose = jest.fn();
		const dismiss = pointerButtons( $, DATA, onClose ).el.children[ 1 ];
		const click = new window.MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
		} );

		dismiss.dispatchEvent( click );

		expect( onClose ).toHaveBeenCalledTimes( 1 );
		expect( click.defaultPrevented ).toBe( true );
	} );

	it( 'does not close when the campaign link is followed', () => {
		const onClose = jest.fn();
		const deals = pointerButtons( $, DATA, onClose ).el.children[ 0 ];

		deals.addEventListener( 'click', ( event ) => event.preventDefault() );
		deals.dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true, cancelable: true } )
		);

		expect( onClose ).not.toHaveBeenCalled();
	} );

	it( 'falls back to "#" without a link', () => {
		const deals = pointerButtons( $, {}, () => {} ).el.children[ 0 ];

		expect( deals.getAttribute( 'href' ) ).toBe( '#' );
	} );
} );

describe( 'pointerOptions', () => {
	it( 'carries the class the stylesheet is scoped to', () => {
		expect( POINTER_CLASS ).toBe( 'wp-pointer nx-offer-pointer' );
		expect( pointerOptions( $, DATA, () => {} ).pointerClass ).toBe(
			POINTER_CLASS
		);
	} );

	it( 'opens on the side the menu is on', () => {
		expect( pointerOptions( $, DATA, () => {} ).position ).toEqual( {
			edge: 'left',
			align: 'center',
		} );
		expect(
			pointerOptions( $, { ...DATA, edge: 'right' }, () => {} ).position
				.edge
		).toBe( 'right' );
		// Anything else is the left-to-right default.
		expect(
			pointerOptions( $, { ...DATA, edge: 'top' }, () => {} ).position
				.edge
		).toBe( 'left' );
		expect( pointerOptions( $, undefined, () => {} ).position.edge ).toBe(
			'left'
		);
	} );

	it( 'records the dismissal when WordPress closes the pointer', () => {
		const onDismiss = jest.fn();
		const options = pointerOptions( $, DATA, onDismiss );

		expect( options.close ).toBe( onDismiss );
	} );

	it( 'closes the pointer it was handed when Dismiss is clicked', () => {
		const pointer = jest.fn();
		const options = pointerOptions( $, DATA, () => {} );
		const row = options.buttons( {}, { element: { pointer } } ).el;

		row.children[ 1 ].dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true, cancelable: true } )
		);

		expect( pointer ).toHaveBeenCalledWith( 'close' );
	} );
} );

describe( 'placement beside the menu link', () => {
	it( 'remembers the gap WordPress left, then keeps it', () => {
		const offset = pointerOffset(
			{ top: 300, left: 0 },
			{ top: 262, left: 160 }
		);

		expect( offset ).toEqual( { top: -38, left: 160 } );
		// The page scrolled 120px; the pinned menu moved its link to 180.
		expect( pointerPosition( { top: 180, left: 0 }, offset ) ).toEqual( {
			top: 142,
			left: 160,
		} );
	} );

	it( 'places in whole pixels', () => {
		expect(
			pointerPosition(
				{ top: 100.4, left: 0.6 },
				{ top: 10.2, left: 159.9 }
			)
		).toEqual( { top: 111, left: 161 } );
	} );

	it( 'survives missing boxes', () => {
		expect( pointerOffset( undefined, undefined ) ).toEqual( {
			top: 0,
			left: 0,
		} );
		expect( pointerPosition( undefined, undefined ) ).toEqual( {
			top: 0,
			left: 0,
		} );
	} );

	it( 'knows when there is no link on screen to point at', () => {
		expect( isTargetVisible( { width: 160, height: 34, right: 160 } ) ).toBe(
			true
		);
		// The off-canvas menu under 783px: the link sits left of the window.
		expect( isTargetVisible( { width: 190, height: 44, right: -1 } ) ).toBe(
			false
		);
		// A hidden item has no box.
		expect( isTargetVisible( { width: 0, height: 0, right: 0 } ) ).toBe(
			false
		);
		expect( isTargetVisible( undefined ) ).toBe( false );
	} );
} );

describe( 'boot', () => {
	it( 'does nothing without the campaign data or the pointer widget', () => {
		const ready = jest.fn();

		// No data: the pointer is not due on this page.
		boot( {}, Object.assign( ready, { fn: { pointer() {} } } ) );
		// No wp-pointer widget.
		boot( { nxOfferPointer: DATA }, Object.assign( ready, { fn: {} } ) );
		boot( { nxOfferPointer: DATA }, undefined );

		expect( ready ).not.toHaveBeenCalled();
	} );

	it( 'shows nothing when the menu item is not on the page', () => {
		const pointer = jest.fn();
		const jq = ( arg ) => {
			if ( typeof arg === 'function' ) {
				arg();
				return undefined;
			}
			return { length: 0, pointer };
		};
		jq.fn = { pointer() {} };

		boot( { nxOfferPointer: { ...DATA, target: '#missing' } }, jq );

		expect( pointer ).not.toHaveBeenCalled();
	} );
} );
