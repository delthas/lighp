<?php
namespace lib\entities;

use core\Entity;

class Page extends Entity {
	protected $name, $title, $content;

	// SETTERS //

	public function setName($name) {
		if (!is_string($name) || empty($name) || !preg_match('#^[^/]+$#', $name)) {
			throw new \InvalidArgumentException('Invalid page name');
		}

		$this->name = $name;
	}

	public function setTitle($title) {
		if (!is_string($title) || empty($title)) {
			throw new \InvalidArgumentException('Invalid page title');
		}

		$this->title = $title;
	}

	public function setContent($content) {
		if (!is_string($content) || empty($content)) {
			throw new \InvalidArgumentException('Invalid page content');
		}

		$this->content = $content;
	}

	// GETTERS //

	public function name() {
		return $this->name;
	}

	public function title() {
		return $this->title;
	}

	public function content() {
		return $this->content;
	}
}