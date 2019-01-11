#!/usr/bin/env node

const fs   = require('fs-extra');
const path = require('path');

const argv = require('yargs')
	.usage('Usage: [options]')
	.option('t', {
		description: 'The target path to update',
		alias:       'target',
		default:     process.cwd(),
		string:      true
	})
	.option('r', {
		description:  'The version',
		alias:        'ref',
		demandOption: true,
		string:       true,
	})
	.option('d', {
		description: 'Debug',
		alias:       'debug',
		boolean:     true,
		default:     false
	})
	.argv;

const targetPath = path.normalize(argv.target);

updatePluginVersion();

function updatePluginVersion() {
	if (argv.target) {
		setTestPatterns();
	}

	const tag = argv.ref.trim();

	if (process.env.OTGS_CI_REPLACEMENTS) {
		const currentDirectory = process.cwd();
		process.chdir(targetPath);

		const mainPluginFile = getMainPluginFile();

		if (mainPluginFile) {
			const file         = mainPluginFile.file;
			const content      = mainPluginFile.content;
			let updatedContent = content;

			console.info('- Found "' + file + '": updating...');

			const replacement_patterns = JSON.parse(process.env.OTGS_CI_REPLACEMENTS);

			console.log('replacement_patterns',replacement_patterns);

			replacement_patterns.map((regex_args, index) => {

				const use     = regex_args.extractSemVer ? regex_args.extractSemVer : false;
				const tagName = use ? extractSemVer(tag) : tag;
				const tagSlug = tagName.trim().replace(/\./g, '-');

				process.stdout.write((index + 1) + ') Will search for "' + regex_args.searchPattern);
				process.stdout.write(' using "' + tagName + '" as a tag and "' + tagSlug + '" as a tag slug');
				process.stdout.write(' and replacing it with "' + regex_args.replacePattern + '"\n');

				const regExp   = new RegExp(regex_args.searchPattern, 'g');
				updatedContent = updatedContent.replace(regExp, regex_args.replacePattern)
					.replace(/{{tag-slug}}/g, tagSlug)
					.replace(/{{tag}}/g, tagName);
			});

			if (!argv.dryRun && updatedContent !== content) {
				fs.writeFileSync(file, updatedContent, {encoding: 'utf8'});
			}

			process.chdir(currentDirectory);
		}
	} else {
		console.info('A constant named OTGS_CI_REPLACEMENTS hasn\'t been set: skipping.');
	}
}

function extractSemVer(version) {
	const versionElements = version
		.trim()
		.replace(/-/g, '.')
		.replace(/_/g, '.')
		.replace(/\+/g, '.')
		.replace(/([^0-9.]+)/, '.$1.')
		.replace(/\.{2,}/g, '.')
		.split('.');

	console.log('versionElements', versionElements);

	const nakedElements = ['0', '0', '0'];

	versionElements
		.filter(element => !isNaN(element))
		.slice(0, 3)
		.map((element, index) => {
			nakedElements[index] = element;
		});

	return nakedElements.join('.');
}

function getMainPluginFile() {
	const files = fs.readdirSync(process.cwd());

	const phpFiles = files
		.filter(file => path.extname(file).toLowerCase() === '.php')
		.filter(file => {

			const content = fs.readFileSync(file, 'utf8')
				.replace(/[\t\n\r]/g, '')
				.trim();

			return content.indexOf('<?php') === 0
				&& content.indexOf('Plugin Name: ') > 0
				&& content.indexOf('Description: ') > 0;

		});

	if (phpFiles) {
		const file = phpFiles[0];
		return {file, content: fs.readFileSync(file, 'utf8')};
	}
	return null;
}

function setTestPatterns() {
	const testPatterns = [
		{
			"searchPattern": "(Version:\\s*)(\\d*.*)",
			"replacePattern": "$1{{tag}}"
		},
		{
			"searchPattern": "(WCML_VERSION\\',\\s*\\')(\\d*.*)(\\')",
			"replacePattern": "$1{{tag}}$3",
			"extractSemVer": true
		}
	];

	process.env.OTGS_CI_REPLACEMENTS = JSON.stringify(testPatterns);
}
