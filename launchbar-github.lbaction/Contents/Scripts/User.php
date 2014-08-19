<?php

require_once('HTTPClient.php');
require_once('Repository.php');

class User {

	private $name = "";
	private $repos = array();

	public function __construct($name) {

		$this->name = $name;

		if($name == 'my') {
			// Get repos owned by the authenticated user
			$rawUserRepos = HTTPClient::getInstance()->getJSON("/user/repos");
			foreach ($rawUserRepos as $repo) {
				$this->repos[] = new Repository($this, $repo);
			}

			// Get repos owned by any organization the authenticated user belongs to
			$rawOrganizations = HTTPClient::getInstance()->getJSON("/user/orgs");
			foreach ($rawOrganizations as $org) {
				$rawOrgRepos = HTTPClient::getInstance()->getJSON("/orgs/" . $org->login . "/repos");

				foreach ($rawOrgRepos as $repo) {
					$this->repos[] = new Repository($this, $repo);
				}
			}
		} else {
			$rawUserRepos = HTTPClient::getInstance()->getJSON("/users/" . $name . "/repos");

			foreach ($rawUserRepos as $repo) {
				$this->repos[] = new Repository($this, $repo);
			}
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