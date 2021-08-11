module.exports = {
    presets: [
        "@babel/preset-env",
        "@babel/preset-react",
        "@babel/preset-typescript",
        "@wordpress/babel-preset-default",
    ],
    plugins: [
        "@babel/plugin-transform-runtime",
        "@babel/plugin-proposal-class-properties",
    ],
};
