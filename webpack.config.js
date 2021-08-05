const MiniCssExtractPlugin = require("mini-css-extract-plugin");
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
        .filter(path => path.match(/\.(tsx?|jsx?)$/));


const entryPaths = [
    ...getFiles("./src/js/admin/pages"),
    ...getFiles("./src/js/shop/pages"),
];

const entries = Object.fromEntries(entryPaths.map(path => [path.replace(/^.*\/src\/js/, "").replace(/\.(jsx?|tsx?)$/, ""), path]));

module.exports = {
    mode: environment,
    entry: {
        shop_fusion: './src/stylesheets/shop/fusion.ts',
        admin: './src/js/admin/admin.ts',
        install: './src/js/setup/install.ts',
        update: './src/js/setup/update.ts',
        shop: './src/js/shop/shop.ts',
        ...entries
    },
    output: {
        filename: "js/[name].js",
        publicPath: "../",
        pathinfo: false,
        path: __dirname + "/build"
    },
    devtool: isProduction ? undefined : 'source-map',
    module: {
        rules: [
            {
                test: /\.(tsx?|jsx?)$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
            {
                test: /\.(png|jpg|svg|gif)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext][query]'
                },
            },
            {
                test: /\.(otf|eot|woff2?|ttf)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'fonts/[name][ext][query]'
                },
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
                use: [
                    MiniCssExtractPlugin.loader,
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
                ],
            }
        ]
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js', '.jsx'],
        symlinks: false,
    },
    optimization: {
        removeAvailableModules: false,
        removeEmptyChunks: false,
        splitChunks: {
            cacheGroups: {
                vendors: {
                    name: 'vendors',
                    chunks: 'initial',
                    minChunks: 2
                }
            }
        },
    },
    plugins: [
        new CopyWebpackPlugin({
            patterns: [
                {from: './src/images/', to: './images/'},
            ],
        }),
        new MiniCssExtractPlugin({
            filename: 'css/[name].css',
        }),
    ]
};
