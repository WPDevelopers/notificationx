const webpack = require('webpack');
const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const MiniCSSExtractPlugin = require("mini-css-extract-plugin");
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const isProduction = process.env.NODE_ENV === "production";

const plugins = defaultConfig.plugins.filter(
    (plugin) =>
        plugin.constructor.name != "MiniCssExtractPlugin" &&
        // plugin.constructor.name != "DependencyExtractionWebpackPlugin" &&
        plugin.constructor.name != "CleanWebpackPlugin"
);

const config = {
    ...defaultConfig,
    entry: {
        frontend: path.resolve(
            __dirname,
            "nxdev/notificationx/frontend/index.tsx"
        ),
        crossSite: path.resolve(
            __dirname,
            "nxdev/notificationx/frontend/crossSite.tsx"
        ),
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
        filename: "public/js/[name].js",
        path: path.resolve(process.cwd(), isProduction ? "assets" : "nxbuild"),
        chunkFilename: (pathData) => {
            return `public/${pathData.chunk.name || pathData.chunk.id}.js`;
        },
    },
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: [
                "public/css/frontend.css",
                "public/css/frontend.css.map",
                "public/js/frontend.js",
                "public/js/frontend.js.map",
                "public/css/crossSite.css",
                "public/css/crossSite.css.map",
                "public/js/crossSite.js",
                "public/js/crossSite.js.map",
            ],
        }),
        new MiniCSSExtractPlugin({
            filename: ({ chunk }) => {
                // if (!isProduction) {
                //     return `${chunk.name}.css`;
                // }
                return chunk.name == "admin"
                    ? `admin/css/${chunk.name}.css`
                    : `public/css/${chunk.name}.css`;
            },
        }),
        // new DependencyExtractionWebpackPlugin( {
        //     injectPolyfill: true,
        //     requestToExternal( request ) {
        //         if(request == '@wordpress/dom-ready') //
        //             return false;
        //         return undefined;
        //     },
        // } ),
        new webpack.IgnorePlugin({
            resourceRegExp: /^\.\/locale$/,
            contextRegExp: /moment$/,
        }),
        ...plugins,
    ],
    optimization: {
        ...defaultConfig.optimization,
        splitChunks: {
            ...defaultConfig.optimization.splitChunks,
            // chunks(chunk) {
            //   // exclude `my-excluded-chunk`
            //   console.log(chunk.name);
            //   return chunk.name?.includes('locale/');
            // },
            cacheGroups: {
                ...defaultConfig.optimization.splitChunks.cacheGroups,
                // vendors: false,
                // moment: {
                //     test: /node_modules\/moment\/moment.js/,
                //     chunks: 'all',
                //     enforce: false,
                //     name: "js/moment",
                // },
                locale: {
                    test: /node_modules\/moment\/locale\/.*/,
                    chunks: 'all',
                    enforce: true,
                    name(module, chunks, cacheGroupKey) {
                      const moduleFileName = module
                        .identifier()
                        .split('/')
                        .reduceRight((item) => item);
                        // console.log(moduleFileName);
                    //   console.log('name', module.identifier());
                      return 'locale/' + moduleFileName.replace('.js', '');
                    },
                },
            },
        },
    },
};

module.exports = config;
