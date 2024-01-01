<?php
/**
 * Created S/14/07/2018
 * Updated J/28/12/2023
 *
 * Copyright 2011-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://github.com/luigifab/openmage-minifier
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

chdir(dirname($argv[0], 7)); // root
error_reporting(E_ALL);
ini_set('display_errors', (PHP_VERSION_ID < 80100) ? '1' : 1);

if (PHP_SAPI != 'cli')
	exit(-1);
if (is_file('maintenance.flag') || is_file('upgrade.flag'))
	exit(0);
if (is_file('app/bootstrap.php'))
	require_once('app/bootstrap.php');

$action = empty($argv[1]) ? false : $argv[1]; // css ou js
$source = empty($argv[2]) ? false : $argv[2]; // fichier(s) source (fichier(s) existant séparés par des virgules)
$dest   = empty($argv[3]) ? false : $argv[3]; // fichier cache     (fichier inexistant)
$dev    = !empty($argv[4]);

if (!empty($action) && !empty($source) && !empty($dest)) {

	require_once('app/Mage.php');
	Mage::app('admin')->setUseSessionInUrl(false);
	Mage::setIsDeveloperMode($dev);

	try {
		// chdir pour le "sources" dans les fichiers map
		chdir(dirname($dest));

		if ($action == 'mergecss') {
			// @see https://github.com/jakubpawlowicz/clean-css-cli
			$program = Mage::getStoreConfig('minifier/cssjs/cleancss');
			if ((!empty($program) && is_executable($program)) || is_executable($program = '/usr/bin/cleancss')) {
				exec(sprintf(
					'%s --with-rebase --source-map --source-map-inline-sources -O1 "specialComments:off" --output %s %s',
					$program,
					escapeshellarg($dest),
					implode(' ', array_map('escapeshellarg', explode(',', $source)))
				));
				exit(0);
			}
			Mage::throwException('cleancss not found');
		}

		if (($action == 'mergejs') || (stripos($dest, 'virtual-') !== false)) { // not mb_stripos
			// @see https://github.com/mishoo/uglifyjs
			$program = Mage::getStoreConfig('minifier/cssjs/uglifyjs');
			if ((!empty($program) && is_executable($program)) || is_executable($program = '/usr/bin/uglifyjs')) {
				exec(sprintf(
					'%s --source-map "content=inline,includeSources,base=\'.\',url=\'%s\'" --output %s %s',
					$program,
					basename($dest).'.map',
					escapeshellarg($dest),
					implode(' ', array_map('escapeshellarg', explode(',', $source)))
				));
				exit(0);
			}
			Mage::throwException('uglifyjs not found');
		}

		if ($action == 'css') {
			// @see https://github.com/jakubpawlowicz/clean-css-cli
			$program = Mage::getStoreConfig('minifier/cssjs/cleancss');
			if ((!empty($program) && is_executable($program)) || is_executable($program = '/usr/bin/cleancss')) {
				exec(sprintf(
					'%s --with-rebase --source-map --source-map-inline-sources -O1 specialComments:off --output %s %s',
					$program,
					escapeshellarg($dest),
					escapeshellarg($source)
				));
				exit(0);
			}
			Mage::throwException('cleancss not found');
		}

		if ($action == 'js') {
			// @see https://github.com/mishoo/uglifyjs
			$program = Mage::getStoreConfig('minifier/cssjs/uglifyjs');
			if ((!empty($program) && is_executable($program)) || is_executable($program = '/usr/bin/uglifyjs')) {
				exec(sprintf(
					'%s --source-map "content=inline,includeSources,base=\'.\',url=\'%s\'" %s --compress --output %s %s',
					$program,
					'inline', //basename($dest).'.map',
					(mb_stripos($source, 'prototype.js') === false) ? '--mangle reserved=[\'$super\']' : '',
					escapeshellarg($dest),
					escapeshellarg($source)
				));
				exit(0);
			}
			Mage::throwException('uglifyjs not found');
		}

		Mage::throwException('action not specified');
	}
	catch (Throwable $t) {
		Mage::logException($t);
		echo trim($t->getMessage()),"\n";
	}
}

exit(-1);