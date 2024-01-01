<?php
/**
 * Created S/20/06/2015
 * Updated S/09/12/2023
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

class Luigifab_Minifier_Model_Observer extends Luigifab_Minifier_Helper_Data {

	// EVENT clean_media_cache_after (adminhtml)
	public function clearMinifiedFiles(Varien_Event_Observer $observer) {

		$dirs = [
			Mage::getBaseDir('media').'/minifier/',
			Mage::getBaseDir('media').'/css/',
			Mage::getBaseDir('media').'/css_secure/',
			Mage::getBaseDir('media').'/js_secure/',
			Mage::getBaseDir('media').'/js/',
		];

		exec('rm -rf '.implode(' ', array_map('escapeshellarg', $dirs)));
		exec('rm -f  '.escapeshellarg(Mage::getBaseDir('media').'/minifier-cache/virtual*'));

		$this->clearCache($observer);
	}

	// EVENT admin_system_config_changed_section_minifier (adminhtml)
	public function clearCache(Varien_Event_Observer $observer) {

		Mage::app()->cleanCache();
		Mage::dispatchEvent('adminhtml_cache_flush_system');

		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The OpenMage cache has been flushed and updates applied.'));
	}

	// EVENT controller_action_predispatch_adminhtml_index_changeLocale (adminhtml)
	public function updateBackendLanguage(Varien_Event_Observer $observer) {

		$locale = $observer->getData('controller_action')->getRequest()->getParam('locale');
		Mage::getSingleton('core/session')->setData('locale', $locale);
	}

	// EVENT core_locale_set_locale (adminhtml)
	public function setBackendLanguage(Varien_Event_Observer $observer) {

		if (Mage::getStoreConfigFlag('minifier/general/autolang') && !empty(getenv('HTTP_ACCEPT_LANGUAGE')) &&
		    empty(Mage::getSingleton('core/session')->getData('locale'))) {

			$locales = Mage::getSingleton('core/locale_config')->getAllowedLocales();
			$result  = $this->searchCurrentLocale($locales);

			if (!empty($result)) {
				$observer->getData('locale')->setLocaleCode($result);
				Mage::getSingleton('core/session')->setData('locale', $result);
			}
		}
	}

	protected function searchCurrentLocale(array $locales, string $result = 'en_US') {

		$codes = [];

		// @see https://stackoverflow.com/a/33748742
		// no mb_functions for locale codes
		if (!empty(getenv('HTTP_ACCEPT_LANGUAGE'))) {

			$codes = array_reduce(
				explode(',', getenv('HTTP_ACCEPT_LANGUAGE')),
				static function ($items, $item) {
					[$code, $q] = explode(';q=', $item.';q=1');
					$items[str_replace('-', '_', $code)] = (float) $q;
					return $items;
				}, []);

			arsort($codes);
			$codes = array_map('\strval', array_keys($codes));
		}

		if (!empty($_GET['lang'])) {
			$code = str_replace('-', '_', $_GET['lang']);
			if (str_contains($code, '_'))
				array_unshift($codes, substr($code, 0, strpos($code, '_')));
			array_unshift($codes, $code);
		}

		foreach ($codes as $code) {

			if ((strlen($code) >= 2) && !str_contains($code, '_')) {
				// es becomes es_ES to prioritize es_ES instead of es_XX
				if (in_array($code.'_'.strtoupper($code), $locales)) {
					$result = $code.'_'.strtoupper($code);
					break;
				}
				// es
				foreach ($locales as $locale) {
					if (stripos($locale, $code) === 0) {
						$result = $locale;
						break 2;
					}
				}
			}
			else if (in_array($code, $locales)) {
				$result = $code;
				break;
			}
		}

		return $result;
	}
}