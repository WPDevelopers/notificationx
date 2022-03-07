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
        blocks: path.resolve(
            __dirname,
            "blocks/notificationx/index.jsx"
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
        ],
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: [".tsx", ".ts", ".js", ".jsx"],
    },
    output: {
        ...defaultConfig.output,
        filename: "index.js",
        path: path.resolve(process.cwd(), "blocks/notificationx/"),
    },
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: [
                "blocks/notificationx/index.js",
                "blocks/notificationx/index.asset.php",
            ],
        }),
        ...plugins,
    ]
};

module.exports = config;
