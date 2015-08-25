<?php

namespace lib\entities;

class Notification extends \core\Entity {
	protected $title, $description, $receiver, $actions;

	// SETTERS //

	public function setTitle($title) {
		if (!is_string($title) || empty($title)) {
			throw new InvalidArgumentException('Invalid notification title: empty title');
		}
		$this->title = $title;
	}

	public function setDescription($desc) {
		if (!is_string($desc) && !empty($desc)) {
			throw new InvalidArgumentException('Invalid notification description: not a string');
		}
		$this->description = $desc;
	}

	public function setReceiver($username) {
		if (!is_string($username) && !empty($username)) {
			throw new InvalidArgumentException('Invalid notification receiver: not a username');
		}
		$this->receiver = $username;
	}

	public function setActions(array $actions) {
		$this->actions = $actions;
	}

	// GETTERS //

	public function title() {
		return $this->title;
	}

	public function description() {
		return $this->description;
	}

	public function receiver() {
		return $this->receiver;
	}

	public function actions() {
		return $this->actions;
	}
}
