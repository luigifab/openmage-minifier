<?php
/**
 * Created L/16/07/2018
 * Updated V/11/02/2022
 *
 * Copyright 2011-2022 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Minifier_Helper_Rewrite_Js extends Mage_Core_Helper_Js {

	public function getJsUrl($file) {
		return Mage::getDesign()->getJsUrl($file);
	}

	public function getJsSkinUrl($file) {
		return Mage::getDesign()->getSkinUrl($file);
	}

	public function getTranslatorScriptContent() {
		return 'var Translator = new Translate('.$this->getTranslateJson().');';
	}
}