<?php

require_once('HTTPClient.php');
require_once('Repository.php');
require_once('MoreLink.php');

class User {

	private $name = "";
	private $repos = array();

	public function __construct($name) {

		$this->name = $name;

		if($name == 'my') {
			// Get repos owned by the authenticated user
			list($moreUserRepos, $rawUserRepos) = HTTPClient::getInstance()->getJSON("/user/repos");
			foreach ($rawUserRepos as $repo) {
				$this->repos[] = new Repository($this, $repo);
			}

			// Get repos owned by any organization the authenticated user belongs to
			list($moreOrgs, $rawOrganizations) = HTTPClient::getInstance()->getJSON("/user/orgs");
			foreach ($rawOrganizations as $org) {
				list($moreOrgRepos, $rawOrgRepos) = HTTPClient::getInstance()->getJSON("/orgs/" . $org->login . "/repos");

				foreach ($rawOrgRepos as $repo) {
					$this->repos[] = new Repository($this, $repo);
				}
			}

			if($moreUserRepos || $moreOrgs || $moreOrgRepos) {
				$userRealName = $repos[0]->owner_name;
				$this->repos[] = new MoreLink("https://github.com/$userRealName?tab=repositories", "View more on GitHub");
			}

		} else {
			list($moreUserRepos, $rawUserRepos) = HTTPClient::getInstance()->getJSON("/users/" . $name . "/repos");

			foreach ($rawUserRepos as $repo) {
				$this->repos[] = new Repository($this, $repo);
			}

			if($moreUserRepos) {
				$this->repos[] = new MoreLink("https://github.com/$name?tab=repositories", "View more on GitHub");
			}
		}
	}

	public function getName() {
		return $this->name;
	}

	public function getReposNamed($name) {
		$output = array();

		foreach($this->repos as $repo) {
			if($repo instanceOf Repository && stripos($repo->getName(), $name) !== false) {
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
			if($repo instanceOf Repository && $repo->getName() == $name) $result[] = $repo;
		}

		if(empty($result))
			return NULL;
		else
			return $result[0];
	}

}