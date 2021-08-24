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
        admin: path.resolve(__dirname, "src/index.tsx"),
        frontend: path.resolve(
            __dirname,
            "src/notificationx/frontend/index.tsx"
        ),
    },
    module: {
        ...defaultConfig.module,
        rules: [
            {
                test: /\.tsx?$/,
                use: "ts-loader",
                exclude: /node_modules/,
            },
            {
                test: /\.(jpg|png|gif|svg)$/,
                use: "url-loader",
            },
            ...defaultConfig.module.rules,
        ],
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: [".tsx", ".ts", ".js", ".jsx"],
    },
    output: {
        ...defaultConfig.output,
        filename: (pathData) => {
            // if (!isProduction) {
            //     return "[name].js";
            // }
            return pathData.chunk.name == "admin"
                ? "admin/js/[name].js"
                : "public/js/[name].js";
        },
        path: path.resolve(process.cwd(), isProduction ? "assets" : "build"),
    },
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: [
                "public/css/frontend.css",
                "public/css/frontend.css.map",
                "public/js/frontend.js",
                "public/js/frontend.js.map",
                "public/js/frontend.asset.php",

                "admin/css/admin.css",
                "admin/css/admin.css.map",
                "admin/js/admin.js",
                "admin/js/admin.js.map",
                "admin/js/admin.asset.php",
            ],
        }),
        new MiniCSSExtractPlugin({
            esModule: false,
            moduleFilename: (chunk) => {
                // if (!isProduction) {
                //     return `${chunk.name}.css`;
                // }
                return chunk.name == "admin"
                    ? `admin/css/${chunk.name}.css`
                    : `public/css/${chunk.name}.css`;
            },
        }),
        ...plugins,
    ],
};

module.exports = config;
