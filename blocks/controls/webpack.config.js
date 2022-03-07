const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { controls } = require("./src/controls/controls.json");

// let entries = Object.keys(controls).reduce((memo, controlName) => {
// 	return {
// 		...memo,
// 		[controlName]: {
// 			import: `./src/controls/${controlName}/index.js`,
// 			library: {
// 				name: ["EBControls", controls[controlName]],
// 				type: "window",
// 			},
// 		},
// 	};
// }, {});

let entries = {};

// FOR ALL CONTROLS
entries.index = {
	import: path.resolve(__dirname, "src/index.js"),
	library: {
		name: ["EBControls"],
		type: "window",
	},
};

module.exports = {
	...defaultConfig,
	entry: entries,
	output: {
		...defaultConfig.output,
		devtoolNamespace: "wp",
		filename: "[name].js",
		path: path.resolve(__dirname, "dist"),
	},
};
