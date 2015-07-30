<?php

namespace ctrl\backend\routes;

use core\http\HTTPRequest;
use core\fs\Pathfinder;
use core\Config;

class RoutesController extends \core\BackController {
	protected function _addBreadcrumb($page = array()) {
		$breadcrumb = array(
			array(
				'url' => $this->app->router()->getUrl('main', 'showModule', array(
					'module' => $this->module()
				)),
				'title' => 'Routes'
			)
		);

		$this->page()->addVar('breadcrumb', array_merge($breadcrumb, array($page)));
	}

	protected function _listRoutes($appName) {
		$routes = array();

		$configPath = Pathfinder::getPathFor('config') . '/app/' . $appName;
		$dir = dir($configPath);

		if ($dir === false) {
			throw new \RuntimeException('Failed to open config directory "'.$configPath.'"');
		}

		while (false !== ($module = $dir->read())) {
			if ($module == '..') {
				continue;
			}

			$modulePath = $configPath . '/' . $module;
			$routesPath = $modulePath . '/routes.json';
			if (is_dir($modulePath) && file_exists($routesPath)) {
				$json = file_get_contents($routesPath);
				if ($json === false) { continue; }

				$moduleRoutes = json_decode($json, true);
				if ($moduleRoutes === null) { continue; }

				if ($module == '.') { $module = ''; }

				$routes[$module] = $moduleRoutes;
			}
		}
		$dir->close();

		return $routes;
	}

	public function executeListRoutes(HTTPRequest $request) {
		$this->page()->addVar('title', 'Gérer une route');
		$this->_addBreadcrumb();

		$app = 'frontend';
		$routes = $this->_listRoutes($app);

		$tplRoutes = array();

		foreach($routes as $moduleName => $moduleRoutes) {
			foreach($moduleRoutes as $id => $route) {
				if (!empty($moduleName)) {
					$route['module'] = $moduleName;
					$route['editable?'] = false;
				} else {
					$route['editable?'] = true;
				}

				$route['id'] = $id;
				$route['app'] = $app;
				$route['varsList'] = implode(',', (isset($route['vars'])) ? $route['vars'] : array());

				$tplRoutes[] = $route;
			}
		}

		$this->page()->addVar('routes', $tplRoutes);
	}

	public function executeInsertRoute(HTTPRequest $request) {
		$this->page()->addVar('title', 'Ajouter une route');
		$this->_addBreadcrumb();

		if ($request->postExists('route-url')) {
			$routeApp = $request->postData('route-app');
			$routeVarsList = $request->postData('route-vars');
			$routeVars = explode(',', $routeVarsList);
			foreach ($routeVars as $i => $var) {
				$routeVars[$i] = trim($var);
			}

			$route = array(
				'url' => $request->postData('route-url'),
				'module' => $request->postData('route-module'),
				'action' => $request->postData('route-action'),
				'vars' => $routeVars,
				'redirect' => ($request->postData('route-redirect') == 'on')
			);

			$this->page()->addVar('route', $route);
			$this->page()->addVar('routeApp', $routeApp);
			$this->page()->addVar('routeVarsList', $routeVarsList);

			$configPath = Pathfinder::getPathFor('config') . '/app/' . $routeApp . '/routes.json';

			try {
				$conf = new Config($configPath);

				$routes = $conf->read();

				$routes[] = $route;

				$conf->write($routes);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('inserted?', true);
		}
	}

	public function executeDuplicateRoute(HTTPRequest $request) {
		$this->page()->addVar('title', 'Dupliquer une route');
		$this->_addBreadcrumb();

		$routeApp = $request->getData('app');
		$moduleName = $request->getData('module');
		$routeId = (int) $request->getData('id');

		if ($request->postExists('route-url')) {
			return $this->executeInsertRoute($request);
		}

		$configPath = Pathfinder::getPathFor('config') . '/app/' . $routeApp . '/' . $moduleName . '/routes.json';

		try {
			$conf = new Config($configPath);

			$routes = $conf->read();
			$route = null;
			foreach ($routes as $id => $route) {
				if ($id == $routeId) {
					$route = $routes[$id];
					break;
				}
			}

			if (empty($route)) {
				$this->page()->addVar('error', 'This route doesn\'t exist');
				return;
			}

			$route['module'] = $moduleName;
			$this->page()->addVar('route', $route);
			$this->page()->addVar('routeVarsList', implode(', ', $route['vars']));

			//$conf->write($routes);
		} catch(\Exception $e) {
			$this->page()->addVar('error', $e->getMessage());
			return;
		}
	}

	public function executeDeleteRoute(HTTPRequest $request) {
		$this->page()->addVar('title', 'Supprimer une route');
		$this->_addBreadcrumb();

		$routeApp = $request->getData('app');
		$routeId = (int) $request->getData('id');

		$configPath = Pathfinder::getPathFor('config') . '/app/' . $routeApp . '/routes.json';

		try {
			$conf = new Config($configPath);

			$routes = $conf->read();
			foreach ($routes as $id => $route) {
				if ($id == $routeId) {
					unset($routes[$id]);
					break;
				}
			}

			$conf->write($routes);
		} catch(\Exception $e) {
			$this->page()->addVar('error', $e->getMessage());
			return;
		}

		$this->page()->addVar('deleted?', true);
	}

	public function listRoutes() {
		$app = 'frontend';
		$routes = $this->_listRoutes($app);

		$list = array();
		foreach($routes as $moduleName => $moduleRoutes) {
			foreach($moduleRoutes as $id => $route) {
				if (!empty($moduleName)) {
					$route['module'] = $moduleName;
					$route['editable?'] = false;
				} else {
					$route['editable?'] = true;
				}

				$list[] = array(
					'title' => '<code>'.$route['url'].'</code>',
					'shortDescription' => 'Module : '.$route['module'].', action : '.$route['action'],
					'vars' => array(
						'app' => $app,
						'module' => $route['module'],
						'id' => $id
					),
					'actions?' => array(
						'deleteRoute' => $route['editable?'],
						'duplicateRoute' => !$route['editable?']
					)
				);
			}
		}

		return $list;
	}
}