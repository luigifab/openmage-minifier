<?php
/**
 * Created D/20/05/2018
 * Updated D/18/07/2021
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

class Luigifab_Minifier_Block_Calendar extends Mage_Core_Block_Html_Calendar {

	public function getHtml(string $locale) {

		// get days names
		$days = Zend_Locale_Data::getList($locale, 'days');
		$this->assign('days', [
			'wide'        => json_encode(array_values($days['format']['wide'])),
			'abbreviated' => json_encode(array_values($days['format']['abbreviated']))
		]);

		// get months names
		$months = Zend_Locale_Data::getList($locale, 'months');
		$this->assign('months', [
			'wide'        => json_encode(array_values($months['format']['wide'])),
			'abbreviated' => json_encode(array_values($months['format']['abbreviated']))
		]);

		// get "today" and "week" words
		$this->assign('today', json_encode(Zend_Locale_Data::getContent($locale, 'relative', 0)));
		$this->assign('week',  json_encode(Zend_Locale_Data::getContent($locale, 'field', 'week')));

		// get "am" & "pm" words
		$this->assign('am', json_encode(Zend_Locale_Data::getContent($locale, 'am')));
		$this->assign('pm', json_encode(Zend_Locale_Data::getContent($locale, 'pm')));

		// get first day of week and weekend days
		$this->assign('firstDay', (int) Mage::getStoreConfig('general/locale/firstday'));
		$this->assign('weekendDays', json_encode((string) Mage::getStoreConfig('general/locale/weekend')));

		// define default format and tooltip format
		$this->assign('defaultFormat', json_encode(
			Mage::getSingleton('core/locale')->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)
		));
		$this->assign('toolTipFormat', json_encode(
			Mage::getSingleton('core/locale')->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG)
		));

		// get days and months for en_US locale - calendar will parse exactly in this locale
		$months = Zend_Locale_Data::getList('en_US', 'months');
		$enUS = new stdClass();
		$enUS->m = new stdClass();
		$enUS->m->wide = array_values($months['format']['wide']);
		$enUS->m->abbr = array_values($months['format']['abbreviated']);
		$this->assign('enUS', json_encode($enUS));

		return $this->renderView();
	}
}