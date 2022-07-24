const webpack = require('webpack');
const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const MiniCSSExtractPlugin = require("mini-css-extract-plugin");

const isProduction = process.env.NODE_ENV === "production";

const plugins = defaultConfig.plugins.filter(
    (plugin) =>
        plugin.constructor.name != "MiniCssExtractPlugin" &&
        plugin.constructor.name != "CleanWebpackPlugin"
);

const config = {
    ...defaultConfig,
    entry: {
        admin: path.resolve(__dirname, "nxdev/index.tsx"),
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.tsx?$/,
                use: "ts-loader",
                exclude: /node_modules/,
            },
            // {
            //     test: /\.(jpg|png|svg)$/,
            //     use: "url-loader",
            //     // type: "asset/source",
            //     dependency: { not: ['url'] },
            // },
            // {
            //     test: /\.(gif)$/,
            //     // use: "url-loader",
			// 	type: 'asset/resource',
            // },
        ],
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: [".tsx", ".ts", ".js", ".jsx"],
    },
    output: {
        ...defaultConfig.output,
        filename: "admin/js/[name].js",
        path: path.resolve(process.cwd(), isProduction ? "assets" : "nxbuild"),
    },
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: [
                "admin/css/admin.css",
                "admin/css/admin.css.map",
                "admin/js/admin.js",
                "admin/js/admin.js.map",
                "admin/js/admin.asset.php",
            ],
        }),
        new MiniCSSExtractPlugin({
            filename: `admin/css/admin.css`,
        }),
        ...plugins,
    ],
};

module.exports = config;
