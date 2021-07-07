<?php
/**
 * Created J/12/11/2020
 * Updated S/14/11/2020
 *
 * Copyright 2011-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/minifier
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

	public function getSkinBaseUrl(array $params = []) {

		$params['_type'] = 'skin';
		$this->updateParamDefaults($params);

		return Mage::getBaseUrl('skin', isset($params['_secure']) ? (bool) $params['_secure'] : null).
			$params['_area'].'/'.
			$params['_package'].Mage::helper('minifier')->getKeyForUrls().'/'.
			$params['_theme'].'/';
	}

	public function getSkinUrl($file = null, array $params = []) {

		if (($file == 'favicon.ico') && Mage::getStoreConfigFlag('minifier/general/favicon'))
			return Mage::getBaseUrl('web').'favicon.ico';

		return parent::getSkinUrl($file, $params);
	}

	public function getJsUrl($file = null) {
		return trim(Mage::getBaseUrl('js'), '/').Mage::helper('minifier')->getKeyForUrls().'/'.$file;
	}
}