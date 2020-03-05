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

const adminUiWc = (env) => {
	const isProduction = env === 'production';
	return {
		entry: ['regenerator-runtime/runtime', './src/js/adminUiWc/app.js'],
		output: {
			path: path.join(__dirname, 'dist'),
			filename: path.join('js', 'adminUiWc', 'app.js'),
			sourceMapFilename: path.join('js', 'adminUiWc', 'app.js.map'),
		},
		module: webPackModule(!isProduction),
		devtool: isProduction ? '' : 'inline-source-map',
		resolve: {
			extensions: ['*', '.ts', '.tsx', '.mjs', '.js', '.jsx']
		},
	};
};

module.exports = [
	adminUiWc,
];
