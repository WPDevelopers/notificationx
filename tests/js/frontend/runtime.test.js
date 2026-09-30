import React from 'react';
import { exposeFrontendRuntime, FRONTEND_RUNTIME_VERSION } from '../../../nxdev/notificationx/frontend/core/runtime';
import useNotificationContext from '../../../nxdev/notificationx/frontend/core/NotificationProvider';
import { analyticsOnClick } from '../../../nxdev/notificationx/frontend/core/Analytics';

describe( 'window.nxFrontendRuntime', () => {
	it( 'exposes the React copy and helpers the bundle itself uses', () => {
		const runtime = exposeFrontendRuntime();
		expect( window.nxFrontendRuntime ).toBe( runtime );
		expect( runtime.version ).toBe( FRONTEND_RUNTIME_VERSION );
		expect( runtime.React ).toBe( React );
		expect( runtime.useNotificationContext ).toBe( useNotificationContext );
		expect( runtime.analyticsOnClick ).toBe( analyticsOnClick );
		expect( typeof runtime.recordAnalyticsClick ).toBe( 'function' );
		expect( typeof runtime.getPath ).toBe( 'function' );
	} );

	it( 'is frozen, so an add-on cannot swap React for everyone', () => {
		const runtime = exposeFrontendRuntime();
		expect( Object.isFrozen( runtime ) ).toBe( true );
	} );

	it( 'keeps the first runtime when the bundle loads twice', () => {
		const first = exposeFrontendRuntime();
		expect( exposeFrontendRuntime() ).toBe( first );
	} );
} );
