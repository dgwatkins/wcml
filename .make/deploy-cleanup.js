#!/usr/bin/env node

const path = require('path');
const del  = require('del');

const argv = require('yargs')
	.usage('Usage: [options]')
	.option('t', {
		description:  'The target path to cleanup',
		alias:        'target',
		demandOption: true,
		string:       true
	})
	.option('d', {
		description: 'Debug',
		alias:       'debug',
		boolean:     true,
		default:     false
	})
	.argv;

const targetPath = path.normalize(argv.target);

cleanupTarget();

function cleanupTarget() {

	if (argv.target) {
		setTestPatterns();
	}

	if (process.env.OTGS_CI_DEPLOY_DEL) {
		const currentDirectory = process.cwd();
		process.chdir(targetPath);
		const del_patterns = JSON.parse(process.env.OTGS_CI_DEPLOY_DEL);

		return del(del_patterns, {dot: true})
			.then(paths => {
				process.chdir(currentDirectory);
				return console.info(`Deleted ${paths.length} files`);
			})
			.catch(error => console.error(error));
	} else {
		throw new Error('An environment variable named OTGS_CI_DEPLOY_DEL and containing a JSON array of Glob patterns is required.');
	}
}

function setTestPatterns() {
	const testPatterns = [
		"**/*/*.css.map",
		"**/*/*.js.map",
		"**/*/*.scss",
		"*.js",
		"*.json",
		"*.sh",
		"*.xml",
		"*.xml.dist",
		".*",
		".babelrc",
		".browserslistrc",
		".eslintrc",
		".githooks",
		".make",
		"build",
		"composer.*",
		"Makefile",
		"node_modules",
		"node_modules",
		"package.json",
		"postcss.config.json",
		"README.md",
		"res/scss",
		"src",
		"tests",
		"vendor/**/*/*.json",
		"vendor/**/*/*.md",
		"vendor/**/*/*.txt",
		"vendor/**/*/*.xml",
		"vendor/**/*/*.xml.dist",
		"vendor/**/*/.*",
		"vendor/**/*/composer.*",
		"vendor/**/*/test/**",
		"vendor/**/*/tests/**",
		"vendor/bin",
		"vendor/wimg",
		"vendor/xrstf",
		"webpack.config.js",
		"yarn-error.log",
		"yarn.lock",
		"!changelog.md",
		"!license.txt",
		"!readme.txt",
		"!vendor/**/*/lib/test*",
		"!vendor/**/*/src/test*",
		"!vendor/otgs/installer/*.xml",
		"!wpml-config.xml",
		"!wpml-dependencies.json",
	];

	process.env.OTGS_CI_DEPLOY_DEL = JSON.stringify(testPatterns);
}
