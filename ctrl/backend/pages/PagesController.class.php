<?php
namespace ctrl\backend\pages;

use core\http\HTTPRequest;
use lib\entities\Page;

class PagesController extends \core\BackController {
	protected function _addBreadcrumb($additionnalBreadcrumb = array(array())) {
		$breadcrumb = array(
			array(
				'url' => $this->app->router()->getUrl('main', 'showModule', array(
					'module' => $this->module()
				)),
				'title' => 'Pages'
			)
		);

		$this->page()->addVar('breadcrumb', array_merge($breadcrumb, $additionnalBreadcrumb));
	}

	public function executeInsert(HTTPRequest $request) {
		$this->page()->addVar('title', 'Créer une page');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('pages');

		if ($request->postExists('page-name')) {
			$pageData = array(
				'name' => $request->postData('page-name'),
				'title' => $request->postData('page-title'),
				'content' => $request->postData('page-content')
			);

			$this->page()->addVar('page', $pageData);

			try {
				$page = new Page($pageData);
			} catch(\InvalidArgumentException $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			try {
				$manager->insert($page);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('inserted?', true);
		}
	}

	public function executeUpdate(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier une page');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('pages');

		$pageName = $request->getData('pageName');
		$page = $manager->get($pageName);

		$this->page()->addVar('page', $page);

		if ($request->postExists('page-name')) {
			$pageData = array(
				'name' => $request->postData('page-name'),
				'title' => $request->postData('page-title'),
				'content' => $request->postData('page-content')
			);

			$this->page()->addVar('page', $pageData);

			try {
				$page->hydrate($pageData);
			} catch(\InvalidArgumentException $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			try {
				$manager->update($page);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('updated?', true);
		}
	}

	public function executeDelete(HTTPRequest $request) {
		$this->page()->addVar('title', 'Supprimer une page');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('pages');

		$pageName = $request->getData('pageName');
		$page = $manager->get($pageName);

		if (empty($page)) {
			$this->page()->addVar('error', 'Cannot find the page named "'.$pageName.'"');
			return;
		}

		$this->page()->addVar('page', $page);

		if ($request->postExists('check')) {
			try {
				$manager->delete($pageName);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('deleted?', true);
		}
	}

	// LISTERS

	public function listPages() {
		$manager = $this->managers->getManagerOf('pages');

		$pages = $manager->listAll();
		$list = array();

		foreach($pages as $page) {
			$list[] = array(
				'title' => $page['title'],
				'shortDescription' => '',
				'vars' => array('pageName' => $page['name'])
			);
		}

		return $list;
	}
}