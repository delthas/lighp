<?php

namespace lib\entities;

use InvalidArgumentException;
use core\Entity;

class User extends Entity {
	protected $username, $password, $email;

	// SETTERS //

	public function setUsername($username) {
		if (!is_string($username) || empty($username)) {
			throw new InvalidArgumentException('Invalid user username');
		}
		if ($username === 'me') {
			throw new InvalidArgumentException('Invalid user username: this username is reserved');
		}
		$this->username = $username;
	}

	public function setPassword($password) {
		if (!is_string($password) || empty($password)) {
			throw new InvalidArgumentException('Invalid user password');
		}
		$this->password = $password;
	}

	public function setEmail($email) {
		if (!is_string($email) && $email !== null) {
			throw new InvalidArgumentException('Invalid user email: not a string');
		}
		if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new InvalidArgumentException('Invalid user email: not a valid email address');
		}
		$this->email = $email;
	}

	// GETTERS //

	public function username() {
		return $this->username;
	}

	public function password() {
		return $this->password;
	}

	public function email() {
		return $this->email;
	}


	public function toArray() {
		// For security reasons, do not include password
		$data = parent::toArray();
		unset($data['password']);
		return $data;
	}
}