const path = require('path');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

const webPackModule = (production = true) => {
	return {
		rules: [
			{
				loader: 'babel-loader',
				test: /\.js$/,
				exclude: /node_modules\/(?!(wpml-common-js-source|use-global-hook)\/).*/,
				query: {
					presets: ['es2015'],
				},
			},
			{
				test: /\.tsx?$/,
				loader: 'ts-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.s?css$/,
				use: ExtractTextPlugin.extract({
					fallback: 'style-loader',
					use: [
						{
							loader: 'css-loader',
							options: {
								sourceMap: !production,
							},
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: !production,
							},
						},
						{
							loader: 'postcss-loader',
						},
					],
				}),
			},
		],
	}
};

const multicurrencyShippingAdmin = (env) => {
	const isProduction = env === 'production';
	return {
		entry: ['./src/js/multicurrencyShippingAdmin/app.js'],
		output: {
			path: path.join(__dirname, 'dist'),
			filename: path.join('js', 'multicurrencyShippingAdmin', 'app.js'),
			sourceMapFilename: path.join('js', 'adminUiWc', 'app.js.map'),
		},
		module: webPackModule(!isProduction),
		devtool: isProduction ? '' : 'inline-source-map',
		resolve: {
			extensions: ['*', '.ts', '.tsx', '.mjs', '.js', '.jsx']
		},
	};
};

const multicurrencyOptions = (env) => {
	const isProduction = env === 'production';
	return {
		entry: ['./src/js/multicurrencyOptions/app.js'],
		output: {
			path: path.join(__dirname, 'dist'),
			filename: path.join('js', 'multicurrencyOptions', 'app.js'),
			sourceMapFilename: path.join('js', 'multicurrencyOptions', 'app.js.map'),
		},
		module: webPackModule(!isProduction),
		devtool: isProduction ? '' : 'inline-source-map',
		resolve: {
			extensions: ['*', '.ts', '.tsx', '.mjs', '.js', '.jsx']
		},
	};
};

module.exports = [
	multicurrencyShippingAdmin,
	multicurrencyOptions,
];
