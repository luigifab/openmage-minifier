<?php
/**
 * Created S/23/07/2016
 * Updated D/06/10/2019
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

class Luigifab_Minifier_Model_Rewrite_Translate extends Mage_Core_Model_Translate {

	protected function _loadModuleTranslation($moduleName, $files, $forceReload = false) {

		// partager les traductions lorsque le mode développeur est activé
		$scope = (Mage::getStoreConfigFlag('minifier/general/translations_share') && Mage::getIsDeveloperMode()) ? false : $moduleName;

		foreach ($files as $file) {
			$file = $this->_getModuleFilePath($moduleName, $file);
			$this->_addData($this->_getFileData($file), $scope, $forceReload);
		}

		return $this;
	}

	protected function _loadThemeTranslation($forceReload = false) {

		// prioriser les traductions du thème
		if (Mage::getStoreConfigFlag('minifier/general/translations_overwrite')) {
			$data = $this->_getFileData(Mage::getDesign()->getLocaleFileName('translate.csv'));
			foreach ($data as $key => $value)
				$this->_data['Zz_Zz'.self::SCOPE_SEPARATOR.$key] = $value;
			return $this;
		}

		return parent::_loadThemeTranslation($forceReload);
	}

	protected function _getTranslatedString($text, $code) {

		// prioriser les traductions du thème
		if (Mage::getStoreConfigFlag('minifier/general/translations_overwrite')) {
			$overload = 'Zz_Zz'.self::SCOPE_SEPARATOR.$text;
			if (array_key_exists($overload, $this->_data))
				return $this->_data[$overload];
		}

		return parent::_getTranslatedString($text, $code);
	}
}