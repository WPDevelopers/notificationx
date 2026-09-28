import {
	DEFAULT_EXTERNAL_STYLES,
	loadExternalStyles,
	whenStyled,
} from '../../../nxdev/notificationx/frontend/core/external-styles';

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

	const settled = async ( promise ) => {
		let done = false;
		promise.then( () => ( done = true ) );
		await Promise.resolve();
		await Promise.resolve();
		return done;
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
} );
