<?php

class HTTPClient {

	private static $instance = null;
	private static $ONE_HOUR = 3600;
	private static $HTTP_OK = 200;
	private $pdo = null;
	private $cookies = null;
	private $hostname = "https://api.github.com";
	private $token = "";

	public static function getInstance() {
		if(HTTPClient::$instance == null) {
			HTTPClient::$instance = new HTTPClient();
		}

		return HTTPClient::$instance;
	}

	/**
	 * Performs an HTTP request and returns the raw body
	 * @param  String  $url      URL
	 * @param  Integer $status   Status code of the request
	 * @param  array   $POSTdata Fields and their value to add in a POST request
	 * @return String            Body of the HTTP response
	 */
	public function request($url, &$status, $POSTdata = array()) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);

        if(!empty($this->token))
        	curl_setopt($ch, CURLOPT_USERPWD, $this->token . ":x-oauth-basic");

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'User-Agent: GitHub for LaunchBar'
		));

		if(!empty($POSTdata)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($POSTdata));
		}

		$result = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		return $result;
	}

	/**
	 * Fetches JSON from $url and returns an object or an array representing it
	 * @param  string  $url         URL from where to fetch the JSON
	 * @param  boolean $associative True to return an assoc. array, false for an object
	 * @return Object or Array      Representation of the JSON found at $URL
	 */
	public function getJSON($url, $associative = false) {

		$inCache = $this->pdo->prepare("SELECT * FROM cache WHERE URL = ?");

		$inCache->execute(array($url));
		$result = $inCache->fetch(PDO::FETCH_ASSOC);

		// Verify that the cache is not outdated
		if($result['INSERTED'] < time() - HTTPClient::$ONE_HOUR) {
			$deletion = $this->pdo->prepare("DELETE FROM cache WHERE INSERTED < ?");
			$deletion->execute(array(time() - HTTPClient::$ONE_HOUR));

			// Set to false so that we will fetch the data from the server
			$result = false;
		}

		if($result === false) {

			$result = $this->request($this->hostname . $url, $status);

			if($status == HTTPClient::$HTTP_OK) {
				$insert = $this->pdo->prepare("INSERT INTO cache VALUES(NULL, ?, ?, ?)");
				$insert->execute(array($url, $result, time()));
			} else {
				echo json_encode(array(
					'title' => 'Query failed : ' . $url,
					'subtitle' => 'Got status ' . $status . ' but expected ' . HTTPClient::$HTTP_OK
				));

				exit(0);
			}
		} else {
			$result = $result['RESULTS'];
		}

		return json_decode($result, $associative);
	}

	public function updateToken($token) {
		$this->token = $token;

		$this->pdo->prepare("DELETE FROM settings")->execute();
		$this->pdo->prepare("INSERT INTO settings VALUES (?)")->execute(array($token));

		$this->emptyCache();
	}

	public function emptyCache() {
		$this->pdo->prepare("DELETE FROM cache")->execute();
	}

	private function __construct() {

		$this->cookies = $_SERVER['LB_CACHE_PATH'] . '/cookies';

		$dbPath = $_SERVER['LB_CACHE_PATH'] . '/cache.db';
		$existed = file_exists($dbPath);

		try {
			$this->pdo = new PDO("sqlite:" . $dbPath);
		} catch(Exception $e) {
			echo "An error occurred while creating the database :\n";
			echo $e->getMessage();
			exit(1);
		}

		// If the database did not exist, create the tables
		if(!$existed) {
			$this->pdo->exec("
				CREATE TABLE settings (
					'TOKEN' VARCHAR(255)
				);

				CREATE TABLE cache (
					'ID' INTEGER PRIMARY KEY AUTOINCREMENT,
					'URL' VARCHAR(255) UNIQUE,
					'RESULTS' LONGTEXT,
					'INSERTED' INTEGER
				)
			");

			chmod($dbPath, 0600); // The DB will contain the access token, don't let others read it !
		} else {
			$query = $this->pdo->prepare("SELECT TOKEN FROM settings");
			$query->execute();
			$token = $query->fetch(PDO::FETCH_ASSOC);

			if(!empty($token)) {
				$this->token = $token['TOKEN'];
			}
		}
	}
}

?>