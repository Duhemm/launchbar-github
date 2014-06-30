<?php

class Comment {
	private $issue;
	private $html_url;
	private $user;
	private $updated_at;
	private $body;

	public function __construct($issue, $rawComment) {
		$this->issue = $issue;
		$this->html_url = $rawComment->html_url;
		$this->user = $rawComment->user->login;
		$this->updated_at = $rawComment->updated_at;
		$this->body = $rawComment->body;
	}

	public function display() {
		return array(
			'title' => $this->body,
			'subtitle' => "By " . $this->user . " " . $this->updated_at,
			'icon' => 'comment.png',
			'url' => $this->html_url
		);
	}
}