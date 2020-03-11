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
    './src/js/admin.js',
    './src/js/install.js',
    './src/js/update.js',
    './src/js/shop.js',
    ...getFiles("./src/js/static"),
];

const entries = Object.fromEntries(entryPaths.map(path => [path, path]));

module.exports = {
    mode: environment,
    entry: entries,
    output: {
        filename: (chunkData) => {
            return chunkData.chunk.entryModule.id.replace(/^\.\/src/, "");
        },
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
    plugins: [
        new CopyWebpackPlugin([
            {from: './src/images/', to: './images/'},
            // {from: './src/js/static/', to: './js/static/'},
            {from: './src/stylesheets/static/', to: './css/static/'}
        ]),
        new ExtractTextPlugin({
            filename: 'css/[name].css'
        })
    ]
};
