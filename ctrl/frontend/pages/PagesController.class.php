<?php
namespace ctrl\frontend\pages;

use core\http\HTTPRequest;

class PagesController extends \core\BackController {
	public function executeShowPage(HTTPRequest $request) {
		$manager = $this->managers->getManagerOf('pages');

		$pageName = $request->getData('pageName');

		$page = $manager->get($pageName);
		if (empty($page)) {
			return $this->app->httpResponse()->redirect404($this->app);
		}

		$this->page()->addVar('title', $page['title']);
		$this->page()->addVar('page', $page);
	}
}