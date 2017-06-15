<?php
	require_once('./http_response_code.php');

	/**
	 * Sets Access-Control-Allow-Origin for any requests originating from
	 * the Northland calendar site on Localist
	 */
	$http_origin = $_SERVER['HTTP_ORIGIN'];
	if ($http_origin == "http://duzzit.northlandchurch.org")
	{
	   header("Access-Control-Allow-Origin: $http_origin");
	}

	$json = array();//Initialize the JSON response that will be sent back to client

	// check for each of the required GET parameters
	if(!empty($_POST) || isset($_POST["emailaddress"]) {
		//import custom caching library
		require_once ('/var/www/ee/scripts/keephpcache/KeePHPCache.php');
		$cache = new KeePHPCache();

		//confirm that cache already exists
		if($cache->cacheExists($_POST["url"])){

			//cached file removal succeeds
			if($cache->removeCacheFile($_POST["url"])) {
				http_response_code(200);
				$json = array(
					array(
						"msg" => "Cached file removed."
					)
				);
			}
			//cached file removal fails
			else {
				http_response_code(500);
				$json = array(
					array(
						"error" => "Cached file could not be removed.",
						"params_received" => array(
								"url" => $_POST["url"]
						)
					)
				);
			}
		}
		// cached file does not exist
		else {
			http_response_code(404);
			$json = array(
				array(
					"error" => "Cached file not found.",
					"params_received" => array(
							"url" => $_POST["url"]
					)
				)
			);
		}
	}
	// Missing one or more required parameters
	else {
		http_response_code(400);
		$json = array(
			array(
			"error" => "Missing one or more required parameters.",
			"params_received" => array(
					"url" => $_POST["url"]
				)
			)
		);
	}

	// send response back to script and end script execution
	header("Content-Type: application/json");
	$encoded = json_encode($json);
	die($encoded);
?>