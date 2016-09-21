<?php

namespace core\apps;

/**
 * A backend API.
 * @author emersion
 * @since 1.0alpha1
 */
class BackendApiApplication extends Application {
	/**
	 * Initialize this backend.
	 */
	public function __construct() {
		parent::__construct();

		$this->name = 'backendApi';
		$this->mountPoint = '/api/admin';
	}

	public function run() {
		if ($this->user->isAdmin()) {
			$controller = $this->getController();
		} else {
			$this->httpResponse()->redirect403($this);
			return;
		}

		$controller->execute();

		$this->httpResponse->setContent($controller->responseContent());
	}
}