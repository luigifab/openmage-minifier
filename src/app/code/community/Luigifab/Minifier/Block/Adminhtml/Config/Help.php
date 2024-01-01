<?php
/**
 * Created S/20/06/2015
 * Updated D/26/11/2023
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

class Luigifab_Minifier_Block_Adminhtml_Config_Help extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	public function render(Varien_Data_Form_Element_Abstract $element) {

		$msg = $this->checkRewrites();
		if ($msg !== true)
			return sprintf('<p class="box">%s %s <span class="right">Stop russian war. <b>🇺🇦 Free Ukraine!</b> | <a href="https://github.com/luigifab/%3$s">github.com</a> | <a href="https://www.%4$s">%4$s</a> - ⚠ IPv6</span></p><p class="box" style="margin-top:-5px; color:white; background-color:#E60000;"><strong>%5$s</strong><br />%6$s</p>',
				'Luigifab/Minifier', $this->helper('minifier')->getVersion(), 'openmage-minifier', 'luigifab.fr/openmage/minifier',
				$this->__('INCOMPLETE MODULE INSTALLATION'),
				$this->__('There is conflict (<em>%s</em>).', $msg));

		return sprintf('<p class="box">%s %s <span class="right">Stop russian war. <b>🇺🇦 Free Ukraine!</b> | <a href="https://github.com/luigifab/%3$s">github.com</a> | <a href="https://www.%4$s">%4$s</a> - ⚠ IPv6</span></p>',
			'Luigifab/Minifier', $this->helper('minifier')->getVersion(), 'openmage-minifier', 'luigifab.fr/openmage/minifier');
	}

	protected function checkRewrites() {

		$rewrites = [
			['block' => 'adminhtml/page'],
			['block' => 'adminhtml/page_head'],
			['block' => 'page/html'],
			['block' => 'page/html_head'],
			['helper' => 'core/js'],
			['model' => 'core/design_package'],
			['model' => 'core/translate'],
		];

		foreach ($rewrites as $rewrite) {
			foreach ($rewrite as $type => $class) {
				if (($type == 'model') && (mb_stripos(Mage::getConfig()->getModelClassName($class), 'luigifab') === false))
					return $class;
				if (($type == 'block') && (mb_stripos(Mage::getConfig()->getBlockClassName($class), 'luigifab') === false))
					return $class;
				if (($type == 'helper') && (mb_stripos(Mage::getConfig()->getHelperClassName($class), 'luigifab') === false))
					return $class;
			}
		}

		return true;
	}
}