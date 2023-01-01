<?php
/**
 * Created M/01/09/2015
 * Updated D/06/10/2019
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

class Luigifab_Minifier_Block_Adminhtml_Rewrite_Head extends Luigifab_Minifier_Block_Rewrite_Head {

	//protected function _construct() {
	//	$this->setModuleName('Mage_Adminhtml');
	//}

	public function getFormKey() {
		return Mage::getSingleton('core/session')->getFormKey();
	}

	protected function _getUrlModelClass() {
		return 'adminhtml/url';
	}
}