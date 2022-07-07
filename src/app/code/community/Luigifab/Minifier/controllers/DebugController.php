<?php
/**
 * Created D/15/11/2020
 * Updated D/26/06/2022
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

class Luigifab_Minifier_DebugController extends Mage_Core_Controller_Front_Action {

	protected function formatData(array $files) {

		$base = Mage::getBaseUrl('web');
		$help = Mage::helper('minifier');

		foreach ($files as $key => $file) {

			if (!empty($file['files'])) {

				$data = [];
				$full = 0;

				foreach ($file['files'] as $src => $dst) {

					$size = 0;
					$nkey = '<b>'.$src.'</b>';

					$data[$nkey] = [
						'src_size' => is_file($src) ?
							$help->getNumber($size = filesize($src) / 1024, ['precision' => 2]).' k' :
							'<b>not found</b>',
						'src_date' => is_file($src) ?
							date('Y-m-d H:i:s', filemtime($src)) :
							'<b>not found</b>',
						'src_link' => '<a href="'.$base.$src.'">'.$src.'</a>',
						'dst_size' => is_file($dst) ?
							$help->getNumber(filesize($dst) / 1024, ['precision' => 2]).' k' :
							'<b>not found</b>',
						'dst_date' => is_file($dst) ?
							date('Y-m-d H:i:s', filemtime($dst)) :
							'<b>not found</b>',
						'dst_link' => '<a href="'.$base.$dst.'">'.$dst.'</a>'
					];

					if (($file['media'] != 'virtual') && is_file($src)) {

						$md5Name = substr(md5($src), 0, 10);
						$md5Data = substr(md5_file($src), 0, 10);

						$data[$nkey]['dst_link'] = '<a href="'.$base.$dst.'">'.str_replace(
							[$md5Name, $md5Data], ['<b>'.$md5Name.'</b>', '<b>'.$md5Data.'</b>'], $dst
						).'</a>';
					}

					$full += $size;
				}

				if ($file['media'] != 'virtual')
					$data['calc'] = $help->getNumber($full, ['precision' => 2]).' k';

				$files[$key]['files'] = $data;
			}

			if (!empty($file['html'])) {
				$files[$key]['html'] = htmlspecialchars($files[$key]['html']);
			}
		}

		return $files;
	}

	public function indexAction() {

		Mage::register('turpentine_nocache_flag', true, true);

		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && Mage::getStoreConfigFlag(Mage::app()->getStore()->isAdmin() ? 'minifier/cssjs/enabled_back' : 'minifier/cssjs/enabled_front')) {

			$passwd = Mage::getStoreConfig('minifier/cssjs/debug_password');
			if (!empty($passwd) && ($this->getRequest()->getParam('pass') != $passwd)) {
				$link = '';
				$text = 'invalid pass';
			}
			else {
				$files = Mage::getSingleton('core/session')->getData('minifier');

				if (empty(Mage::getSingleton('core/cookie')->get('minifier'))) {
					$link = ' - <a href="'.Mage::getUrl('*/*/start', ['pass' => $passwd]).'">start</a>';
					if (empty($files))
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $passwd]).'" style="color:#666;">clear</a>';
					else
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $passwd]).'">clear</a>';
				}
				else {
					$link = ' - <a href="'.Mage::getUrl('*/*/stop', ['pass' => $passwd]).'">stop</a>';
					if (empty($files))
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $passwd]).'" style="color:#666;">clear</a>';
					else
						$link .= ' - <a href="'.Mage::getUrl('*/*/clear', ['pass' => $passwd]).'">clear</a>';
				}

				if (is_array($files))
					$text = str_replace(Mage::getBaseDir().'/', '', print_r($this->formatData($files), true));
				else
					$text = 'no data';
			}
		}
		else {
			$link = '';
			$text = 'disabled';
		}

		$this->getResponse()->setBody(
			'<html lang="en"><head><title>minifier</title>'.
			'<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.
			'<meta name="robots" content="noindex,nofollow"></head><body><pre style="white-space:pre-wrap;">'.
			date('c').$link.'<br><br>'.$text.
			'</pre></body></html>');
	}

	public function startAction() {

		Mage::register('turpentine_nocache_flag', true, true);

		$passwd = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($passwd) || ($this->getRequest()->getParam('pass') == $passwd))) {
			Mage::getSingleton('core/cookie')->set('minifier', 1, true);
			$this->_redirect('*/*/index', ['pass' => $passwd]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}

	public function clearAction() {

		Mage::register('turpentine_nocache_flag', true, true);

		$passwd = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($passwd) || ($this->getRequest()->getParam('pass') == $passwd))) {
			Mage::getSingleton('core/session')->setData('minifier', null);
			$this->_redirect('*/*/index', ['pass' => $passwd]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}

	public function stopAction() {

		Mage::register('turpentine_nocache_flag', true, true);

		$passwd = Mage::getStoreConfig('minifier/cssjs/debug_password');
		if (Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled') && (empty($passwd) || ($this->getRequest()->getParam('pass') == $passwd))) {
			Mage::getSingleton('core/cookie')->delete('minifier');
			$this->_redirect('*/*/index', ['pass' => $passwd]);
		}
		else {
			$this->_redirect('*/*/index');
		}
	}
}