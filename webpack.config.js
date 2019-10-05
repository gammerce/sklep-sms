var ExtractTextPlugin = require('extract-text-webpack-plugin');
var CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
    mode: process.env.NODE_ENV || "development",
    entry: {
        admin: './src/js/admin.js',
        install: './src/js/full.js',
        update: './src/js/update.js',
        shop: './src/js/shop.js'
    },
    output: {
        filename: 'js/[name].js',
        publicPath: "/build/",
        pathinfo: false,
        path: __dirname + "/build"
    },
    devtool: 'source-map',
    module: {
        rules: [
            {
                test: /\.(png|jpg|svg|gif)$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]',
                    outputPath: 'images/'
                }
            },
            {
                test: /\.(otf|eot|woff2?|ttf)$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]',
                    outputPath: 'fonts/'
                }
            },
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
            {from: './src/images/', to: './images/'},
            {from: './src/js_old/', to: './js_old/'},
            {from: './src/stylesheets_old/', to: './stylesheets_old/'}
        ]),
        new ExtractTextPlugin({
            filename: 'css/[name].css'
        })
    ]
};
