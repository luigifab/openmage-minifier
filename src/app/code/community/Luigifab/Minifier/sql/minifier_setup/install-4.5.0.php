<?php
/**
 * Created J/03/11/2016
 * Updated V/03/02/2023
 *
 * Copyright 2011-2023 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

// prevent multiple execution
$lock = Mage::getModel('index/process')->setId('minifier_setup');
if ($lock->isLocked())
	Mage::throwException('Please wait, install is already in progress...');

$lock->lockAndBlock();
$this->startSetup();

// ignore user abort and time limit
ignore_user_abort(true);
set_time_limit(0);

try {
	$this->run('
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "minifier/%";
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "compressor/%";
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "crontab/jobs/minifier_%";
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "crontab/jobs/compressor_%";

		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "design/head/shortcut_icon";
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "dev/css/%";
		DELETE FROM '.$this->getTable('core_config_data').' WHERE path LIKE "dev/js/%";

		DROP TABLE IF EXISTS '.$this->getTable('luigifab_minifier_kraken').';
	');

	$this->setConfigData('dev/css/merge_css_files', '0');
	$this->setConfigData('dev/js/merge_files', '0');

	$variable = Mage::getModel('core/variable')->loadByCode('cachekey');
	if (!empty($variable->getId()))
		$variable->delete();
}
catch (Throwable $t) {
	$lock->unlock();
	Mage::throwException($t);
}

$this->endSetup();
$lock->unlock();