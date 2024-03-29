<?php
/**
 * Created D/15/11/2020
 * Updated S/23/12/2023
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

class Luigifab_Minifier_DebugController extends Mage_Core_Controller_Front_Action {

	public function preDispatch() {

		Mage::register('turpentine_nocache_flag', true, true);
		$this->getResponse()
			->setHeader('Cache-Control', 'no-cache, must-revalidate', true)
			->setHeader('X-Robots-Tag', 'noindex, nofollow', true);

		$isBot = true;
		$userAgent = getenv('HTTP_USER_AGENT');

		if (!empty($userAgent) && (mb_stripos($userAgent, 'bot') === false)) {
			$isBot = false;
			// @see Mage_Log_Model_Visitor
			$ignoreAgents = Mage::getConfig()->getNode('global/ignore_user_agents');
			if (!empty($ignoreAgents)) {
				$ignoreAgents = $ignoreAgents->asArray();
				foreach ($ignoreAgents as $ignoreAgent) {
					if (mb_stripos($userAgent, $ignoreAgent) !== false) {
						$isBot = true;
						break;
					}
				}
			}
		}

		if ($isBot) {
			$this->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
			$this->getResponse()->setHttpResponseCode(404);
		}

		return parent::preDispatch();
	}

	public function indexAction() {

		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && Mage::getStoreConfigFlag(Mage::app()->getStore()->isAdmin() ? 'minifier/cssjs/enabled_back' : 'minifier/cssjs/enabled_front')) {

			$pass = Mage::getStoreConfig('minifier/cssjs/debug_password');
			if (!empty($pass) && ($this->getRequest()->getParam('pass') != $pass)) {
				$link = '';
				$text = 'invalid pass';
			}
			else {
				$files = Mage::getSingleton('core/session')->getData('minifier');

				if (empty(Mage::getSingleton('core/cookie')->get('minifier'))) {
					$link = ' - <a href="'.Mage::getUrl('*/*/start', ['pass' => $pass]).'">start</a>';
					if (empty($files))
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $pass]).'" style="color:#666;">clear</a>';
					else
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $pass]).'">clear</a>';
				}
				else {
					$link = ' - <a href="'.Mage::getUrl('*/*/stop', ['pass' => $pass]).'">stop</a>';
					if (empty($files))
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $pass]).'" style="color:#666;">clear</a>';
					else
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $pass]).'">clear</a>';
				}

				if (is_array($files))
					$text = str_replace(BP.'/', '', print_r($this->formatData($files), true));
				else
					$text = 'no data';
			}
		}
		else {
			$link = '';
			$text = 'disabled';
		}

		$this->getResponse()
			->setHttpResponseCode(200)
			->setHeader('Content-Type', 'text/html; charset=utf-8', true)
			->setBody('<html lang="en"><head><title>minifier</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="robots" content="noindex,nofollow"></head><body><pre style="white-space:pre-wrap;"><b>'.date('c').'</b>'.$link.'<br><br>'.$text.'</pre></body></html>');
	}

	public function startAction() {

		$pass = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($pass) || ($this->getRequest()->getParam('pass') == $pass))) {
			Mage::getSingleton('core/cookie')->set('minifier', 1, true);
			$this->_redirect('*/*/index', ['pass' => $pass]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}

	public function clearAction() {

		$pass = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($pass) || ($this->getRequest()->getParam('pass') == $pass))) {
			Mage::getSingleton('core/session')->setData('minifier', null);
			$this->_redirect('*/*/index', ['pass' => $pass]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}

	public function stopAction() {

		$pass = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($pass) || ($this->getRequest()->getParam('pass') == $pass))) {
			Mage::getSingleton('core/cookie')->delete('minifier');
			$this->_redirect('*/*/index', ['pass' => $pass]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}

	protected function formatData(array $files) {

		$base   = Mage::getBaseUrl('web');
		$helper = Mage::helper('minifier');

		foreach ($files as $key => $file) {

			if (!empty($file['files'])) {

				$data = [];
				$full = 0;

				foreach ($file['files'] as $src => $dst) {

					$size = 0;
					$nkey = '<b>'.$src.'</b>';

					$data[$nkey] = [
						'src_size' => is_file($src) ?
							$helper->getNumber($size = filesize($src) / 1024, ['precision' => 2]).' k' :
							'<b>not found</b>',
						'src_date' => is_file($src) ?
							date('Y-m-d H:i:s', filemtime($src)) :
							'<b>not found</b>',
						'src_link' => '<a href="'.$base.$src.'">'.$src.'</a>',
						'dst_size' => is_file($dst) ?
							$helper->getNumber(filesize($dst) / 1024, ['precision' => 2]).' k' :
							'<b>not found</b>',
						'dst_date' => is_file($dst) ?
							date('Y-m-d H:i:s', filemtime($dst)) :
							'<b>not found</b>',
						'dst_link' => '<a href="'.$base.$dst.'">'.$dst.'</a>',
					];

					if (($file['media'] != 'virtual') && is_file($src)) {

						$md5name = substr(md5($src), 0, 10);      // not mb_substr
						$md5data = substr(md5_file($src), 0, 10); // not mb_substr

						$data[$nkey]['dst_link'] = '<a href="'.$base.$dst.'">'.str_replace(
							[$md5name, $md5data], ['<b>'.$md5name.'</b>', '<b>'.$md5data.'</b>'], $dst
						).'</a>';
					}

					$full += $size;
				}

				if ($file['media'] != 'virtual')
					$data['calc'] = $helper->getNumber($full, ['precision' => 2]).' k';

				$files[$key]['files'] = $data;
			}

			if (!empty($file['html'])) {
				$files[$key]['html'] = htmlspecialchars($files[$key]['html']);
			}
		}

		return $files;
	}
}