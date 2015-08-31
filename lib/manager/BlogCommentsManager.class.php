<?php
namespace lib\manager;

abstract class BlogCommentsManager extends \core\Manager {
	use BasicManager;

	protected $entity = '\lib\entities\BlogComment';
	protected $primaryKey = 'id';

	public function listByPost($postName, $opts = array()) {
		return $this->listBy(array(
			'postName' => $postName
		), $opts);
	}
}