<?php

namespace ctrl\backend\devtools;

use core\http\HTTPRequest;
use core\fs\Pathfinder;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DevtoolsController extends \core\BackController {
	protected function _addBreadcrumb($page = array()) {
		$breadcrumb = array(
			array(
				'url' => $this->app->router()->getUrl('main', 'showModule', array(
					'module' => $this->module()
				)),
				'title' => 'Options pour développeurs'
			)
		);

		$this->page()->addVar('breadcrumb', array_merge($breadcrumb, array($page)));
	}

	public function executeFixPermissions(HTTPRequest $req) {
		$this->page()->addVar('title', 'Réparer les permissions');
		$this->_addBreadcrumb();

		if ($req->postExists('check')) {
			$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Pathfinder::getRoot()));

			foreach ($it as $filepath => $file) {
				chmod($filepath, 0777);
			}

			$this->page()->addVar('fixed?', true);
		}
	}
}
