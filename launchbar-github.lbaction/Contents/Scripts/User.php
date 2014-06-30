<?php

require_once('HTTPClient.php');
require_once('Repository.php');

class User {

	private $name = "";
	private $repos = array();

	public function __construct($name) {

		if($name == 'my') {
			$url = "/user/repos";
		} else {
			$url = "/users/" . $name . "/repos";
		}

		$rawUserRepos = HTTPClient::getInstance()->getJSON($url);
		$this->name = $name;

		foreach ($rawUserRepos as $repo) {
			$this->repos[] = new Repository($this, $repo);
		}
	}

	public function getName() {
		return $this->name;
	}

	public function getReposNamed($name) {
		$output = array();

		foreach($this->repos as $repo) {
			if(stripos($repo->getName(), $name) !== false) {
				$output[] = $repo;
			}
		}

		return $output;
	}

	public function getRepos() {
		return $this->repos;
	}

	public function getRepo($name) {
		$result = array();

		foreach($this->repos as $repo) {
			if($repo->getName() == $name) $result[] = $repo;
		}

		if(empty($result))
			return NULL;
		else
			return $result[0];
	}

}