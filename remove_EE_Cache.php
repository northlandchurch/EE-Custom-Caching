<?php

	require_once('/var/www/ee/scripts/keephpcache/http_response_code.php');

	/**
	 * Sets Access-Control-Allow-Origin for any requests originating from
	 * the Northland calendar site on Localist
	 */
	$http_origin = $_SERVER['HTTP_ORIGIN'];
	if ($http_origin == "http://duzzit.northlandchurch.org")
	{
		header("Access-Control-Allow-Origin: $http_origin");
	}

	$json = array();// Initialize the JSON response that will be sent back to client

	// Check for each of the required POST parameters
	if(!empty($_POST) && isset($_POST["url"])) {

		// Import custom caching library
		require_once ('/var/www/ee/scripts/keephpcache/KeePHPCache.php');
		$cache = new KeePHPCache();

		// Confirm that cache already exists
		if($cache->cacheExists($_POST["url"])){

			// Cached file removal succeeds
			if($cache->removeCacheFile($_POST["url"])) {
				http_response_code(200);
				$json["result"] = "success";
				$json["msg"] = "Cached file removed.";
				$json["status"] = 200;
				$json["params_received"] = array(
					"url" => $_POST["url"]
				);
			}
			// Cached file removal fails
			else {
				http_response_code(500);
				$json["result"] = "error";
				$json["status"] = 500;
				$json["params_received"] = array(
					"url" => $_POST["url"]
				);
			}
		}
		// Cached file does not exist
		else {
			http_response_code(404);
			$json["result"] = "error";
			$json["msg"] = "Cached file not found.";
			$json["status"] = 404;
			$json["params_received"] = array(
				"url" => $_POST["url"]
			);
		}
	}
	// Missing one or more required parameters
	else {
		http_response_code(400);
		$json["result"] = "error";
		$json["msg"] = "Missing one or more required parameters.";
		$json["status"] = 400;
		$json["params_received"] = array(
			"url" => $_POST["url"]
		);
	}

	// Send response back to script and end script execution
	header("Content-Type: application/json");
	$encoded = json_encode($json);
	die($encoded);
?>