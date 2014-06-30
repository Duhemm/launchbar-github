<?php

class Branch {

	private $repo;
	private $name;
	private $last_commit;
	private $html_url;

	public function __construct($repo, $rawBranch) {
		$this->repo = $repo;

		$this->name = $rawBranch->name;
		$this->last_commit = $rawBranch->commit->sha;
	}

	public function display() {
		return array(
			'title' => $this->name,
			'subtitle' => $this->last_commit,
			'icon' => 'not-merged.png',
			'url' => $this->repo->getHtmlUrl() . "/tree/" . $this->name
		);
	}

}