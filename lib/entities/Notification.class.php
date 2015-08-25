<?php

namespace lib\entities;

class Notification extends \core\Entity {
	protected $title, $description, $icon, $receiver, $actions;

	// SETTERS //

	public function setTitle($title) {
		if (!is_string($title) || empty($title)) {
			throw new InvalidArgumentException('Invalid notification title: empty title');
		}
		$this->title = $title;
	}

	public function setDescription($desc) {
		if (!is_string($desc) && $desc !== null) {
			throw new InvalidArgumentException('Invalid notification description: not a string');
		}
		$this->description = $desc;
	}

	public function setIcon($icon) {
		if (!is_string($icon) && $icon !== null) {
			throw new InvalidArgumentException('Invalid notification icon: not a string');
		}
		$this->icon = $icon;
	}

	public function setReceiver($username) {
		if (!is_string($username) && $username !== null) {
			throw new InvalidArgumentException('Invalid notification receiver: not a username');
		}
		$this->receiver = $username;
	}

	public function setActions($actions) {
		if (!is_array($actions) && $actions !== null) {
			throw new InvalidArgumentException('Invalid notification actions: not an array');
		}
		
		$this->actions = $actions;
	}

	// GETTERS //

	public function title() {
		return $this->title;
	}

	public function description() {
		return $this->description;
	}

	public function icon() {
		return $this->icon;
	}

	public function receiver() {
		return $this->receiver;
	}

	public function actions() {
		return $this->actions;
	}
}
