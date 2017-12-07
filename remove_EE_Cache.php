<?php

	//lookup table
	const DB_SERVER	= "nacd-db.c3zfiehi4eja.us-east-1.rds.amazonaws.com";
	const DB_USER		= "root";
	const DB_PASSWORD	= "na32750cD";
	const DB_NAME		= "ee";

	require_once('/var/www/ee/scripts/keephpcache/http_response_code.php');

	/**
	 * @param $responseObject PHP object that will be encoded into JSON
	 * Sends encoded JSON response back to client and ends script execution
	 */
	function sendJSON($responseObject) {
		header("Content-Type: application/json");
		$encodedJSON = json_encode($responseObject);
		die($encodedJSON);
	}

	/**
	 * @param $url
	 * @return On success, string. On error, ends script execution
	 * Given a user's page url, queries lookup table to retrieve the corresponding
	 * cached file's path.
	 */
	function lookupCachePath($url) {
		$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

		// Check connection, end execution if error occurs
		if (mysqli_connect_errno()) {
			http_response_code(500);
			$json = array();
			$json["result"] = "error";
			$json["status"] = 500;
			$json["params_received"] = array(
				"url" => $_POST["url"]
			);
			$json["msg"] = "Could not connect to database: " . mysqli_connect_error();
			$mysqli->close();
			sendJSON($json);
		}

		$url = $mysqli->real_escape_string($url);// Escapes special characters
		$query = "SELECT * FROM nc_cache_map WHERE url = '$url'";

		//Execute Query, end execution if error occurs
		if (($result = $mysqli->query($query)) === FALSE) {
			http_response_code(500);
			$json = array();
			$json["result"] = "error";
			$json["status"] = 500;
			$json["params_received"] = array(
				"url" => $_POST["url"]
			);
			$json["msg"] = "DB Query Error: $mysqli->error";
			$result->close();
			$mysqli->close();
			sendJSON($json);
		}

		//End execution if no matches are found for the given url
		if ($result->num_rows == 0) {
			http_response_code(404);
			$json = array();
			$json["result"] = "error";
			$json["msg"] = "Cached file not found for the given url.";
			$json["status"] = 404;
			$json["params_received"] = array(
				"url" => $_POST["url"]
			);
			$result->close();
			$mysqli->close();
			sendJSON($json);
		}

		//Return the corresponding cached file's path.
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$result->close();
		$mysqli->close();
		return $row['file_path'];
	}


	/**
	 * Sets Access-Control-Allow-Origin for any requests originating from
	 * the Northland calendar site on Localist
	 */
	$http_origin = $_SERVER['HTTP_ORIGIN'];
	if ($http_origin == "http://duzzit.northlandchurch.org")
	{
		header("Access-Control-Allow-Origin: $http_origin");
	}

	// Check for each of the required POST parameters
	if(!empty($_POST) && isset($_POST["url"])) {

		$pathToCacheFile = lookupCachePath($_POST["url"]);

		// Import custom caching library
		require_once ('/var/www/ee/scripts/keephpcache/KeePHPCache.php');
		$cache = new KeePHPCache();

		// Confirm that cache already exists
		if($cache->cacheExistsByURL($pathToCacheFile)){

			// Cached file removal succeeds
			if($cache->removeCacheFile($pathToCacheFile)) {
				http_response_code(200);
				$json = array();
				$json["result"] = "success";
				$json["msg"] = "Cached file removed.";
				$json["status"] = 200;
				$json["params_received"] = array(
					"url" => $_POST["url"]
				);
				sendJSON($json);
			}
			// Cached file removal fails
			else {
				http_response_code(500);
				$json = array();
				$json["result"] = "error";
				$json["msg"] = "Attempt to remove cached file failed.";
				$json["status"] = 500;
				$json["params_received"] = array(
					"url" => $_POST["url"]
				);
				sendJSON($json);
			}
		}
		// Cached file does not exist
		else {
			http_response_code(404);
			$json = array();
			$json["result"] = "error";
			$json["msg"] = "Cached file does not exist.";
			$json["status"] = 404;
			$json["params_received"] = array(
				"url" => $_POST["url"]
			);
			sendJSON($json);
		}
	}
	// Missing one or more required parameters
	else {
		http_response_code(400);
		$json = array();
		$json["result"] = "error";
		$json["msg"] = "Missing one or more required parameters.";
		$json["status"] = 400;
		$json["params_received"] = array(
			"url" => $_POST["url"]
		);
		sendJSON($json);
	}
?>