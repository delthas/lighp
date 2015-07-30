<?php

namespace ctrl\backend\websiteconfig;

use core\http\HTTPRequest;

class WebsiteconfigController extends \core\BackController {
	public function executeUpdate(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier la configuration');

		$configFile = $this->app->websiteConfig();
		$config = $configFile->read();

		if ($request->postExists('name')) {
			$config['name'] = $request->postData('name');
			$config['description'] = $request->postData('description');
			$config['author'] = $request->postData('author');

			try {
				$configFile->write($config);
			} catch (\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('updated?', true);
		}

		$this->page()->addVar('config', $config);
	}
}