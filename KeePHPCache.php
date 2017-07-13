<?php
class KeePHPCache
{
	public $host_name	= 'https://www.northlandchurch.net';
	public $roku_host_name	= 'http://www.northlandchurch.net/index.php';
	public $cache_path 	= '/var/www/ee/new_page_caching';
	public $cache_file 	= '/index.html';
	public $cache_xml_file 	= '/index.xml';

	public $DB_SERVER	= "nacd-db.c3zfiehi4eja.us-east-1.rds.amazonaws.com";
	public $DB_USER		= "root";
	public $DB_PASSWORD	= "na32750cD";
	public $DB_NAME		= "ee";

	/**
	 * @var array
	 */
	private $config = array(
		'default_chmod' => 0777, // 0777 , 0666, 0644
	);


	/**
	 * @param $templatename
	 * @return full cache file path (e.g. /var/www/ee/new_page_caching/template.group/template/index.html)
	*/
    public function getRealCacheFilePath($templatename)
	{
		return $this->cache_path . $templatename . $this->cache_file;
	}

		
	/**
	 * @param $templatename
	 * @return full cache file path (e.g. /var/www/ee/new_page_caching/template.group/template/index.xml)
	*/
    public function getRealRokuCacheFilePath($templatename)
	{
		return $this->cache_path . $templatename . $this->cache_xml_file;
	}

		
	/**
	 * @param $templatename
	 * @return string of page
	*/
    public function writeCacheFile($templatename)
	{
		// URL: https://www.northlandchurch.net/_component/kee_video_featured
		$url	= $this->host_name . $templatename;			
		// File: /var/www/ee/new_page_caching/_component/kee_video_featured/index.html
		$file 	= $this->cache_path . $templatename . $this->cache_file;				

		
		$data = @file_get_contents($url);

		$fp = fopen($file, 'w'); 			// open cache file
		fwrite($fp, $data); 				// create new cache file
		fclose($fp); 						// close cache file

		return $data;
	}


	/**
	 * @param $templatename
	 * @return string of page
	*/
    public function writeCacheFileforRoku($templatename)
	{
		// URL: http://www.northlandchurch.net/index.php/{template_name}
		$url	= $this->roku_host_name . $templatename;			
		// File: /var/www/ee/new_page_caching/feed/{template_path}/index.html
		$file 	= $this->cache_path . $templatename . $this->cache_xml_file;				

		
		$data = @file_get_contents($url);

		$fp = fopen($file, 'w'); 			// open cache file
		fwrite($fp, $data); 				// create new cache file
		fclose($fp); 						// close cache file

		return $data;
	}


	/**
	 * @param $templatename
	 * @return string of page
	*/
    public function writeRokuCacheFile($templatename)
	{
		// URL: http://www.northlandchurch.net/index.php/{template_name}
		$url	= $this->roku_host_name . $templatename;			
		// File: /var/www/ee/new_page_caching/feed/{template_path}/index.xml
		$file 	= $this->cache_path . $templatename . $this->cache_xml_file;				

		
		$data = @file_get_contents($url);

		$fp = fopen($file, 'w'); 			// open cache file
		fwrite($fp, $data); 				// create new cache file
		fclose($fp); 						// close cache file

		return $data;
	}


	/**
	 * @param $templatename
	 * @return string of page
	*/
    public function writeDB($realurl, $templatename)
	{
		// URL: 'https://www.northlandchurch.net' . $templatename
		$url	= $this->host_name . $templatename;
		// File: '/var/www/ee/new_page_caching' . $templatename . '/index.html'
		$file 	= $this->cache_path . $templatename . $this->cache_file;


		$mysqli = new mysqli($this->DB_SERVER, $this->DB_USER, $this->DB_PASSWORD, $this->DB_NAME);

		// Check connection
		if (mysqli_connect_errno())
		{
			return '<!-- Could not connect: ' . mysqli_connect_error() . ' -->';
		}


		$query = "SELECT * FROM nc_cache_map WHERE url = '" . $realurl . "'";

		if (($result = $mysqli->query($query)) === FALSE)
		{
			return '<!-- Error on Query: ' . $query . ' -->';
		}

		if ($result->num_rows > 0)
		{
			$row = $result->fetch_array(MYSQLI_ASSOC);

			// Update the row
			$query1 = "UPDATE nc_cache_map SET file_path = '" . $file . "' WHERE id = " . $row['id'];
		}
		else
		{
			// Insert a row
			$query1 = sprintf("INSERT INTO nc_cache_map (url, file_path) VALUES ('%s', '%s')", $realurl, $file);
		}
		$result->close();

		if ($mysqli->query($query1) === FALSE)
		{
			return '<!-- Error on query: ' . $mysqli->error . ' -->';
		}


		$mysqli->close();
		return '<!-- Success to insert/update -->';
	}



	/**
	 * @param $url
	 * @return TRUE on success or FALSE on failure
	 */
	public function removeCacheFile($url) {
		if(!file_exists($url)) {
			return false;
		}
		else {
			return unlink($url);
		}
	}


	/**
	 * @param $url
	 * @return TRUE on success or FALSE on failure
	 */
	public function cacheExists($url) {
		return file_exists($url);
	}


