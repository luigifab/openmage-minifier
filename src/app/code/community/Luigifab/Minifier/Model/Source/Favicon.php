<?php
/**
 * Created V/20/05/2022
 * Updated S/11/11/2023
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

class Luigifab_Minifier_Model_Source_Favicon {

	protected $_options;

	public function toOptionArray() {

		if (empty($this->_options)) {
			$helper = Mage::helper('minifier');
			$this->_options = [
				['value' => 0, 'label' => Mage::helper('adminhtml')->__('No')],
				['value' => 1, 'label' => $helper->__('Yes - frontend only')],
				['value' => 2, 'label' => $helper->__('Yes - frontend and backend')],
			];
		}

		return $this->_options;
	}
}