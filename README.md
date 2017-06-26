# Custom-Caching

## Purpose

These scripts were written to cache the html files rendered by ExpressionEngine's Templates.

## Usage
```
<?php
	require_once ('/var/www/ee/scripts/keephpcache/KeePHPCache.php');

	$cache			= new KeePHPCache();
	$exp_interval 	= 200;										// Expiration interval: minutes
	$request_uri 	= '/_component/video_single/{segment_2}';	// Template file that you want to cache

	// Check if the cache file is expired
	if (!$cache->isCacheExpired($request_uri, $exp_interval)) {
		// Return cached contents by getting the location of cache file (/var/www/ee/new_page_caching/$request_uri/index.html)
		include($cache->getRealCacheFilePath($request_uri));
		echo "<!-- End of Cached contents at " . $request_uri . "... -->";
	} else {
		// Write content into a cache file and display it
		echo $cache->writeCacheFile($request_uri);
		echo "<!-- End of Live contents at " . $request_uri . "... -->";
		echo $cache->writeDB('{current_url}', $request_uri);
	}
	echo PHP_EOL . '<!-- {current_url} -->';
```
