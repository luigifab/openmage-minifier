<?php
/**
 * Created S/03/03/2018
 * Updated S/03/12/2022
 *
 * Copyright 2011-2023 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Minifier_Block_Adminhtml_Config_Server extends Mage_Adminhtml_Block_System_Config_Form_Field {

	public function render(Varien_Data_Form_Element_Abstract $element) {

		// getPath PR 2774
		return str_replace(['`', "\n"], [chr(194).chr(160), '<br />'], '<tr><td colspan="'.(empty($element->getPath()) ? 4 : 5).'"><code style="display:block; margin:1em 0; padding-left:6px; font-size:0.9em; line-height:1.4em; border-left:3px solid #AAA;"><strong>## THE REQUIRED CONFIGURATION</strong>

## for apache (virtual host or htaccess)
RewriteRule (.*)skin/.*/favicon.ico           $1favicon.ico [L]
# global
RewriteRule (.*)media/minifier-\d+/(.*)       $1media/minifier/$2 [L]
RewriteRule (.*)skin/adminhtml/(\w+)-\d+/(.*) $1skin/adminhtml/$2/$3 [L]
RewriteRule (.*)skin/frontend/(\w+)-\d+/(.*)  $1skin/frontend/$2/$3 [L]
RewriteRule (.*)js-\d+/(.*)                   $1js/$2 [L]
# by file
RewriteRule (.*)-\d{10,}\.(.*)                $1.$2 [L]

## for lighttpd
url.rewrite-once = (
````"(.*)/skin/.*/favicon.ico"           => "$1/favicon.ico",
````# global
````"(.*)/media/minifier-\d+/(.*)"       => "$1/media/minifier/$2",
````"(.*)/skin/adminhtml/(\w+)-\d+/(.*)" => "$1/skin/adminhtml/$2/$3",
````"(.*)/skin/frontend/(\w+)-\d+/(.*)"  => "$1/skin/frontend/$2/$3",
````"(.*)/js-\d+/(.*)"                   => "$1/js/$2",
````# by file
````"(.*)-\d{10,}\.(.*)"                 => "$1.$2"
)

## for nginx
rewrite "(.*)/skin/.*/favicon.ico"           $1/favicon.ico          last;
# global
rewrite "(.*)/media/minifier-\d+/(.*)"       $1/media/minifier/$2    last;
rewrite "(.*)/skin/adminhtml/(\w+)-\d+/(.*)" $1/skin/adminhtml/$2/$3 last;
rewrite "(.*)/skin/frontend/(\w+)-\d+/(.*)"  $1/skin/frontend/$2/$3  last;
rewrite "(.*)/js-\d+/(.*)"                   $1/js/$2                last;
# by file
rewrite "(.*)-\d{10,}\.(.*)"                 $1.$2                last;</code></td></tr>');
	}
}