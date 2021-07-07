<?php
/**
 * Created D/15/11/2020
 * Updated S/21/11/2020
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

class Luigifab_Minifier_Block_Adminhtml_Config_Debug extends Mage_Adminhtml_Block_System_Config_Form_Field {

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

		$storeId = Mage::app()->getDefaultStoreView()->getId();
		$passwd  = Mage::getStoreConfig('minifier/cssjs/debug_password');
		$element->setValue(sprintf('<a href="%s">debug front</a> / <a href="%s">debug back</a>',
			preg_replace('#/key/[^/]+/#', '/', $this->getUrl('minifier/debug/index', ['pass' => $passwd, '_store' => $storeId])),
			preg_replace('#/key/[^/]+/#', '/', $this->getUrl('*/minifier_debug/index', ['pass' => $passwd]))));

		return sprintf('<span lang="en" id="%s">%s</span>', $element->getHtmlId(), $element->getValue());
	}
}