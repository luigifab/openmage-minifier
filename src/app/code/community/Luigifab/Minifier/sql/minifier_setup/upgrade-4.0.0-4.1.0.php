<?php
/**
 * Created L/13/09/2021
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
	Mage::throwException('Please wait, upgrade is already in progress...');

$lock->lockAndBlock();
$this->startSetup();

// ignore user abort and time limit
ignore_user_abort(true);
set_time_limit(0);

try {
	$config = Mage::getModel('core/config_data')->load('minifier/html/enabled', 'path');
	if (!empty($config->getData('value'))) {
		$this->setConfigData('minifier/html/enabled_back', '1');
		$this->setConfigData('minifier/html/enabled_front', '1');
	}
	$config->delete();

	$config = Mage::getModel('core/config_data')->load('minifier/cssjs/enabled', 'path');
	if (!empty($config->getData('value'))) {
		$this->setConfigData('minifier/cssjs/enabled_back', '1');
		$this->setConfigData('minifier/cssjs/enabled_front', '1');
	}
	$config->delete();

	Mage::getConfig()->reinit();
}
catch (Throwable $t) {
	$lock->unlock();
	Mage::throwException($t);
}

$this->endSetup();
$lock->unlock();