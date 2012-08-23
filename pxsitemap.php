<?php
/*
 * This file is part of Pxsitemap.
 * 
 * Pxsitemap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Pxsitemap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Pxsitemap.  If not, see <http://www.gnu.org/licenses/>
 * 
 * 
 * Author: Pixelperfect s.n.c. <info@pixelperfect.it>
 * Website: http://www.pixelperfect.it
 * 
 */

// Turn off all error reporting
error_reporting(0);

// It may take a whils to crawl a site ...
set_time_limit(10000);

#
# Settings
#
$base_url = $_SERVER['HTTP_HOST'];

$default_properties = array(
	'priority' => 'auto1', //can be a number (between 0 and 1) or "autoN" scaling by 0.2 each subdirectory from N
	'changefreq' => 'daily',
	'lastmod' => date("Y-m-d")
);

$custom_properties = array(
	//'http://'.$_SERVER['HTTP_HOST'].'/mypage.htm' => array(
	//	'priority' => '0.1',
	//	'changefreq' => 'monthly',
	//	'lastmod' => date("Y-m-d")
	//)
);

$exclude_pages = array(
	//'http://'.$_SERVER['HTTP_HOST'].'/excludethis.htm'
);
$include_pages = array(
	//'http://'.$_SERVER['HTTP_HOST'].'/it/addthis.htm'
);

#
# Crawling
#

// Inculde the phpcrawl-mainclass
include("pxsitemap_include/phpcrawl/libs/PHPCrawler.class.php");

// Extend the class and override the handleDocumentInfo()-method 
class SitemapGenerator extends PHPCrawler {
	var $pages = array();
	
	function handleDocumentInfo($DocInfo) {
		if($DocInfo->http_status_code == '200') {
			$this->pages[] = $DocInfo->url;
		}
	} 
	
	function getPages () {
		return $this->pages;
	}
}

// Now, create a instance of your class, define the behaviour
// of the crawler (see class-reference for more options and details)
// and start the crawling-process. 
$crawler = new SitemapGenerator();
// URL to crawl
$crawler->setURL($base_url);
// Only receive content of files with content-type "text/html"
$crawler->addContentTypeReceiveRule("#text/html#");
// Ignore links to pictures, css, js, ecc; dont even request them
$crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png|ico|js|css)$# i");
// Store and send cookie-data like a browser does
$crawler->enableCookieHandling(true);
// Set the traffic-limit to 1 MB (in bytes,
// for testing we dont want to "suck" the whole site)
$crawler->setTrafficLimit(1000 * 1024);

// Thats enough, now here we go
$crawler->go();
// That's it, start crawling using 5 processes
//$crawler->goMultiProcessed(5);

// At the end, after the process is finished...
$pages = $crawler->getPages();

#
# XML response
#
$lb = "\n";
$lt = '<';
$gt = '>';
$sl = '/';
$qp = '?';

header("Content-type: text/xml");

echo $lt.$qp.'xml version="1.0" encoding="utf-8" '.$qp.$gt.$lb;

echo $lt.'urlset'.$lb;
echo ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'.$lb;
echo ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.$lb;
echo ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'.$lb;
echo ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"';
echo $gt.$lb;

echo "<!-- created with PXsitemap by Pixelperfect www.pixelperfect.it -->".$lb;

foreach ($include_pages as $include_page) {
	$pages[] = $include_page;
}

$i = 0;
foreach ($pages as $page) {
	if(!in_array($page, $exclude_pages)) {
		echo $lt.'url'.$gt.$lb;
		
		echo $lt.'loc'.$gt;
		echo $page;
		echo $lt.$sl.'loc'.$gt.$lb;
		
		foreach ($default_properties as $property => $default_value) {
			echo $lt.$property.$gt;
			if($property == 'priority' && strpos(' '.$default_value, 'auto')) {
				$auto_value = 1-((substr_count($page,'/') - (2 + str_replace('auto','',$default_value))) * 0.2);
				echo (isset($custom_properties[$page][$property]) ? $custom_properties[$page][$property] : ($i == 0 ? 1 : ($auto_value > 0 ? $auto_value : 0.1)));
			} else {
				echo (isset($custom_properties[$page][$property]) ? $custom_properties[$page][$property] : $default_value);
			}
			echo $lt.$sl.$property.$gt.$lb;
		}
		echo $lt.$sl.'url'.$gt;
	}
$i ++;
}
echo $lt.$sl.'urlset'.$gt.$lb;

?>
