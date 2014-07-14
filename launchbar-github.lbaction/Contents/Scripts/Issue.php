<?php

require_once('HTTPClient.php');
require_once('Comment.php');

class Issue {

	private $repo;
	private $url;
	private $comments_url;
	private $number;
	private $title;
	private $state;
	private $body;

	public function __construct($repo, $rawIssue) {
		$this->repo = $repo;

		$this->comments_url = $rawIssue->comments_url;
		$this->html_url = $rawIssue->html_url;
		$this->number = $rawIssue->number;
		$this->title = $rawIssue->title;
		$this->state = $rawIssue->state;
		$this->body = $rawIssue->body;
	}

	public function display($linkToGithub = false) {
		$output = array(
			'title' => "#" . $this->number . ": " . $this->title,
			'subtitle' => $this->body,
			'icon' => $this->state == "open" ? "open.png" : "closed.png",
		);

		if($linkToGithub) {
			$output['url'] = $this->html_url;
		} else {
			$output['action'] = 'default.php';
			$output['user'] = $this->repo->getUser()->getName();
			$output['repo'] = $this->repo->getName();
			$output['act'] = 'showIssue';
			$output['issue'] = '#' . $this->number;
			$output['actionReturnsItems'] = true;
		}

		return $output;
	}

	public function getComments() {
		$rawComments = HTTPClient::getInstance()->getJSON("/repos/" . $this->repo->getFullName() . "/issues/" . $this->number . "/comments");
		$output = array();

		foreach($rawComments as $rawComment) {
			$output[] = new Comment($this, $rawComment);
		}

		return $output;
	}
}