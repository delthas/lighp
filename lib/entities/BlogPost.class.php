<?php
namespace lib\entities;

use core\Entity;

class BlogPost extends Entity {
	protected $name, $title, $content, $creationDate, $publishedAt, $author, $isDraft, $tags;

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

	/**
	 * @deprecated
	 */
	public function setCreationDate($creationDate) {
		return $this->setPublishedAt($creationDate);
	}

	public function setPublishedAt($publishedAt) {
		if (!is_int($publishedAt)) {
			throw new \InvalidArgumentException('Invalid blog post publication date');
		}

		$this->publishedAt = $publishedAt;
	}

	public function setAuthor($author) {
		if (!is_string($author) || empty($author)) {
			throw new \InvalidArgumentException('Invalid blog post author');
		}

		$this->author = $author;
	}

	public function setIsDraft($isDraft) {
		if (!is_bool($isDraft)) {
			throw new \InvalidArgumentException('Invalid blog post draft value');
		}

		if ($this->isDraft === false && $isDraft === true) {
			$this->setPublishedAt(time());
		}

		$this->isDraft = $isDraft;
	}

	public function setTags($tags) {
		if (!is_array($tags)) {
			throw new \InvalidArgumentException('Invalid blog post tags');
		}

		$this->tags = $tags;
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

	/**
	 * @deprecated
	 */
	public function creationDate() {
		return $this->publishedAt();
	}

	public function publishedAt() {
		return $this->publishedAt;
	}

	public function author() {
		return $this->author;
	}

	public function isDraft() {
		return $this->isDraft;
	}

	public function tags() {
		return $this->tags;
	}


	public function toArray() {
		$data = parent::toArray();
		unset($data['creationDate']);
		return $data;
	}
}