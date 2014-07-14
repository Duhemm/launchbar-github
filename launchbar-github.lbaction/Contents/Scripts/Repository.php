<?php

require_once('HTTPClient.php');
require_once('Issue.php');
require_once('PullRequest.php');
require_once('Branch.php');

class Repository {

	private $user;

	private $html_url;
	private $name;
	private $full_name;
	private $description;
	private $has_issues;

	public function __construct($user, $rawRepo) {
		$this->user = $user;

		$this->html_url = $rawRepo->html_url;
		$this->name = $rawRepo->name;
		$this->full_name = $rawRepo->full_name;
		$this->description = $rawRepo->description;
		$this->has_issues = $rawRepo->has_issues;
		$this->has_wiki = $rawRepo->has_wiki;

	}

	public function getHtmlUrl() {
		return $this->html_url;
	}

	public function getName() {
		return $this->name;
	}

	public function getFullName() {
		return $this->full_name;
	}

	public function getUser() {
		return $this->user;
	}

	public function display() {
		$output = array(
			'title' => $this->full_name,
			'subtitle' => $this->description,
			'icon' => 'repo.png',
			'action' => 'default.php',
			'user' => $this->user->getName(),
			'repo' => $this->name,
			'act' => 'showActions',
			'actionReturnsItems' => true
		);

		return $output;
	}

	public function getIssue($number) {
		$rawIssue = HTTPClient::getInstance()->getJSON("/repos/" . $this->full_name . "/issues/" . $number);

		return new Issue($this, $rawIssue);
	}

	public function getIssues() {
		$rawIssues = HTTPClient::getInstance()->getJSON("/repos/" . $this->full_name . "/issues?state=all");

		$output = array();

		foreach($rawIssues as $rawIssue) {
			$output[] = new Issue($this, $rawIssue);
		}

		return $output;
	}

	public function showIssues() {

		$issues = $this->getIssues();

		$output = array();

		// Display a link to all issues
		$output[] = array(
			'title' => "View all issues",
			'subtitle' => $this->html_url . "/issues",
			'icon' => 'link.png',
			'url' => $this->html_url . "/issues"
		);

		foreach($issues as $issue) {
			$output[] = $issue->display(false);
		}

		return $output;
	}

	public function getPullRequests() {
		$rawPulls = HTTPClient::getInstance()->getJSON("/repos/" . $this->full_name . "/pulls?state=all");

		$output = array();

		foreach($rawPulls as $rawPull) {
			$output[] = new PullRequest($this, $rawPull);
		}

		return $output;
	}

	public function showPullRequests() {

		$pulls = $this->getPullRequests();

		$output = array();

		// Display a link to all PRs
		$output[] = array(
			'title' => "View all pull requests",
			'subtitle' => $this->html_url . "/pulls",
			'icon' => 'link.png',
			'url' => $this->html_url . "/pulls"
		);

		foreach ($pulls as $pull) {
			$output[] = $pull->display(false);
		}

		return $output;
	}

	public function getBranches() {
		$rawBranches = HTTPClient::getInstance()->getJSON("/repos/" . $this->full_name . "/branches");

		$output = array();

		foreach($rawBranches as $rawBranch) {
			$output[] = new Branch($this, $rawBranch);
		}

		return $output;
	}

	public function showBranches() {

		$branches = $this->getBranches();

		$output = array();

		foreach($branches as $branch) {
			$output[] = $branch->display();
		}

		return $output;

	}

	function showActions() {

		$actions = array();

		$actions[] = array(
			'title' => "View repository on GitHub",
			'subtitle' => $this->html_url,
			'icon' => 'github.png',
			'url' => $this->html_url
		);

		if($this->has_issues) {
			$actions[] = array(
				'title' => "Issues",
				'icon' => 'issues.png',
				'action' => 'default.php',
				'user' => $this->user->getName(),
				'repo' => $this->name,
				'act' => 'issues',
				'actionReturnsItems' => true
			);
		}

		$actions[] = array(
			'title' => "Pull requests",
			'icon' => 'pulls.png',
			'action' => 'default.php',
			'user' => $this->user->getName(),
			'repo' => $this->name,
			'act' => 'pulls',
			'actionReturnsItems' => true
		);

		$actions[] = array(
			'title' => "Branches",
			'icon' => 'branches.png',
			'action' => 'default.php',
			'user' => $this->user->getName(),
			'repo' => $this->name,
			'act' => 'branches',
			'actionReturnsItems' => true
		);

		$actions[] = array(
			'title' => "Pulse",
			'subtitle' => $this->html_url . "/pulse",
			'icon' => 'pulse.png',
			'url' => $this->html_url . "/pulse"
		);

		$actions[] = array(
			'title' => "Graphs",
			'subtitle' => $this->html_url . "/graphs",
			'icon' => 'graphs.png',
			'url' => $this->html_url . "/graphs"
		);

		$actions[] = array(
			'title' => "Network",
			'subtitle' => $this->html_url . "/network",
			'icon' => 'merged.png',
			'url' => $this->html_url . "/network"
		);

		if($this->has_wiki) {
			$actions[] = array(
				'title' => "Repository wiki",
				'subtitle' => $this->html_url . "/wiki",
				'icon' => 'wiki.png',
				'url' => $this->html_url . "/wiki"
			);
		}

		return $actions;
	}

}