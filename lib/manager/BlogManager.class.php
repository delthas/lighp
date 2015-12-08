<?php
namespace lib\manager;

abstract class BlogManager extends \core\Manager {
	use BasicManager;

	protected $entity = '\lib\entities\BlogPost';
	protected $primaryKey = 'name';

	abstract public function listByTag($tag);
	abstract public function search($query);
	abstract public function count();
	abstract public function exists($postName);

	abstract public function listAllTags();
}
