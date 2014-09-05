<?php

class HTTPClient {

	private static $instance = null;
	private static $ONE_HOUR = 3600;
	private static $HTTP_OK = 200;
	private static $AUTO_PAGE_LIMIT = 5;
	private $pdo = null;
	private $cookies = null;
	private $hostname = "https://api.github.com";
	private $token = "";

	private $dbSchema = "
		CREATE TABLE settings (
			'TOKEN' VARCHAR(255)
		);
		CREATE TABLE cache (
			'ID' INTEGER PRIMARY KEY AUTOINCREMENT,
			'URL' VARCHAR(255) UNIQUE,
			'HASMORE' VARCHAR(255) UNIQUE,
			'RESULTS' LONGTEXT,
			'INSERTED' INTEGER
		)
	";

	public static function getInstance() {
		if(HTTPClient::$instance == null) {
			HTTPClient::$instance = new HTTPClient();
		}

		return HTTPClient::$instance;
	}

	/**
	 * Performs an HTTP request and returns the raw body.
	 * If the result spans over multiple pages (Link header), then
	 * it automatically gets pages up to AUTO_PAGE_LIMIT and appends
	 * the results.
	 * @param  String  $url      URL
	 * @param  Integer $status   Status code of the request
	 * @param  array   $POSTdata Fields and their value to add in a POST request
	 * @return array             2-elements array where first element is the address
	 *                           of the next batch if there are more, false o/w. The
	 *                           second element is the result of the request.
	 */
	private function request($url, &$status, $POSTdata = array()) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);

        if(!empty($this->token))
        	curl_setopt($ch, CURLOPT_USERPWD, $this->token . ":x-oauth-basic");

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'User-Agent: GitHub for LaunchBar'
		));

		if(!empty($POSTdata)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($POSTdata));
		}

		list($header, $body) = explode("\r\n\r\n", curl_exec($ch), 2);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if(preg_match("@^Link:.+<(.+?)>; rel=\"next\".+$@m", $header, $matches) && preg_match("@[?&]page=(\d+)@", $matches[1], $pageNumber)) {
			if($pageNumber[1] > HTTPClient::$AUTO_PAGE_LIMIT)
				return array($matches[1], $body);
			else {
				list($hasMore, $nextBatch) = $this->request($matches[1], $status, $POSTdata);

				// We assume that if we receive a Link header, then the data is a big JSON array.
				// We need to concatenate the arrays : [ r1, r2 ] [ r3, r4 ] ==> [ r1, r2, r3, r4 ]
				$concatenatedArrays = substr($body, 0, -1) . ", " . substr($nextBatch, 1);
				return array($hasMore, $concatenatedArrays);
			}
		}
		else
			return array(false, $body);
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

			list($hasMore, $result) = $this->request($this->hostname . $url, $status);

			if($status == HTTPClient::$HTTP_OK) {
				$insert = $this->pdo->prepare("INSERT INTO cache VALUES(NULL, ?, ?, ?, ?)");
				$insert->execute(array($url, ($hasMore ? $hasMore : ""), $result, time()));
			} else {
				echo json_encode(array(
					'title' => 'Query failed : ' . $url,
					'subtitle' => 'Got status ' . $status . ' but expected ' . HTTPClient::$HTTP_OK
				));

				exit(0);
			}
		} else {
			$hasMore = $result['HASMORE'];
			$result = $result['RESULTS'];
		}

		return array($hasMore, json_decode($result, $associative));
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

	public function updateDB() {
		$this->pdo->exec("DROP TABLE settings");
		$this->pdo->exec("DROP TABLE cache");

		$this->pdo->exec($this->dbSchema);

		$this->updateToken($this->token);
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
			$this->pdo->exec($this->dbSchema);

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