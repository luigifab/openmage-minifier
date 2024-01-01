<?php
/**
 * Created M/01/09/2015
 * Updated D/03/12/2023
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

class Luigifab_Minifier_Block_Rewrite_Head extends Mage_Page_Block_Html_Head {

	//protected function _construct() {
	//	$this->setModuleName('Mage_Page');
	//}

	public function getCssJsHtml() {

		// @todo (int) for strict_types=1
		$items = Mage::getSingleton('minifier/files')->getMinifiedFiles((int) Mage::app()->getStore()->getId());

		if (!empty($items)) {

			$html = array_column($items, 'html');
			if (!empty($this->_data['items']['other']))
				$html[] = $this->_prepareOtherHtmlHeadElements($this->_data['items']['other']);

			return implode("\n", $html);
		}

		return parent::getCssJsHtml();
	}

	protected function &_prepareStaticAndSkinElements($format, array $staticItems, array $skinItems, $mergeCallback = null) {

		$design = Mage::getDesign();
		$items  = [];
		$html   = '';

		foreach ($staticItems as $params => $rows) {
			$params = trim($params);
			foreach ($rows as $row)
				$items[$params][] = $design->getJsUrl($row);
		}

		foreach ($skinItems as $params => $rows) {
			$params = trim($params);
			foreach ($rows as $row)
				$items[$params][] = $design->getSkinUrl($row);
		}

		foreach ($items as $params => $rows) {
			$params = empty($params) ? '' : ' '.$params;
			foreach ($rows as $row)
				$html .= sprintf($format, $row, $params);
		}

		return $html;
	}
}