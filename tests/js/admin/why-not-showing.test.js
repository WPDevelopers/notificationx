/**
 * "Why isn't it showing?" modal (nxdev/notificationx/admin/WhyNotShowing/).
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { act } from 'react-dom/test-utils';
import { closedHere, cookieNames, groupReasons, verdict } from '../../../nxdev/notificationx/admin/WhyNotShowing/logic';
import WhyNotShowingModal from '../../../nxdev/notificationx/admin/WhyNotShowing/WhyNotShowingModal';

const storage = ( keys ) => ( { getItem: ( k ) => ( keys.includes( k ) ? '1' : null ) } );
const REASONS = [
	{ key: 'enabled', level: 'blocked', title: 'It’s switched off', detail: 'Turn it on.', fix: { label: 'Open disabled notifications', url: '/wp-admin/admin.php?page=nx-admin&status=disabled' } },
	{ key: 'pages', level: 'limited', title: 'It shows only on selected pages', detail: 'Only on: Front Page.', fix: { label: 'Change “Show On”', url: '/wp-admin/admin.php?page=nx-edit&id=7&tab=display_tab' } },
	{ key: 'cache', level: 'note', title: 'A page cache is on', detail: '', fix: null },
	{ key: 'devices', level: 'ok', title: 'Shows on desktop, tablet and mobile', detail: '', fix: null },
];
const BROWSER = { cookies: [ 'notificationx_7', 'nx_bar_7' ], local: [ 'notificationx_7' ], session: [ 'notificationx_7', 'notificationx_popup_7' ] };

describe( 'logic', () => {
	test( 'groups by level and keeps the order', () => {
		const groups = groupReasons( [ ...REASONS, { key: 'x', level: 'nonsense', title: 'X' }, null ] );
		expect( groups.blocked.map( ( r ) => r.key ) ).toEqual( [ 'enabled' ] );
		expect( groups.limited.map( ( r ) => r.key ) ).toEqual( [ 'pages' ] );
		expect( groups.note.map( ( r ) => r.key ) ).toEqual( [ 'cache' ] );
		expect( groups.ok.map( ( r ) => r.key ) ).toEqual( [ 'devices' ] );
		expect( groupReasons( undefined ).blocked ).toEqual( [] );
	} );

	test( 'reads cookie names', () => {
		expect( cookieNames( 'a=1; notificationx_7=true;  b = 2' ) ).toEqual( [ 'a', 'notificationx_7', 'b' ] );
		expect( cookieNames( '' ) ).toEqual( [] );
	} );

	test( 'finds this notification’s close markers only', () => {
		expect( closedHere( BROWSER, { cookie: 'notificationx_70=true; notificationx_7=true', local: storage( [] ), session: storage( [ 'notificationx_popup_7' ] ) } ) ).toEqual( [
			'cookie:notificationx_7',
			'sessionStorage:notificationx_popup_7',
		] );
		expect( closedHere( BROWSER, { cookie: 'notificationx_70=true', local: storage( [ 'notificationx_70' ] ), session: null } ) ).toEqual( [] );
		const throwing = { getItem: () => { throw new Error( 'blocked' ); } };
		expect( closedHere( BROWSER, { cookie: '', local: throwing, session: throwing } ) ).toEqual( [] );
	} );

	test( 'verdict', () => {
		expect( verdict( groupReasons( REASONS ) ) ).toBe( 'blocked' );
		expect( verdict( groupReasons( REASONS.slice( 1 ) ) ) ).toBe( 'limited' );
		expect( verdict( groupReasons( REASONS.slice( 2 ) ) ) ).toBe( 'ok' );
		expect( verdict( groupReasons( REASONS.slice( 2 ) ), true ) ).toBe( 'limited' );
	} );
} );

describe( 'modal', () => {
	let container;
	beforeEach( () => {
		container = document.createElement( 'div' );
		container.id = 'notificationx';
		document.body.appendChild( container );
	} );
	afterEach( () => {
		ReactDOM.unmountComponentAtNode( container );
		container.remove();
		document.body.innerHTML = '';
	} );

	const open = async ( response, env = { cookie: '', local: storage( [] ), session: storage( [] ) } ) => {
		const fetcher = jest.fn( () => Promise.resolve( response ) );
		const onClose = jest.fn();
		await act( async () => {
			ReactDOM.render( <WhyNotShowingModal id={ 7 } title="Spring sale" fetcher={ fetcher } env={ env } onClose={ onClose } />, container );
		} );
		return { fetcher, onClose, modal: document.querySelector( '.nx-wns-modal' ) };
	};

	test( 'asks the server for this notification and lists the reasons by group', async () => {
		const { fetcher, modal } = await open( { id: 7, title: 'Spring sale', reasons: REASONS, browser: BROWSER } );
		expect( fetcher ).toHaveBeenCalledWith( 'why-not-showing/7' );
		expect( modal.getAttribute( 'role' ) ).toBe( 'dialog' );
		expect( modal.querySelector( 'h2' ).textContent ).toBe( 'Why isn’t it showing?' );
		expect( modal.querySelector( '.nx-wns-subtitle' ).textContent ).toBe( '#7 · Spring sale' );
		expect( modal.querySelector( '.nx-wns-verdict' ).textContent ).toBe( '1 thing stops it from showing' );
		const titles = [ ...modal.querySelectorAll( '.nx-wns-group h3, .nx-wns-group summary' ) ].map( ( n ) => n.textContent );
		expect( titles ).toEqual( [ 'Stops it from showing', 'Shows only sometimes or somewhere', 'Good to know', 'Checked and fine 1' ] );
		const fix = modal.querySelector( '[data-key="pages"] .nx-wns-reason__fix' );
		expect( fix.getAttribute( 'href' ) ).toBe( '/wp-admin/admin.php?page=nx-edit&id=7&tab=display_tab' );
		expect( fix.textContent ).toBe( 'Change “Show On” →' );
		expect( modal.querySelector( '[data-key="cache"] .nx-wns-reason__fix' ) ).toBeNull();
		expect( modal.querySelector( '[data-key="browser"]' ) ).toBeNull();
	} );

	test( 'adds “You closed it in this browser” from this browser’s markers', async () => {
		const { modal } = await open(
			{ id: 7, title: 'Spring sale', reasons: REASONS.slice( 2 ), browser: BROWSER },
			{ cookie: 'notificationx_7=true', local: storage( [] ), session: storage( [] ) }
		);
		const browser = modal.querySelector( '[data-key="browser"]' );
		expect( browser.querySelector( '.nx-wns-reason__title' ).textContent ).toBe( 'You closed it in this browser' );
		expect( browser.closest( '.nx-wns-group--limited' ) ).not.toBeNull();
		expect( modal.querySelector( '.nx-wns-verdict' ).className ).toContain( 'nx-wns-verdict--limited' );
	} );

	test( 'nothing wrong', async () => {
		const { modal } = await open( { id: 7, title: '', reasons: [ REASONS[ 3 ] ], browser: BROWSER } );
		expect( modal.querySelector( '.nx-wns-verdict' ).textContent ).toBe( 'Nothing stops it from showing' );
		expect( modal.querySelector( '.nx-wns-subtitle' ).textContent ).toBe( '#7 · Spring sale' );
	} );

	test( 'a failed request says so', async () => {
		const { modal } = await open( undefined );
		expect( modal.querySelector( '.nx-wns-error' ).getAttribute( 'role' ) ).toBe( 'alert' );
		expect( modal.querySelector( '.nx-wns-verdict' ) ).toBeNull();
	} );

	test( 'closes from the × and the Close button', async () => {
		const { onClose, modal } = await open( { id: 7, title: 'x', reasons: [], browser: {} } );
		act( () => modal.querySelector( '.nx-wns-close' ).click() );
		act( () => modal.querySelector( '.nx-wns-button' ).click() );
		expect( onClose ).toHaveBeenCalledTimes( 2 );
	} );
} );
