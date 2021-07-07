<?php
/**
 * Created S/20/06/2015
 * Updated D/23/05/2021
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

class Luigifab_Minifier_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getVersion() {
		return (string) Mage::getConfig()->getModuleConfig('Luigifab_Minifier')->version;
	}

	public function _(string $data, ...$values) {
		$text = $this->__(' '.$data, ...$values);
		return ($text[0] == ' ') ? $this->__($data, ...$values) : $text;
	}

	public function escapeEntities($data, bool $quotes = false) {
		return htmlspecialchars($data, $quotes ? ENT_SUBSTITUTE | ENT_COMPAT : ENT_SUBSTITUTE | ENT_NOQUOTES);
	}


	public function afterToHtml(string $html) {

		$current = Mage::app()->getFrontController()->getAction()->getFullActionName('/');
		$exclude = array_filter(preg_split('#\s+#', Mage::getStoreConfig('minifier/html/exclude')));

		if (extension_loaded('tidy') && !in_array($current, $exclude) && Mage::getStoreConfigFlag('minifier/html/enabled'))
			$html = $this->cleanWithTidy($html);

		if (Mage::getStoreConfigFlag('minifier/gzip/enabled'))
			$html = $this->compressWithGzip($html);

		return trim($html);
	}

	public function getKeyForUrls() {

		if (!isset($this->_urlkey)) {
			$this->_urlkey = '';
			if (Mage::getStoreConfigFlag('minifier/cssjs/solution')) {
				$this->_urlkey = '-'.preg_replace('#\D#', '', Mage::getStoreConfig('minifier/cssjs/value'));
				if (Mage::getIsDeveloperMode())
					$this->_urlkey = '-00'.date('YmdHis');
			}
		}

		return $this->_urlkey;
	}

	private function cleanWithTidy(string $html) {

		$html = str_replace('></option>', '>&nbsp;</option>', $html);

		$tidy = new Tidy();
		$tidy->parseString($html, Mage::getModuleDir('etc', 'Luigifab_Minifier').'/tidy.conf', 'utf8');
		$tidy->cleanRepair();

		$html = str_replace(["\"\n   ", '>&nbsp;</option>'], ['"', '></option>'], tidy_get_output($tidy)); // doctype & option
		return preg_replace([
			'#css">\s+/\*<!\[CDATA\[\*/#',
			'#/\*]]>\*/\s+</style#',
			'#" />#',
			'#>\s*</script>#',
			'#-->\s{2,}#',
			'#\s*<!--\[if([^]]+)]>\s*<#',
			'#>\s*<!\[endif]-->#',
			'#>\s*/?/?<!\[CDATA\[#',
			'#\s*/?/?]]></script>#',
			'#<br ?/?>\s+#',
			'#</code>\s</pre>#',
			'#\s+</textarea>#'
		], [
			'css">',
			'</style',
			'"/>',
			'></script>',
			"-->\n",
			"\n<!--[if$1]><",
			'><![endif]-->',
			'>//<![CDATA[',
			"\n//]]></script>",
			'<br/>',
			'</code></pre>',
			'</textarea>'
		], $html);
	}

	private function compressWithGzip(string $html) {

		if ((ini_get('zlib.output_compression') != '1') && (mb_stripos(getenv('HTTP_ACCEPT_ENCODING'), 'gzip') !== false)) {
			header('Content-Encoding: gzip');
			$html = gzencode($html, 9);
		}

		return $html;
	}

	public function getNumberOfCpuCore() {

		if (empty($this->cpucore)) {
			exec('nproc', $data);
			$this->cpucore = max(1, (int) trim(implode($data)));
		}
		return $this->cpucore;
	}
}