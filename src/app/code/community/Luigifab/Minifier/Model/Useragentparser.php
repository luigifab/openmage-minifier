<?php
/**
 * Copyright 2013-2023 | Jesse G. Donat <donatj~gmail~com>
 * https://github.com/donatj/PhpUserAgent
 *
 * Copyright 2019-2023 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://gist.github.com/luigifab/4cb373e75f3cd2f342ca6bc25504b149 (1.8.0-fork2)
 *
 * Parses a user agent string into its important parts
 * Licensed under the MIT License
 */

class Luigifab_Minifier_Model_Useragentparser {

	public function parse($userAgent = null) {

		if (empty($userAgent)) $userAgent = getenv('HTTP_USER_AGENT');

		$platform = null;
		$browser  = null;
		$version  = null;
		$empty    = ['platform' => $platform, 'browser' => $browser, 'version' => $version];
		$priority = ['Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish'];

		if (empty($userAgent)) return $empty;

		if (preg_match('/\((.*?)\)/m', $userAgent, $parentMatches)) {

			preg_match_all('/(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(?:Open|Net|Free)BSD|Macintosh|Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?) (?:\ [^;]*)? (?:;|$)/imx',
				$parentMatches[1], $result);
			$result['platform'] = array_unique($result['platform']);
			if (count($result['platform']) > 1) {
				if (!empty($keys = array_intersect($priority, $result['platform'])))
					$platform = reset($keys);
				else
					$platform = $result['platform'][0];
			}
			else if (isset($result['platform'][0])) {
				$platform = $result['platform'][0];
			}
		}

		if ($platform == 'linux-gnu' || $platform == 'X11')
			$platform = 'Linux';
		else if ($platform == 'CrOS')
			$platform = 'Chrome OS';
		else if ($platform == 'Adr')
			$platform = 'Android';
		else if (($platform === null) && preg_match_all('%(?P<platform>Android)[:/ ]%ix', $userAgent, $result))
			$platform = $result['platform'][0];








		preg_match_all( // ["browser" => ["Firefox"...], "version" => ["45.0"...]]
			'%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Thunderbird|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|YaApp_Android|Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|Valve\ Steam\ Tenfoot|NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)\)?;?(?:[:/ ](?P<version>[\dA-Z.]+)|/[A-Z]*)%ix',
			$userAgent, $result);


		// If nothing matched, return null (to avoid undefined index errors)
		if (!isset($result['browser'][0], $result['version'][0])) {

			if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z\d\-]+)([/ :](?P<version>[\dA-Z.]+))?%ix', $userAgent, $result)) {
				return [
					'platform' => $platform ?: null,
					'browser'  => $result['browser'],
					'version'  => empty($result['version']) ? null : $result['version'],
				];
			}
			return $empty;
		}


		if (preg_match('/rv:(?P<version>[\dA-Z.]+)/i', $userAgent, $rv_result)) {
			$rv_result = $rv_result['version'];
		}

		$browser = $result['browser'][0];
		$version = $result['version'][0];

		$lowerBrowser = array_map('strtolower', $result['browser']);
		$key = 0;
		$val = '';

		if ($this->findT($lowerBrowser, ['OPR' => 'Opera', 'Facebot' => 'iMessageBot', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'YaApp_Android' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge', 'EdgA' => 'Edge', 'XiaoMi/MiuiBrowser' => 'MiuiBrowser'], $key, $browser)) {
			$version = is_numeric(substr($result['version'][$key], 0, 1)) ? $result['version'][$key] : null;
		}
		else if ($this->find($lowerBrowser, 'Playstation Vita', $key, $platform)) {
			$platform = 'PlayStation Vita';
			$browser  = 'Browser';
		}
		else if ($this->find($lowerBrowser, ['Kindle Fire', 'Silk'], $key, $val)) {
			$browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
			$platform = 'Kindle Fire';
			if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
				$version = $result['version'][array_search('Version', $result['browser'])];
			}
		}
		else if ($platform == 'Nintendo 3DS' || $this->find($lowerBrowser, 'NintendoBrowser', $key)) {
			$browser = 'NintendoBrowser';
			$version = $result['version'][$key];
		}
		else if ($this->find($lowerBrowser, 'Kindle', $key, $platform)) {
			$browser = $result['browser'][$key];
			$version = $result['version'][$key];
		}
		else if ($this->find($lowerBrowser, 'Opera', $key, $browser)) {
			$this->find($lowerBrowser, 'Version', $key);
			$version = $result['version'][$key];
		}
		else if ($this->find($lowerBrowser, 'Puffin', $key, $browser)) {
			$version = $result['version'][$key];
			if (strlen($version) > 3) {
				$part = substr($version, -2);
				if (ctype_upper($part)) {
					$version = substr($version, 0, -2);
					$flags   = ['IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows'];
					if (is_string($part) && array_key_exists($part, $flags)) {
						$platform = $flags[$part];
					}
				}
			}
		}
		else if ($this->find($lowerBrowser, ['Applebot', 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome', 'SailfishBrowser'], $key, $browser)) {
			$version = $result['version'][$key];
		}
		else if ($rv_result && $this->find($lowerBrowser, 'Trident')) {
			$browser = 'MSIE';
			$version = $rv_result;
		}
		else if ($browser == 'AppleWebKit') {
			if ($platform == 'Android') {
				$browser = 'Android Browser';
			}
			else if (strncmp((string) $platform, 'BB', 2) === 0) {
				$browser  = 'BlackBerry Browser';
				$platform = 'BlackBerry';
			}
			else if ($platform == 'BlackBerry' || $platform == 'PlayBook') {
				$browser = 'BlackBerry Browser';
			}
			else {
				$this->find($lowerBrowser, 'Safari', $key, $browser) || $this->find($lowerBrowser, 'TizenBrowser', $key, $browser);
			}
			$this->find($lowerBrowser, 'Version', $key);
			$version = $result['version'][$key];
		}
		else if (!empty($pKey = preg_grep('/playstation \d/i', $result['browser']))) {
			$pKey     = reset($pKey);
			$platform = 'PlayStation '.preg_replace('/\D/', '', $pKey);
			$browser  = 'NetFront';
		}

		return ['platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null];
	}

	private function find($lowerBrowser, $search, &$key = null, &$value = null) {

		$search = (array) $search;

		foreach ($search as $val) {
			$xkey = array_search(strtolower($val), $lowerBrowser);
			if ($xkey !== false) {
				$value = $val;
				$key   = $xkey;
				return true;
			}
		}

		return false;
	}

	private function findT($lowerBrowser, array $search, &$key = null, &$value = null) {

		$value2 = null;

		if ($this->find($lowerBrowser, array_keys($search), $key, $value2)) {
			$value = $search[$value2];
			return true;
		}

		return false;
	}
}