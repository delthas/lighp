<?php

namespace ctrl\backend\users;

use core\http\HTTPRequest;
use lib\entities\User;

class UsersController extends \core\BackController {
	protected function _addBreadcrumb($page = array()) {
		$breadcrumb = array(
			array(
				'url' => $this->app->router()->getUrl('main', 'showModule', array(
					'module' => $this->module()
				)),
				'title' => 'Utilisateurs'
			)
		);

		$this->page()->addVar('breadcrumb', array_merge($breadcrumb, array($page)));
	}

	protected function _rehashPassword(User $user, $password) {
		$manager = $this->managers->getManagerOf('users');
		$cryptoManager = $this->managers->getManagerOf('crypto');

		if ($cryptoManager->needsRehash($user['password'])) {
			$user['password'] = $cryptoManager->hashPassword($password);

			try {
				$manager->update($user);
			} catch (\Exception $e) {
				// Silently ignore error
			}
		}
	}

	public function executeLogin(HTTPRequest $request) {
		$this->page()->addVar('title', 'Connexion');

		$manager = $this->managers->getManagerOf('users');
		$cryptoManager = $this->managers->getManagerOf('crypto');

		if ($request->postExists('login-username')) {
			$username = $request->postData('login-username');
			$password = $request->postData('login-password');

			try {
				$user = $manager->getByUsername($username);
			} catch (\Exception $e) {
				sleep(3); // Delay to prevent bruteforce attacks
				$this->page()->addVar('error', 'Incorrect username or password');
				return;
			}

			if (!$cryptoManager->verifyPassword($password, $user['password'])) {
				sleep(3); // Delay to prevent bruteforce attacks
				$this->page()->addVar('error', 'Incorrect username or password');
				return;
			}

			$this->_rehashPassword($user, $password);

			$sessionUser = $this->app->user();
			$sessionUser->setUsername($username);
			$sessionUser->setAdmin(true);

			$this->app->httpResponse()->redirect('');
		}
	}

	public function executeLogout(HTTPRequest $request) {
		$this->page()->addVar('title', 'Déconnexion');

		$this->app->user()->setAdmin(false);
		$this->app->httpResponse()->redirect('..');
	}

	public function executeUpdateMe(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier mon compte');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('users');
		$cryptoManager = $this->managers->getManagerOf('crypto');

		$username = $this->app->user()->username();
		try {
			$user = $manager->getByUsername($username);
		} catch (\Exception $e) {
			return $this->executeLogout($request);
		}

		$this->page()->addVar('user', $user);

		if ($request->postExists('email')) {
			$isDirty = false;

			$email = $request->postData('email');
			if ($email != $user['email']) {
				try {
					$user['email'] = $email;
				} catch (\InvalidArgumentException $e) {
					$this->page()->addVar('error', $e->getMessage());
					return;
				}

				$isDirty = true;
			}

			$password = $request->postData('current-password');
			if (!empty($password)) {
				if ($cryptoManager->verifyPassword($password, $user['password'])) {
					$newPassword = $request->postData('new-password');
					$confirmPassword = $request->postData('confirm-password');

					if (empty($newPassword)) {
						$this->page()->addVar('error', 'Password cannot be empty');
						return;
					}

					if ($newPassword !== $confirmPassword) {
						$this->page()->addVar('error', 'Passwords does\'t match');
						return;
					}

					$user['password'] = $cryptoManager->hashPassword($newPassword);

					$isDirty = true;
				} else {
					sleep(3); // Delay to prevent bruteforce attacks
					$this->page()->addVar('error', 'Incorrect password');
					return;
				}
			}

			if ($isDirty) {
				try {
					$manager->update($user);
				} catch (\Exception $e) {
					$this->page()->addVar('error', $e->getMessage());
					return;
				}

				$this->page()->addVar('updated?', true);
			}
		}
	}

	public function executeInsert(HTTPRequest $request) {
		$this->page()->addVar('title', 'Ajouter un utilisateur');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('users');
		$cryptoManager = $this->managers->getManagerOf('crypto');

		if ($request->postExists('username')) {
			// Do not add the password to the page variable
			$userData = array(
				'username' => $request->postData('username'),
				'email' => $request->postData('email')
			);

			$this->page()->addVar('user', $userData);

			// Now we can set the password
			$userData['password'] = $request->postData('password');

			// Check if password is empty
			if (empty($userData['password'])) {
				$this->page()->addVar('error', 'Password cannot be empty');
				return;
			}

			// Check password confirm
			$passwordConfirm = $request->postData('password-confirm');
			if ($userData['password'] !== $passwordConfirm) {
				$this->page()->addVar('error', 'Passwords does\'t match');
				return;
			}

			// Check if username already exists
			$user = null;
			try {
				$user = $manager->getByUsername($userData['username']);
			} catch (\Exception $e) {}
			if (!empty($user)) {
				$this->page()->addVar('error', 'This username is already taken');
				return;
			}

			// Hash password
			$userData['password'] = $cryptoManager->hashPassword($userData['password']);

			try {
				$user = new User($userData);
			} catch(\InvalidArgumentException $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			// Finally, insert user
			try {
				$manager->insert($user);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('inserted?', true);
		}
	}

	public function executeDelete(HTTPRequest $request) {
		$this->page()->addVar('title', 'Supprimer un utilisateur');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('users');

		$username = $request->getData('username');

		if ($request->postExists('check')) {
			try {
				$manager->deleteByUsername($username);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('deleted?', true);
		} else {
			$user = $manager->getByUsername($username);
			$this->page()->addVar('user', $user);
		}
	}

	public function listUsers() {
		$manager = $this->managers->getManagerOf('users');
		$users = $manager->listAll();

		$list = array();
		foreach($users as $user) {
			$list[] = array(
				'title' => $user['username'],
				//'shortDescription' => '',
				'vars' => array('username' => $user['username'])
			);
		}

		return $list;
	}
}
