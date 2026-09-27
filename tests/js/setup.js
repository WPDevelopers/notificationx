/**
 * Every test starts with no WordPress hooks registry. Tests that need one call
 * installHooks() from ./helpers.
 */
beforeEach( () => {
	delete window.wp;
	delete window.nxFrontendRuntime;
} );
