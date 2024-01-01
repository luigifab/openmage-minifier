<?php
/**
 * Created D/25/02/2018
 * Updated S/03/12/2022
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

class Luigifab_Minifier_Block_Adminhtml_Config_Check extends Mage_Adminhtml_Block_System_Config_Form_Field {

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue()->unsPath();
		return parent::render($element);
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

		if (is_executable($program = '/usr/bin/npm'))
			exec($program.' --version', $npm);
		$npm = empty($npm) ? '' : 'npm '.trim(implode($npm));

		if (!empty($program = Mage::getStoreConfig('minifier/cssjs/cleancss')) && is_executable($program))
			exec($program.' --version', $css);
		if (empty($css) && is_executable($program = '/usr/bin/cleancss'))
			exec($program.' --version', $css);
		$css = empty($css) ? 'cleancss not found' : 'clean-css (4.3+) '.trim(implode($css));

		if (!empty($program = Mage::getStoreConfig('minifier/cssjs/uglifyjs')) && is_executable($program))
			exec($program.' --version', $js);
		if (empty($js) && is_executable($program = '/usr/bin/uglifyjs'))
			exec($program.' --version', $js);
		$js = empty($js) ? 'uglifyjs not found' : str_replace('uglify-js', 'uglify-js (3.0+)', trim(implode($js)));

		$element->setValue(implode('<br />', array_filter([$npm, $css, $js, $this->helper('minifier')->getNumberOfCpuCore().' cpu'])));
		return sprintf('<strong lang="en" id="%s">%s</strong>', $element->getHtmlId(), $element->getValue());
	}
}