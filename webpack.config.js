const path = require('path');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const R = require('ramda');

const webPackModule = (production = true, langFile = '') => {
	return {
		rules: [
			{
				loader: 'babel-loader',
				test: /\.js$/,
				exclude: /node_modules\/(?!(wpml-common-js-source|use-global-hook)\/).*/,
				query: {
					presets: ['es2015'],
					plugins: [
						[
							'@wordpress/babel-plugin-makepot',
							{
								'output': langFile ? `./locale/jed/wpml-${langFile}-ui/wpml-${langFile}-ui.pot` : 'null.pot',
							}
						],
					],
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

// nullModule is a workaround to allow for creation of translation pot files per module
const nullModule = {
	entry: './src/js/null.js',
	output: {
		filename: 'null.js',
		path: path.resolve(__dirname, 'dist'),
	},
};


const getModuleDefinition = (moduleName, isProduction, langFile) => ({
	entry: ['regenerator-runtime/runtime', `./src/js/${moduleName}/app.js`],
	output: {
		path: path.join(__dirname, 'dist'),
		filename: path.join('js', moduleName, 'app.js'),
		sourceMapFilename: path.join('js', moduleName, 'app.js.map'),
	},
	module: webPackModule(!isProduction, langFile ? moduleName : null),
	devtool: isProduction ? '' : 'inline-source-map',
	plugins: [
		new ExtractTextPlugin(path.join('css', moduleName, 'styles.css')),
	],
	resolve: {
		extensions: ['*', '.ts', '.tsx', '.mjs', '.js', '.jsx'],
		alias: {
			// Disable loading all icons to reduce bundle size
			'@ant-design/icons/lib/dist$': path.resolve(__dirname, './src/js/icons.js')
		}
	},
});

const isProduction = ( env ) => (env === 'production') || env.production !== "false";

const createModule = R.curryN(2, (moduleName, env) => {
	return !env.module || env.module === moduleName ?
		getModuleDefinition( moduleName, isProduction(env), env.module) :
		nullModule;
} );

module.exports = [
	createModule('multicurrencyShippingAdmin'),
	createModule('multicurrencyOptions'),
];
