<?php
namespace lib\manager;

abstract class PagesManager extends \core\Manager {
	use BasicManager;

	protected $entity = '\lib\entities\Page';
	protected $primaryKey = 'name';
}