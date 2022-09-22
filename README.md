# Custom-Caching

## Purpose

These scripts were written to cache the html files rendered by ExpressionEngine's Templates.
Put below php code in the EE template, then it will create a cached file in the $cache_path (i.e. /var/www/ee/new_page_caching) followed by $request_uri

## Usage
```php
<?php
	require_once ('/var/www/ee/scripts/keephpcache/KeePHPCache.php');

	$cache          = new KeePHPCache();
	$exp_interval   = 200;										// Expiration interval: minutes
	$request_uri    = '/_component/video_single/{segment_2}';	// Template file that you want to cache

	$exists = $cache->cacheExists($request_uri);
	$isExpired = $cache->isCacheExpired($request_uri, $exp_interval);

	// IF cache file exists
	// AND cache file is NOT expired
	if($exists && !$isExpired) {
		// Return cached contents by getting the location of cache file (/var/www/ee/new_page_caching/$request_uri/index.html)
		include($cache->getRealCacheFilePath($request_uri));
		echo "<!-- End of Cached contents at " . $request_uri . "... -->";
	}
	else {
		// Attempt to write page content into a cache file
		$result = $cache->writeCacheFile($request_uri);

		// Write failed; redirect to 404 template
		if($result === false) {
		?>
			{redirect="404"}
		<?php
		}
		// Write succeeded; display cached result
		else {
			echo $result;
			echo "<!-- End of Live contents at " . $request_uri . "... -->";
			echo $cache->writeDB('{current_url}', $request_uri);
		}
	}
	echo PHP_EOL . '<!-- {current_url} -->';
```