	/**
	 * @param $templatename
	 * @param int $time
	 * @return bool
	 * @throws \Exception
	*/
    public function isCacheExpired($templatename, $time = 60)
	{
		$expired 	= true;
		$path 		= $this->cache_path . $templatename;		// Path: /var/www/ee/new_page_caching/_component/kee_past_news
		$file 		= $path . $this->cache_file;				// File: /var/www/ee/new_page_caching/_component/kee_past_news/index.html
		$interval 	= '+' . $time . ' minutes';					// Expiration Interval: '+10 minutes'

		try 
		{
			////////////////////////////////////////////////////////////////////////////////
			// Check if cache file exists, Create a cache file if it does not exist
			////////////////////////////////////////////////////////////////////////////////
			if ($this->checkCache($path))
			{
				$current_time 	= time();
				$cache_last_mod	= filemtime($file); 	// Time when the cache file was last modified

				// Check if cache file hasn't expired yet
				if($current_time < strtotime($interval, $cache_last_mod))
				{
					$expired = false;
				}
				else
				{
					$expired = true;
				}
			}
			else
			{
				$expired = true;
			}
        } 
		catch (\Exception $e) 
		{
			echo $e->getMessage() . "<BR>";
		}
		
		return $expired;
	}
	
	

	/**
	 * @param $templatename
	 * @param int $time
	 * @return bool
	 * @throws \Exception
	*/
    public function isRokuCacheExpired($templatename, $time = 60)
	{
		$expired 	= true;
		$path 		= $this->cache_path . $templatename;		// Path: /var/www/ee/new_page_caching/_component/kee_past_news
		$file 		= $path . $this->cache_xml_file;				// File: /var/www/ee/new_page_caching/_component/kee_past_news/index.xml
		$interval 	= '+' . $time . ' minutes';					// Expiration Interval: '+10 minutes'

		try 
		{
			////////////////////////////////////////////////////////////////////////////////
			// Check if cache file exists, Create a cache file if it does not exist
			////////////////////////////////////////////////////////////////////////////////
			if ($this->checkRokuCache($path))
			{
				$current_time 	= time();
				$cache_last_mod	= filemtime($file); 	// Time when the cache file was last modified

				// Check if cache file hasn't expired yet
				if($current_time < strtotime($interval, $cache_last_mod))
				{
					$expired = false;
				}
				else
				{
					$expired = true;
				}
			}
			else
			{
				$expired = true;
			}
        } 
		catch (\Exception $e) 
		{
			echo $e->getMessage() . "<BR>";
		}
		
		return $expired;
	}
	

	/**
	 * @param $path
	 * @return bool
	 * @throws \Exception
	*/
    private function checkCache($path)
	{
		$file = $path . $this->cache_file;
		//////////////////////////////////////////////////////////////
		// Check if cache file exists, 
		// Create a cache file if it does not exist
		//////////////////////////////////////////////////////////////
		if (file_exists($file))
		{
			return true;
		}
		else 
		{
			//////////////////////////////////////////////////////////////
			// Check the directory, Create a $path if does not exist
			//////////////////////////////////////////////////////////////
			$dir = @opendir($path);
			if (!$dir) 
			{
				if (!@mkdir($path, self::__setChmodAuto($this->config), true))
				{
					throw new Exception("Can't create path:" . $path, 93);
//					die('Failted to create folders: ' . $path . PHP_EOL);
				}
			}

			$f = fopen($path . $this->cache_file, 'w');
			fclose($f);
			
			return false;
		}
	}
	
	
    private function checkRokuCache($path)
	{
		$file = $path . $this->cache_xml_file;
		//////////////////////////////////////////////////////////////
		// Check if cache file exists, 
		// Create a cache file if it does not exist
		//////////////////////////////////////////////////////////////
		if (file_exists($file))
		{
			return true;
		}
		else 
		{
			//////////////////////////////////////////////////////////////
			// Check the directory, Create a $path if does not exist
			//////////////////////////////////////////////////////////////
			$dir = @opendir($path);
			if (!$dir) 
			{
				if (!@mkdir($path, self::__setChmodAuto($this->config), true))
				{
					throw new Exception("Can't create path:" . $path, 93);
//					die('Failted to create folders: ' . $path . PHP_EOL);
				}
			}

			$f = fopen($path . $this->cache_xml_file, 'w');
			fclose($f);
			
			return false;
		}
	}
	
	

    /**
     * @param $config
     * @return int
     */
    public static function __setChmodAuto($config)
//    private function __setChmodAuto($config)
    {
        if (!isset($config[ 'default_chmod' ]) || $config[ 'default_chmod' ] == '' || is_null($config[ 'default_chmod' ])) {
            return 0777;
        } else {
            return $config[ 'default_chmod' ];
        }
    }


	/**
	 * @param $templatename
	 * @param int $time
	 * @return string or null
	 * @throws \Exception
	*/
    public function getCacheFile($templatename, $time = 60)
	{
		$path 		= $this->cache_path . $templatename;		// Path: /var/www/ee/new_page_caching/_component/kee_past_news
		$file 		= $path . $this->cache_file;				// File: /var/www/ee/new_page_caching/_component/kee_past_news/index.html
		$interval 	= '+' . $time . ' minutes';					// Expiration Interval: '+10 minutes'

		try 
		{
			////////////////////////////////////////////////////////////////////////////////
			// Check if a cache file exists, Create a cache file if it does not exist
			////////////////////////////////////////////////////////////////////////////////
			if ($this->checkCache($path))
			{
				$current_time 	= time();
				$cache_last_mod	= filemtime($file); 	// Time when the cache file was last modified

				// Check if cache file hasn't expired yet
				if($current_time < strtotime($interval, $cache_last_mod))
				{
					include($file);
				}
				else
				{
					return null;
				}
			}
			else
			{
				return null;
			}
        } 
		catch (\Exception $e) 
		{
			echo $e->getMessage() . "<BR>" . PHP_EOL;
		}
		
		return null;
	}

	
}
?>
