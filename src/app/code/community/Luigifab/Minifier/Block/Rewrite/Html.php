<?php
/**
 * Created S/20/06/2015
 * Updated S/31/08/2019
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

class Luigifab_Minifier_Block_Rewrite_Html extends Mage_Page_Block_Html {

	protected function _construct() {
		$this->setModuleName('Mage_Page');
	}

	protected function _afterToHtml($html) {
		return $this->helper('minifier')->afterToHtml(parent::_afterToHtml($html));
	}
}