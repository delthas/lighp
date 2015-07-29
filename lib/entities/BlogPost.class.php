<?php
namespace lib\entities;

class BlogPost extends \core\Entity {
	protected $name, $title, $content, $creationDate;

	// SETTERS //

	public function setName($name) {
		if (!is_string($name) || empty($name) || !preg_match('#^[a-zA-Z0-9-_.]+$#', $name)) {
			throw new \InvalidArgumentException('Invalid blog post name');
		}

		$this->name = $name;
	}

	public function setTitle($title) {
		if (!is_string($title) || empty($title)) {
			throw new \InvalidArgumentException('Invalid blog post title');
		}

		$this->title = $title;
	}

	public function setContent($content) {
		if (!is_string($content) || empty($content)) {
			throw new \InvalidArgumentException('Invalid blog post content');
		}

		$this->content = $content;
	}

	public function setCreationDate($creationDate) {
		if (!is_int($creationDate)) {
			throw new \InvalidArgumentException('Invalid blog post creation date');
		}

		$this->creationDate = $creationDate;
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

	public function creationDate() {
		return $this->creationDate;
	}
}