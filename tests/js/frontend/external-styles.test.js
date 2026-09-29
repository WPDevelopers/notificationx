import {
	applyDeferredStyles,
	DEFAULT_EXTERNAL_STYLES,
	loadExternalStyles,
	styleApplies,
	whenStyled,
} from '../../../nxdev/notificationx/frontend/core/external-styles';

const settled = async ( promise ) => {
	let done = false;
	promise.then( () => ( done = true ) );
	for ( let i = 0; i < 4; i++ ) {
		await Promise.resolve();
	}
	return done;
};

const links = () =>
	Array.from( document.head.querySelectorAll( 'link[rel="stylesheet"]' ) );

describe( 'loadExternalStyles (cross-domain fonts and icons)', () => {
	afterEach( () => {
		document.head.innerHTML = '';
	} );

	it( 'adds the defaults when the config has no list (old snippets)', () => {
		loadExternalStyles( undefined );
		expect( links().map( ( l ) => l.id ) ).toEqual(
			Object.keys( DEFAULT_EXTERNAL_STYLES ).map( ( h ) => h + '-css' )
		);
	} );

	it( 'adds only what the site allows', () => {
		loadExternalStyles( {
			'notificationx-fontawesome-4':
				DEFAULT_EXTERNAL_STYLES[ 'notificationx-fontawesome-4' ],
		} );
		expect( links().map( ( l ) => l.id ) ).toEqual( [
			'notificationx-fontawesome-4-css',
		] );
	} );

	it( 'adds nothing when every stylesheet is opted out (PHP sends [])', () => {
		loadExternalStyles( [] );
		expect( links() ).toHaveLength( 0 );
	} );

	it( 'does not add a stylesheet twice', () => {
		loadExternalStyles();
		loadExternalStyles();
		expect( links() ).toHaveLength(
			Object.keys( DEFAULT_EXTERNAL_STYLES ).length
		);
	} );
} );

describe( 'whenStyled (non-blocking notificationx-public)', () => {
	afterEach( () => {
		document.head.innerHTML = '';
	} );

	const deferredLink = () => {
		const link = document.createElement( 'link' );
		link.id = 'notificationx-public-css';
		link.rel = 'stylesheet';
		link.media = 'print';
		document.head.appendChild( link );
		return link;
	};

	it( 'resolves at once when the sheet is not on the page (cross-domain)', async () => {
		expect( await settled( whenStyled( 'notificationx-public-css' ) ) ).toBe( true );
	} );

	it( 'resolves at once when the sheet already applies', async () => {
		deferredLink().media = 'all';
		expect( await settled( whenStyled( 'notificationx-public-css' ) ) ).toBe( true );
	} );

	it( 'waits for a deferred sheet to load', async () => {
		const link = deferredLink();
		const ready = whenStyled( 'notificationx-public-css' );
		expect( await settled( ready ) ).toBe( false );
		link.dispatchEvent( new Event( 'load' ) );
		expect( await settled( ready ) ).toBe( true );
	} );

	it( 'does not hold the render back when the sheet fails', async () => {
		const link = deferredLink();
		const ready = whenStyled( 'notificationx-public-css' );
		link.dispatchEvent( new Event( 'error' ) );
		expect( await settled( ready ) ).toBe( true );
	} );

	it( 'switches a print sheet whose onload never ran (stripped / CSP) and waits for it', async () => {
		const link = deferredLink();
		const ready = whenStyled( 'notificationx-public-css' );
		expect( link.media ).toBe( 'all' );
		expect( await settled( ready ) ).toBe( false );
		link.dispatchEvent( new Event( 'load' ) );
		expect( await settled( ready ) ).toBe( true );
	} );

	it( 'still waits when applyDeferredStyles() switched the sheet first', async () => {
		const link = deferredLink();
		link.setAttribute( 'data-nx-style', 'print' );
		applyDeferredStyles();
		expect( link.media ).toBe( 'all' );
		const ready = whenStyled( 'notificationx-public-css' );
		expect( await settled( ready ) ).toBe( false );
		link.dispatchEvent( new Event( 'load' ) );
		expect( await settled( ready ) ).toBe( true );
	} );

	it( 'leaves a media other than print alone', async () => {
		const link = deferredLink();
		link.media = 'screen';
		expect( await settled( whenStyled( 'notificationx-public-css' ) ) ).toBe( true );
		expect( link.media ).toBe( 'screen' );
	} );
} );

describe( 'whenStyled with a fallback (sheet combined by an optimizer)', () => {
	const fallback = { href: 'https://example.org/frontend.css?ver=1', probe: 'frontend' };
	const probeRule = () => {
		const style = document.createElement( 'style' );
		style.textContent = '.nx-style-probe { --nx-frontend: 1; }';
		document.head.appendChild( style );
	};

	afterEach( () => {
		document.head.innerHTML = '';
	} );

	it( 'reads the probe rule', () => {
		expect( styleApplies( 'frontend' ) ).toBe( false );
		probeRule();
		expect( styleApplies( 'frontend' ) ).toBe( true );
		expect( document.querySelectorAll( '.nx-style-probe' ) ).toHaveLength( 0 );
	} );

	it( 'adds nothing when the combined bundle applies', async () => {
		probeRule();
		await whenStyled( 'notificationx-public-css', fallback );
		expect( document.getElementById( 'notificationx-public-css' ) ).toBeNull();
	} );

	it( 'adds the sheet again when it does not apply, and waits for it', async () => {
		const ready = whenStyled( 'notificationx-public-css', fallback );
		await new Promise( ( r ) => setTimeout( r, 0 ) );
		const link = document.getElementById( 'notificationx-public-css' );
		expect( link ).not.toBeNull();
		expect( link.getAttribute( 'href' ) ).toBe( fallback.href );
		expect( link.rel ).toBe( 'stylesheet' );
		expect( await settled( ready ) ).toBe( false );
		link.dispatchEvent( new Event( 'load' ) );
		expect( await settled( ready ) ).toBe( true );
	} );

	it( 'adds it only once for several configs', async () => {
		whenStyled( 'notificationx-public-css', fallback );
		whenStyled( 'notificationx-public-css', fallback );
		await new Promise( ( r ) => setTimeout( r, 0 ) );
		expect( document.querySelectorAll( '#notificationx-public-css' ) ).toHaveLength( 1 );
	} );
} );

describe( 'applyDeferredStyles', () => {
	afterEach( () => {
		document.head.innerHTML = '';
	} );

	it( 'switches the marked print sheets and leaves other links alone', () => {
		document.head.innerHTML =
			'<link id="a" rel="stylesheet" media="print" data-nx-style="print">' +
			'<link id="b" rel="stylesheet" media="print">' +
			'<link id="c" rel="preload" as="style" data-nx-style="print">';
		applyDeferredStyles();
		expect( document.getElementById( 'a' ).media ).toBe( 'all' );
		expect( document.getElementById( 'b' ).media ).toBe( 'print' );
		expect( document.getElementById( 'c' ).rel ).toBe( 'preload' );
	} );
} );
