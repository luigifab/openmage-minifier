<?php
/**
 * Created W/13/04/2016
 * Updated S/23/12/2023
 *
 * Copyright 2011-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * Copyright 2022-2023 | Fabrice Creuzot <fabrice~cellublue~com>
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

class Luigifab_Minifier_Model_Files extends Mage_Core_Model_Layout_Update {

	public function getMinifiedFiles(int $storeId) {

		if (!Mage::getStoreConfigFlag(($storeId == 0) ? 'minifier/cssjs/enabled_back' : 'minifier/cssjs/enabled_front', $storeId))
			return [];

		$dir = Mage::getBaseDir('media').'/minifier-cache/';
		if (!is_dir($dir))
			@mkdir($dir, 0755);
		if (!is_dir($dir) || !is_writable($dir))
			Mage::throwException('Directory media/minifier-cache does not exist or is not writable.');

		$dir = Mage::getBaseDir('media').'/minifier/';
		if (!is_dir($dir))
			@mkdir($dir, 0755);
		if (!is_dir($dir) || !is_writable($dir))
			Mage::throwException('Directory media/minifier does not exist or is not writable.');

		$start  = microtime(true);
		$debug  = 'load files from cache';
		$design = Mage::getDesign();

		// cherche les fichiers sources
		// depuis le cache ou depuis le layout
		$ckey  = ($storeId == 0) ? 'admin_'.Mage::getSingleton('core/translate')->getLocale() : 'store_'.$storeId;
		$items = Mage::app()->useCache('layout') ? Mage::app()->loadCache('minifier_layout_'.$ckey) : null;
		$items = empty($items) ? null : @json_decode($items, true);

		if (empty($items)) {

			$debug = 'load files from layout';
			$items = $this->searchFilesFromLayout($design, $storeId);

			if (Mage::app()->useCache('layout'))
				Mage::app()->saveCache(json_encode($items), 'minifier_layout_'.$ckey,
					[Mage_Core_Model_Layout_Update::LAYOUT_GENERAL_CACHE_TAG]);
		}

		// si quelqu'un d'autre minifie/merge les fichiers, patiente le temps que ça se termine (maximum 90 secondes)
		// pour ne pas faire la même chose
		$lock = Mage::getBaseDir('var').'/minifier.lock';
		while (is_file($lock)) {
			if ((filemtime($lock) + 90) < time()) {
				unlink($lock);
				break;
			}
			usleep(500000); // 0.5 s
			clearstatcache(true, $lock);
		}

		// minifie les fichiers (et change la clé) si nécessaire
		// attention lorsque les fichiers sont en cache et que les fichiers sont supprimés sans vider le cache
		// les fichiers virtuels ne seront pas régénérés car il faut passer dans searchFilesFromLayout avant minifyFiles
		$files = [];
		foreach ($items as $file => $data) {

			$merge = false;

			foreach ($data['files'] as $source => $cache) {
				if (is_file($source) && !is_file($cache)) {
					$files['minify'][$source] = $cache;
					$merge = true;
				}
			}

			$dest = $dir.$file;
			if ($data['merge'] && ($merge || !is_file($dest)))
				$files['merge'][$dest] = array_values($data['files']);
		}

		if (!empty($files)) {

			exec(sprintf('command -v php%d.%d || command -v php', PHP_MAJOR_VERSION, PHP_MINOR_VERSION), $cmd);
			$cmd = trim(implode($cmd));
			if (empty($cmd))
				Mage::throwException('PHP not found');

			file_put_contents($lock, getenv('REMOTE_ADDR').' '.(getenv('HTTP_USER_AGENT') ?? 'unknown'));
			ignore_user_abort(true);
			set_time_limit(300);

			$log = Mage::getBaseDir('log');
			if (!is_dir($log))
				@mkdir($log, 0755);
			$log .= '/minifier.log';

			// leaves 2 cores free, but because $runs include grep check, we add 2 for $maxc
			// [] => 18:14 0:00 php ...
			// [] => 18:14 0:00 sh -c ps aux | grep Minifier/lib/minify.php
			// [] => 18:14 0:00 grep Minifier/lib/minify.php
			$maxc = 2 + max(1, Mage::helper('minifier')->getNumberOfCpuCore() - 2);

			// [source file => cache file]
			if (!empty($files['minify']))
				$this->minifyFiles($cmd, $maxc, $log, $files['minify']);

			// [dest file => [cache files]]
			if (!empty($files['merge']))
				$this->mergeFiles($cmd, $maxc, $log, $files['merge']);

			if (Mage::getStoreConfig('minifier/cssjs/solution') == '1') {
				$value = Mage::getSingleton('core/date')->date('YmdHis');
				Mage::getModel('core/config')->saveConfig('minifier/cssjs/value', $value);
				Mage::getConfig()->reinit();
			}

			unlink($lock);
		}

		// html
		foreach ($items as $file => $data) {

			$url = Mage::getBaseUrl('media').'minifier-zzyyxx/'.$file;
			$url = $design->getFinalUrl($dir.$file, $url);

			$items[$file]['html'] = $data['css'] ?
				sprintf('<link rel="stylesheet" media="%s" type="text/css" href="%s" />', $data['media'], $url) :
				sprintf('<script type="text/javascript" src="%s"></script>', $url);
		}

		// debug
		if (!empty($_COOKIE['minifier']) && Mage::getStoreConfigFlag('minifier/cssjs/debug_enabled')) {
			array_unshift($items, round(microtime(true) - $start, 3).' seconds');
			array_unshift($items, $debug);
			array_unshift($items, gmdate('c'));
			array_unshift($items, getenv('REQUEST_URI'));
			Mage::getSingleton('core/session')->setData('minifier', $items);
		}

		return $items;
	}

	// utilise uglify-js et clean-css
	protected function minifyFiles(string $php, int $maxc, string $log, array $files) {

		$pids = [];

		// @see https://medium.com/async-php/multi-process-php-94a4e5a4be05
		foreach ($files as $source => $cache) {

			while (count($pids) >= $maxc) {
				usleep(100000); // 0.1 s
				foreach ($pids as $key => $pid) {
					if (file_exists('/proc/'.$pid))
						clearstatcache(true, '/proc/'.$pid);
					else
						unset($pids[$key]);
				}
			}

			$runs = [];
			exec('ps aux | grep Minifier/lib/minify.php', $runs);
			while (count($runs) >= $maxc) {
				usleep(90000); // 0.09 s
				$runs = [];
				exec('ps aux | grep Minifier/lib/minify.php', $runs);
			}

			$outdated = preg_replace('#\.[a-z\d]+\.min\.#', '*.min.', $cache);
			array_map('unlink', glob($outdated));

			$cmd = sprintf('%s %s %s %s %s %d >> %s 2>&1 & echo $!',
				escapeshellcmd($php),
				str_replace('Minifier/etc', 'Minifier/lib/minify.php', Mage::getModuleDir('etc', 'Luigifab_Minifier')),
				(mb_stripos($source, '.css') === false) ? 'js' : 'css',
				escapeshellarg($source),
				escapeshellarg($cache),
				Mage::getIsDeveloperMode() ? 1 : 0,
				$log);

			Mage::log($cmd, Zend_Log::DEBUG, 'minifier.log');
			$pids[] = exec($cmd);
		}

		while (!empty($pids)) {
			usleep(100000); // 0.1 s
			foreach ($pids as $key => $pid) {
				if (file_exists('/proc/'.$pid))
					clearstatcache(true, '/proc/'.$pid);
				else
					unset($pids[$key]);
			}
		}

		return $this;
	}

	protected function mergeFiles(string $php, int $maxc, string $log, array $files) {

		$pids = [];

		// https://medium.com/async-php/multi-process-php-94a4e5a4be05
		foreach ($files as $dest => $sources) {

			while (count($pids) >= $maxc) {
				usleep(100000); // 0.1 s
				foreach ($pids as $key => $pid) {
					if (file_exists('/proc/'.$pid))
						clearstatcache(true, '/proc/'.$pid);
					else
						unset($pids[$key]);
				}
			}

			$runs = [];
			exec('ps aux | grep Minifier/lib/minify.php', $runs);
			while (count($runs) >= $maxc) {
				usleep(90000); // 0.09 s
				$runs = [];
				exec('ps aux | grep Minifier/lib/minify.php', $runs);
			}

			foreach ($sources as $idx => $file) {
				if (!is_file($file))
					unset($sources[$idx]);
			}

			$cmd = sprintf('%s %s %s %s %s %d >> %s 2>&1 & echo $!',
				escapeshellcmd($php),
				str_replace('Minifier/etc', 'Minifier/lib/minify.php', Mage::getModuleDir('etc', 'Luigifab_Minifier')),
				(mb_stripos($dest, '.js') === false) ? 'mergecss' : 'mergejs',
				implode(',', array_map('escapeshellarg', $sources)),
				escapeshellarg($dest),
				Mage::getIsDeveloperMode() ? 1 : 0,
				$log);

			Mage::log($cmd, Zend_Log::DEBUG, 'minifier.log');
			$pids[] = exec($cmd);
		}

		while (!empty($pids)) {
			usleep(100000); // 0.1 s
			foreach ($pids as $key => $pid) {
				if (file_exists('/proc/'.$pid))
					clearstatcache(true, '/proc/'.$pid);
				else
					unset($pids[$key]);
			}
		}

		return $this;
	}

	// prend un soin tout particulier à ignorer les données de oauth
	protected function getFileFromHelper(object $node) {

		$helperName = (string) $node->getAttribute('helper');
		if ($helperName == 'configurableswatches/getSwatchesProductJs')
			return 'js/configurableswatches/swatches-product.js';

		$helperName   = explode('/', $helperName);
		$helperMethod = array_pop($helperName);
		$helperName   = implode('/', $helperName);
		$attributes   = [];

		foreach ($node->attributes as $attrName => $attrNode)
			$attributes[] = [$attrName => $attrNode];

		return call_user_func_array([Mage::helper($helperName), $helperMethod], $attributes);
	}

	protected function searchFilesFromLayout(object $design, int $storeId) {

		//$ignores = array_filter(preg_split('#\s+#', Mage::getStoreConfig('minifier/cssjs/exclude')));
		$removed = [];
		$items   = [];
		$data    = ['optionalZipCountries = '.Mage::helper('directory')->getCountriesWithOptionalZip(true).';'];

		// génération des fichiers virtuels
		if (empty($storeId)) {
			$name = 'virtual-admin-'.str_replace('_','-', Mage::getSingleton('core/translate')->getLocale()).'.min.js';
			$area = 'adminhtml';
			$pack = 'admin';
		}
		else {
			$name = 'virtual-front-'.str_replace('_','-', Mage::getSingleton('core/translate')->getLocale()).'.min.js';
			$area = 'frontend';

			if (!empty($cnf = Mage::getStoreConfig('design/theme/skin', $storeId)))
				$pack = Mage::getStoreConfig('design/package/name', $storeId).'-'.$cnf;
			else if (!empty($cnf = Mage::getStoreConfig('design/theme/default', $storeId)))
				$pack = Mage::getStoreConfig('design/package/name', $storeId).'-'.$cnf;
			else
				$pack = Mage::getStoreConfig('design/package/name', $storeId).'-default';
		}

		$items[$name] = ['type' => 'minifier', 'name' => $name, 'media' => 'virtual', 'merge' => false, 'css' => false];
		$oldLocale = Mage::getSingleton('core/translate')->getLocale();
		$oldArea   = Mage::app()->getStore()->isAdmin() ? 'adminhtml' : 'frontend';
		$locales   = glob(Mage::getBaseDir('app').'/locale/*/Mage_Adminhtml.csv');

		foreach ($locales as $locale) {

			$locale   = mb_substr($locale, mb_stripos($locale, 'locale/') + 7);
			$locale   = mb_substr($locale, 0, mb_strpos($locale, '/'));
			$source   = $this->getMinifiedName('virtual-'.mb_substr($area, 0, 5).'-'.str_replace('_', '-', $locale).'.js', false, false);
			$minified = str_replace(['-cache', '.js'], ['', '.min.js'], $source);

			if (!is_file($source)) {

				Mage::getSingleton('core/translate')->setLocale($locale)->init($area, true);
				$data1 = Mage::getBlockSingleton('minifier/calendar')->setTemplate('page/js/calendar.phtml')->getHtml($locale);
				$data1 = str_replace(['<script type="text/javascript">', '<script>', '//<![CDATA[', '//]]>', '</script>'], '', $data1);
				$data1 = 'if (self.Calendar) { '.$data1.' }';
				$data2 = Mage::helper('core/js')->getTranslatorScriptContent();

				file_put_contents($source, trim(implode("\n", $data)."\n".trim($data2)."\n".trim($data1)), LOCK_EX);
			}

			$items[$name]['files'][$source] = $minified;
		}

		$virtual = $name;
		Mage::getSingleton('core/translate')->setLocale($oldLocale)->init($oldArea, true);

		// récupère la liste des fichiers depuis le layout
		$head  = Mage::app()->getLayout()->getBlock('head');
		$doc   = new DOMDocument();
		@$doc->loadHTML($this->merge('all')->_packageLayout->asNiceXml(), LIBXML_NOERROR);
		$xpath = new DomXPath($doc);

		foreach ($xpath->query('//action[@method="removeItem"]') as $element) {

			if (str_contains($element->getNodePath(), 'oauth'))
				continue;

			$config = $element->getAttribute('ifconfig');

			if (empty($config) || Mage::getStoreConfigFlag($config, $storeId)) {

				$nodes = [];
				foreach ($element->childNodes as $node) {
					if ($node->nodeType == 1)
						$nodes[] = $node;
				}

				// <action method="removeItem" ifconfig="a/b/c">
				//  <type>js | js_css | skin_js | skin_css</type>
				//  <name>abc/abc.xyz</name>
				// </action>
				$type = empty($nodes[0]) ? '' : trim($nodes[0]->textContent);
				$name = empty($nodes[1]) ? '' : trim($nodes[1]->textContent);

				if (!empty($type) && !empty($name))
					$removed[] = $type.'|'.$name;
			}
		}

		foreach ($xpath->query('//action[@method="addItem"]|//action[@method="addCss"]|//action[@method="addJs"]') as $element) {

			if (str_contains($element->getNodePath(), 'oauth'))
				continue;

			$config = $element->getAttribute('ifconfig');

			if (empty($config) || Mage::getStoreConfigFlag($config, $storeId)) {

				$nodes = [];
				foreach ($element->childNodes as $node) {
					if ($node->nodeType == 1)
						$nodes[] = $node;
				}

				// <action method="addItem" ifconfig="a/b/c">
				//  <type>js | js_css | skin_js | skin_css</type>
				//  <name>abc/abc.xyz</name>
				//  <params>abc="xyz"</params>
				//  <if />
				//  <condition />
				// </action>
				if ($element->getAttribute('method') == 'addItem') {

					$type   = empty($nodes[0]) ? '' : trim($nodes[0]->textContent);
					$name   = empty($nodes[1]) ? '' : trim($nodes[1]->textContent);
					$params = empty($nodes[2]) ? '' : trim($nodes[2]->textContent);
					$if     = empty($nodes[3]) ? '' : trim($nodes[3]->textContent);

					if (!empty($nodes[1]) && $nodes[1]->hasAttribute('helper'))
						$name = $this->getFileFromHelper($nodes[1]);

					if (empty($if) && !empty($name)) {
						if ($type == 'js') {

							$source = $this->getRealSource(BP.'/js/'.$name);
							$media  = $this->getFileMedia($name);
							$final  = $this->getFinalName($pack, $media);

							if (empty($items[$final]))
								$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => false];

							if (in_array($type.'|'.$name, $removed))
								$items[$final]['removed'][] = $source;
							else
								$items[$final]['files'][$source] = $this->getMinifiedName($source);
						}
						else if ($type == 'js_css') {

							$source = $this->getRealSource(BP.'/js/'.$name);
							$media  = $this->getFileMedia($params);
							$final  = $this->getFinalName($pack, $media);

							if (empty($items[$final]))
								$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => true];

							if (in_array($type.'|'.$name, $removed))
								$items[$final]['removed'][] = $source;
							else
								$items[$final]['files'][$source] = $this->getMinifiedName($source);
						}
						else if ($type == 'skin_css') {

							$source = $this->getRealSource($design->getFileName($name, ['_type' => 'skin', '_default' => false]));
							$media  = $this->getFileMedia($params);
							$final  = $this->getFinalName($pack, $media);

							if (empty($items[$final]))
								$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => true];

							if (in_array($type.'|'.$name, $removed))
								$items[$final]['removed'][] = $source;
							else
								$items[$final]['files'][$source] = $this->getMinifiedName($source);
						}
						else if ($type == 'skin_js') {

							$source = $this->getRealSource($design->getFileName($name, ['_type' => 'skin', '_default' => false]));
							$media  = $this->getFileMedia($name);
							$final  = $this->getFinalName($pack, $media);

							if (empty($items[$final]))
								$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => false];

							if (in_array($type.'|'.$name, $removed))
								$items[$final]['removed'][] = $source;
							else
								$items[$final]['files'][$source] = $this->getMinifiedName($source);
						}
					}
				}
				// addCss=skin_css addCssIe=skin_css
				// <action method="addCss" ifconfig="a/b/c">
				//  <name>abc/abc.css</name>
				//  <params>media="xyz" abc="xyz"</params>
				//  <if />
				//  <condition />
				// </action>
				else if ($element->getAttribute('method') == 'addCss') {

					$type   = 'skin_css';
					$name   = empty($nodes[0]) ? '' : trim($nodes[0]->textContent);
					$params = empty($nodes[1]) ? '' : trim($nodes[1]->textContent);
					$if     = empty($nodes[2]) ? '' : trim($nodes[2]->textContent);

					if (empty($if) && !empty($name)) {

						$source = $this->getRealSource($design->getFileName($name, ['_type' => 'skin', '_default' => false]));
						$media  = $this->getFileMedia($params);
						$final  = $this->getFinalName($pack, $media);

						if (empty($items[$final]))
							$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => true];

						if (in_array($type.'|'.$name, $removed))
							$items[$final]['removed'][] = $source;
						else
							$items[$final]['files'][$source] = $this->getMinifiedName($source);
					}
				}
				// addJs=js addJsIe=js
				// <action method="addJs" ifconfig="a/b/c">
				//  <script>abc/abc.js</script>
				//  <params />
				//  <if />
				//  <condition />
				// </action>
				else if ($element->getAttribute('method') == 'addJs') {

					$type  = 'js';
					$name  = empty($nodes[0]) ? '' : trim($nodes[0]->textContent);
					$if    = empty($nodes[2]) ? '' : trim($nodes[2]->textContent);

					if (empty($if) && !empty($name)) {

						$source = $this->getRealSource(BP.'/js/'.$name);
						$media  = $this->getFileMedia($name);
						$final  = $this->getFinalName($pack, $media);

						if (empty($items[$final]))
							$items[$final] = ['type' => 'minifier', 'name' => $final, 'media' => $media, 'merge' => true, 'css' => false];

						if (in_array($type.'|'.$name, $removed))
							$items[$final]['removed'][] = $source;
						else
							$items[$final]['files'][$source] = $this->getMinifiedName($source);
					}
				}
			}

			if (!empty($type) && !empty($name))
				$head->removeItem($type, $name);
		}

		// déplace la fichier virtuel en dernier
		$items[$virtual] = array_shift($items);

		return $items;
	}

	// nom des fichiers
	protected function getFinalName(string $pack, string $media) {
		return (string) preg_replace('#[^a-z\d.]+#', '-', $pack.'-'.$media.((mb_stripos($media, 'script') === false) ? '.min.css' : '.min.js'));
	}

	protected function getRealSource(string $file) {

		if (mb_stripos($file, '.min.js') !== false) {
			$check = str_replace('.min.js', '.js', $file);
			if (is_file($check))
				return $check;
		}

		if (mb_stripos($file, '.min.css') !== false) {
			$check = str_replace('.min.css', '.css', $file);
			if (is_file($check))
				return $check;
		}

		return $file;
	}

	protected function getFileMedia(string $data) {

		if (mb_stripos($data, 'media="') !== false)
			$media = mb_substr($data, mb_strpos($data, '"') + 1, -1);
		else if (mb_stripos($data, 'es6') !== false)
			$media = 'script-es6';
		else if (mb_stripos($data, '.js') !== false)
			$media = 'script';

		return empty($media) ? 'screen, projection' : $media;
	}

	protected function getMinifiedName(string $file, bool $md5 = true, bool $min = true) {

		// /.../example.js     => example.js
		// /.../example.min.js => example.min.js => example.js
		$name = str_replace(['.min.css', '.min.js'], ['.css', '.js'], basename($file));

		// example.js => example.md5name.js
		if ($min) {
			$key  = substr(md5($file), 0, 10); // not mb_substr
			$name = str_replace(['.css', '.js'], ['.'.$key.'.css', '.'.$key.'.js'], $name);
		}

		// example.js         => example.md5content.min.js
		// example.md5name.js => example.md5name.md5content.min.js
		if ($md5) {
			$key  = is_file($file) ? substr(md5_file($file), 0, 10) : 'na'; // not mb_substr
			$name = str_replace(['.css', '.js'], ['.'.$key.'.min.css', '.'.$key.'.min.js'], $name);
		}
		// example.md5name.js => example.md5name.min.js
		else if ($min) {
			$name = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $name);
		}

		// example.js
		// example.md5content.min.js
		// example.md5name.md5content.min.js
		return Mage::getBaseDir('media').'/minifier-cache/'.$name;
	}
}