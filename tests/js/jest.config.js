/**
 * Jest config for the JS unit tests in tests/js/.
 *
 * Run with: npm run test:js
 */
module.exports = {
	preset: '@wordpress/jest-preset-default',
	rootDir: '../..',
	roots: [ '<rootDir>/tests/js' ],
	testMatch: [ '<rootDir>/tests/js/**/*.test.[jt]s?(x)' ],
	testPathIgnorePatterns: [ '/node_modules/', '/vendor/', '/nxbuild/' ],
	// @wordpress/i18n ships a nested ESM-only `memize`; let babel-jest compile it.
	transformIgnorePatterns: [
		'/node_modules/(?!(memize|@wordpress/i18n/node_modules/memize)/)',
	],
	// Every test starts with no WordPress hooks registry (see setup.js).
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
};
