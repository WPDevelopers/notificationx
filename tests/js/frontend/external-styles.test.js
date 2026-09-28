import {
	DEFAULT_EXTERNAL_STYLES,
	loadExternalStyles,
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
