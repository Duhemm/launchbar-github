<?php

class MoreLink {

	private $url;
	private $title;

	public function __construct($url, $title) {
		$this->url = $url;
		$this->title = $title;
	}

	public function display() {
		return array(
			'title' => $this->title,
			'subtitle' => $this->url,
			'icon' => 'github.png',
			'url' => $this->url
		);
	}

}