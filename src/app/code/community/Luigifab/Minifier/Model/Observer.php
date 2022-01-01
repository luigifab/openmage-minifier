<?php
/**
 * Created S/20/06/2015
 * Updated J/04/11/2021
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

class Luigifab_Minifier_Model_Observer extends Luigifab_Minifier_Helper_Data {

	// EVENT clean_media_cache_after (adminhtml)
	public function clearMinifiedFiles(Varien_Event_Observer $observer) {

		$dirs = [
			Mage::getBaseDir('media').'/minifier/',
			Mage::getBaseDir('media').'/css/',
			Mage::getBaseDir('media').'/css_secure/',
			Mage::getBaseDir('media').'/js_secure/',
			Mage::getBaseDir('media').'/js/'
		];

		exec('rm -rf '.implode(' ', $dirs));
		exec('rm -f  '.Mage::getBaseDir('media').'/minifier-cache/virtual*');

		$this->updateConfig($observer);
	}

	// EVENT admin_system_config_changed_section_minifier (adminhtml)
	public function updateConfig(Varien_Event_Observer $observer) {

		Mage::app()->cleanCache();
		Mage::dispatchEvent('adminhtml_cache_flush_system');

		Mage::getSingleton('adminhtml/session')->addSuccess(str_replace('Magento', 'OpenMage', Mage::helper('adminhtml')->__('The Magento cache storage has been flushed.')));
	}

	// EVENT controller_action_predispatch_adminhtml_index_changeLocale (adminhtml)
	public function changeBackgendLanguage(Varien_Event_Observer $observer) {

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

		// recherche des préférences dans HTTP_ACCEPT_LANGUAGE
		// https://stackoverflow.com/a/33748742
		$codes = array_reduce(
			empty(getenv('HTTP_ACCEPT_LANGUAGE')) ? [] : explode(',', getenv('HTTP_ACCEPT_LANGUAGE')),
			static function ($items, $item) {
				[$code, $q] = explode(';q=', $item.';q=1');
				$items[str_replace('-', '_', $code)] = (float) $q;
				return $items;
			}, []);

		arsort($codes);
		$codes = array_map('\strval', array_keys($codes));

		// ajoute la locale présente dans l'url en premier car elle est prioritaire
		if (!empty($_GET['lang'])) {
			$code = str_replace('-', '_', $_GET['lang']);
			if (strpos($code, '_') !== false)
				array_unshift($codes, substr($code, 0, strpos($code, '_')));
			array_unshift($codes, $code);
		}

		// cherche la locale à utiliser
		// essaye es ou fil puis es_ES ou fil_PH
		foreach ($codes as $code) {

			if ((strlen($code) >= 2) && (strpos($code, '_') === false)) {
				// es devient es_ES de manière à prioriser es_ES au lieu d'utiliser es_XX
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