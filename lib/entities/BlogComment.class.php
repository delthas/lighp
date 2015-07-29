<?php
namespace lib\entities;

class BlogComment extends \core\Entity {
	protected $authorPseudo, $authorEmail, $authorWebsite, $content, $postName, $creationDate;

	// SETTERS //

	public function setAuthorPseudo($pseudo) {
		if (!is_string($pseudo) || empty($pseudo)) {
			throw new \InvalidArgumentException('Invalid blog comment author pseudo');
		}

		$this->authorPseudo = $pseudo;
	}

	public function setAuthorEmail($email) {
		if (!is_string($email)) {
			throw new \InvalidArgumentException('Invalid blog comment author email');
		}

		$this->authorEmail = $email;
	}

	public function setAuthorWebsite($website) {
		if (!is_string($website)) {
			throw new \InvalidArgumentException('Invalid blog comment author website');
		}

		$this->authorWebsite = $website;
	}

	public function setContent($content) {
		if (!is_string($content) || empty($content)) {
			throw new \InvalidArgumentException('Invalid blog comment content');
		}

		$this->content = $content;
	}

	public function setPostName($postName) {
		if (!is_string($postName) || empty($postName)) {
			throw new \InvalidArgumentException('Invalid blog comment post name');
		}

		$this->postName = $postName;
	}

	public function setCreationDate($creationDate) {
		if (!is_int($creationDate)) {
			throw new \InvalidArgumentException('Invalid blog comment creation date');
		}

		$this->creationDate = $creationDate;
	}

	// GETTERS //

	public function authorPseudo() {
		return $this->authorPseudo;
	}

	public function authorEmail() {
		return $this->authorEmail;
	}

	public function authorWebsite() {
		return $this->authorWebsite;
	}

	public function content() {
		return $this->content;
	}

	public function postName() {
		return $this->postName;
	}

	public function creationDate() {
		return $this->creationDate;
	}
}