/**
 * Card size presets (84708): bin/check-css-append.js, the "frontend.css only
 * gained card-size rules" check.
 */
const { check, splitSelectors } = require( '../../bin/check-css-append' );

const OLD = '.notification-item{min-width:300px}.nx-style-probe{--nx-frontend:1}';
const ok = ( appended ) => check( OLD, OLD + appended ).errors;

describe( 'check-css-append', () => {
	it( 'passes an unchanged file', () => {
		const { errors, stats } = check( OLD, OLD );
		expect( errors ).toEqual( [] );
		expect( stats.appendedBytes ).toBe( 0 );
	} );

	it( 'passes scoped rules inside @supports (zoom: 1), with @media inside', () => {
		const { errors, stats } = check(
			OLD,
			OLD +
				'@supports (zoom:1){.notification-item.nx-card-size-compact>.notificationx-inner{zoom:var(--nx-scale)}' +
				'@media (min-width:481px){.notification-item.nx-card-size-compact,.notification-item.nx-card-size-large:not(.themes-a,.themes-b){min-width:calc(300px*var(--nx-scale))}}}'
		);
		expect( errors ).toEqual( [] );
		expect( stats ).toMatchObject( { supportsBlocks: 1, rules: 2 } );
	} );

	it( 'accepts the unminified @supports spelling', () => {
		expect( ok( '\n@supports ( zoom: 1 ) {\n  .notification-item.nx-card-size-large { min-width: 360px; }\n}\n' ) ).toEqual( [] );
	} );

	it( 'fails when an existing byte changed', () => {
		const errors = check( OLD, OLD.replace( '300px', '301px' ) + '@supports (zoom:1){}' ).errors;
		expect( errors ).toHaveLength( 1 );
		expect( errors[ 0 ] ).toMatch( /the old file is not kept: first difference at byte 31/ );
	} );

	it( 'fails when an existing byte was removed', () => {
		expect( check( OLD, OLD.replace( '300px', '30px' ) ).errors[ 0 ] ).toMatch( /the old file is not kept/ );
	} );

	const RULES = '@supports(zoom:1){.notification-item.nx-card-size-compact>.notificationx-inner{zoom:var(--nx-scale)}}';

	it( 'passes the rules before the old file\'s trailing newline, also with --at-end', () => {
		const { errors, stats } = check( OLD + '\n', OLD + RULES + '\n', { atEnd: true } );
		expect( errors ).toEqual( [] );
		expect( stats ).toMatchObject( { atEnd: true, rules: 1 } );
	} );

	it( 'passes rules inserted between two top-level rules (crossSite.css), but not with --at-end', () => {
		const after = '.nx-admin-modal-head{display:flex}';
		const { errors, stats } = check( OLD + '\n' + after, OLD + RULES + '\n' + after );
		expect( errors ).toEqual( [] );
		expect( stats ).toMatchObject( { atEnd: false, at: OLD.length, rules: 1 } );
		expect( check( OLD + '\n' + after, OLD + RULES + '\n' + after, { atEnd: true } ).errors[ 0 ] ).toMatch( /not at the end/ );
	} );

	it( 'fails rules inserted inside a block', () => {
		const old = '@media (min-width:1px){.a{color:red}.b{color:blue}}';
		const inserted = old.replace( '.b{', RULES + '.b{' );
		expect( check( old, inserted ).errors[ 0 ] ).toMatch( /inserted inside a block/ );
	} );

	it( 'fails a rule appended outside @supports', () => {
		expect( ok( '.notification-item.nx-card-size-compact{color:red}' )[ 0 ] ).toMatch( /outside @supports \(zoom: 1\)/ );
	} );

	it( 'fails another @supports condition', () => {
		expect( ok( '@supports (display:grid){.notification-item.nx-card-size-compact{color:red}}' )[ 0 ] ).toMatch( /outside @supports/ );
	} );

	it( 'fails an unscoped selector, also in a selector list and inside @media', () => {
		expect( ok( '@supports (zoom:1){.notification-item{zoom:.8}}' )[ 0 ] ).toMatch( /selector "\.notification-item" does not start/ );
		expect( ok( '@supports (zoom:1){.notification-item.nx-card-size-compact,.nx-container{zoom:.8}}' )[ 0 ] ).toMatch( /"\.nx-container"/ );
		expect( ok( '@supports (zoom:1){@media (min-width:575px){.notificationx-inner{zoom:1.2}}}' )[ 0 ] ).toMatch( /@media \(min-width:575px\): selector/ );
	} );

	it( 'fails other at-rules and statements', () => {
		expect( ok( '@supports (zoom:1){@keyframes x{0%{opacity:0}}}' )[ 0 ] ).toMatch( /unexpected at-rule "@keyframes x"/ );
		expect( ok( '@import url(x.css);' )[ 0 ] ).toMatch( /statement outside/ );
	} );

	it( 'is not fooled by braces in strings or comments', () => {
		expect( ok( '@supports (zoom:1){/* } */.notification-item.nx-card-size-compact::after{content:"}"}}' ) ).toEqual( [] );
	} );

	it( 'fails unbalanced CSS', () => {
		expect( ok( '@supports (zoom:1){.notification-item.nx-card-size-compact{color:red}' )[ 0 ] ).toMatch( /does not parse/ );
	} );

	it( 'splits selector lists only on top-level commas', () => {
		expect( splitSelectors( '.a:not(.b,.c), .d[data-x="1,2"]' ) ).toEqual( [ '.a:not(.b,.c)', '.d[data-x="1,2"]' ] );
	} );
} );
