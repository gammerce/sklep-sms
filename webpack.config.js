const ExtractTextPlugin = require('extract-text-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const fs = require("fs");

const environment = process.env.NODE_ENV || "development";
const isProduction = environment === "production";

const getFiles = (dirPath) =>
    fs.readdirSync(dirPath)
        .map(file => {
            if (fs.statSync(`${dirPath}/${file}`).isDirectory()) {
                return getFiles(dirPath + "/" + file);
            }

            return [`${dirPath}/${file}`];
        })
        .flat()
        .filter(path => path.match(/\.(ts|js)$/));


const entryPaths = [
    ...getFiles("./src/js/admin/pages"),
    ...getFiles("./src/js/shop/pages"),
];

const entries = Object.fromEntries(entryPaths.map(path => [path.replace(/^.*\/src\/js/, "").replace(/\.(js|ts)$/, ""), path]));

module.exports = {
    mode: environment,
    entry: {
        admin: './src/js/admin/admin.js',
        install: './src/js/setup/install.js',
        update: './src/js/setup/update.js',
        shop: './src/js/shop/shop.js',
        ...entries
    },
    output: {
        filename: "js/[name].js",
        // filename: (chunkData) => {
        //     return chunkData.chunk.entryModule.resource.replace(/^.*\/src/, "");
        // },
        publicPath: "../",
        pathinfo: false,
        path: __dirname + "/build"
    },
    devtool: isProduction ? undefined : 'source-map',
    module: {
        rules: [
            {
                test: /\.(ts|js)$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
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
    optimization: {
        removeAvailableModules: false,
        removeEmptyChunks: false,
        splitChunks: {
            chunks: 'all',
            maxInitialRequests: 2,
            // cacheGroups: {
            //     defaultVendors: {
            //         filename: 'vendors.js'
            //     }
            // }
        },
    },
    plugins: [
        new CopyWebpackPlugin([
            {from: './src/images/', to: './images/'},
            {from: './src/stylesheets/shop/pages/', to: './css/shop/pages/'},
            {from: './src/stylesheets/general/services/', to: './css/general/services/'},
            {from: './src/stylesheets/shop/long_desc.css', to: './css/shop/long_desc.css'},
        ]),
        new ExtractTextPlugin({
            filename: 'css/[name].css'
        })
    ]
};
