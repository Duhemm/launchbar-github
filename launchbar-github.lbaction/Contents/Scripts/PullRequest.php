<?php

class PullRequest {

	private $repo;
	private $url;
	private $comments_url;
	private $html_url;
	private $number;
	private $title;
	private $state;
	private $body;

	public function __construct($repo, $rawPull) {
		$this->repo = $repo;

		$this->url = $rawPull->url;
		$this->comments_url = $rawPull->comments_url;
		$this->html_url = $rawPull->html_url;
		$this->number = $rawPull->number;
		$this->title = $rawPull->title;
		$this->state = $rawPull->state;
		$this->body = $rawPull->body;
	}

	public function display($linkToGithub = false) {
		$output = array(
			'title' => '#' . $this->number . ': ' . $this->title,
			'subtitle' => $this->body,
			'icon' => $this->state == 'open' ? 'not-merged.png' : 'merged.png'
		);

		if($linkToGithub) {
			$output['url'] = $this->html_url;
		} else {
			$output['action'] = 'default.php';
			$output['actionArgument'] = array(
				'user' => $this->repo->getUser()->getName(),
				'repo' => $this->repo->getName(),
				'action' => 'showIssue', // Not a typo, all PRs are associated to an issue on GH
				'issue' => $this->number
			);
			$output['actionReturnsItems'] = true;
		}

		return $output;
	}
}