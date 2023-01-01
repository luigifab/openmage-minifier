<?php
/**
 * Created J/12/11/2020
 * Updated J/17/11/2022
 *
 * Copyright 2011-2023 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * Copyright 2022-2023 | Fabrice Creuzot <fabrice~cellublue~com>
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

class Luigifab_Minifier_Model_Rewrite_Package extends Mage_Core_Model_Design_Package {

	protected $_configKey;

	public function __construct() {

		parent::__construct();

		$value = Mage::getStoreConfig('minifier/cssjs/solution');
		if ($value == 2) {
			// by file
			$this->_configKey = true;
		}
		else if ($value == 1) {
			// global
			$this->_configKey = '-'.Mage::getStoreConfig('minifier/cssjs/value');
			if (Mage::getIsDeveloperMode())
				$this->_configKey = '-00'.date('YmdHis');
		}
		else {
			// disabled
			$this->_configKey = false;
		}
	}

	public function getFinalUrl($fileName, $url) {

		// no key
		if (empty($this->_configKey))
			return str_replace('-zzyyxx', '', $url);

		// global key
		if ($this->_configKey !== true)
			return str_replace('-zzyyxx', $this->_configKey, $url);

		// by file
		if (empty($fileName))
			return str_replace('-zzyyxx', '', $url);

		$key = (is_file($fileName) ? date('-YmdHis', filemtime($fileName)) : '-00000000000000');
		return str_replace([basename($url), '-zzyyxx'], [preg_replace('#\.#', $key.'.', basename($url), 1), ''], $url);
	}

	public function getSkinUrl($file = null, array $params = []) {

		if (($file == 'favicon.ico') && !empty($conf = Mage::getStoreConfig('minifier/general/favicon'))) {
			// 1 frontend, 2 frontend + backend
			if (($conf == 2) || (!Mage::app()->getStore()->isAdmin() && ($conf == 1)))
				return Mage::getBaseUrl('web').'favicon.ico';
		}

		// prevent reading files outside of the proper directory while still allowing symlinked files
		if (!empty($file) && str_contains($file, '..')) {
			Mage::log(sprintf('Invalid path requested: %s (params: %s)', $file, json_encode($params)), Zend_Log::ERR);
			throw new RuntimeException(sprintf('Invalid path requested: %s', $file));
		}

		if (empty($params['_type']))
			$params['_type'] = 'skin';
		if (empty($params['_default']))
			$params['_default'] = false;

		$this->updateParamDefaults($params);
		if (!empty($file)) {
			$fileName = $this->_fallback(
				$file,
				$params,
				$this->_fallback->getFallbackScheme($params['_area'], $params['_package'], $params['_theme'])
			);
			$params['_package'] .= '-zzyyxx';
			$result = $this->getFinalUrl($fileName, $this->getSkinBaseUrl($params).$file);
		}
		else {
			$params['_package'] .= '-zzyyxx';
			$result = $this->getFinalUrl(null, $this->getSkinBaseUrl($params));
		}

		return $result;
	}

	public function getJsUrl($file = null) {
		return $this->getFinalUrl(Mage::getBaseDir().'/js/'.$file, trim(Mage::getBaseUrl('js'), '/').'-zzyyxx/'.$file);
	}
}