<?php

namespace core\apps;

/**
 * A frontend API.
 * @author emersion
 * @since 1.0alpha1
 */
class FrontendApiApplication extends Application {
	/**
	 * Initialize this frontend.
	 */
	public function __construct() {
		parent::__construct();

		$this->name = 'frontendApi';
		$this->mountPoint = '/api';
	}

	public function run() {
		$controller = $this->getController();
		$controller->execute();

		$this->httpResponse->setContent($controller->responseContent());
	}
}