var ExtractTextPlugin = require('extract-text-webpack-plugin');
var CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
    mode: process.env.NODE_ENV || "development",
    entry: {
        admin: './src/js/admin.js',
        install: './src/js/install.js',
        shop: './src/js/shop.js'
    },
    output: {
        filename: 'js/[name].js',
        publicPath: "/",
        pathinfo: false,
        path: __dirname + "/build"
    },
    devtool: 'source-map',
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader'
                ]
            },
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: true
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: true
                            }
                        }
                    ]
                })
            }
        ]
    },
    plugins: [
        new CopyWebpackPlugin([
            {from: './src/oldjs/', to: './oldjs/'},
            {from: './src/oldcss/', to: './oldcss/'}
        ]),
        new ExtractTextPlugin({
            filename: 'css/[name].css'
        })
    ]
};
