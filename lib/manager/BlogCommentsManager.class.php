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

	abstract public function countByPost($postName);

	public function getTreeByPost($postName, $opts = array()) {
		$comments = $this->listByPost($postName, $opts);

		$getChildren = function ($parent = null, $level = 0) use(&$comments, &$getChildren, $opts) {
			$children = array();

			$parentId = null;
			if (isset($parent['id'])) {
				$parentId = $parent['id'];
			}

			foreach ($comments as $comment) {
				if ($comment['inReplyTo'] === $parentId) {
					$subchildren = $getChildren($comment, $level + 1);

					$commentData = $comment->toArray();
					$commentData['replies'] = array();

					if (isset($opts['includeParent']) && $opts['includeParent']) {
						$commentData['inReplyTo'] = $parent;
					}

					if (isset($opts['levels']) && $level >= $opts['levels']) {
						// Do not create another level
						$children[] = $commentData;
						$children = array_merge($children, $subchildren);
					} else {
						$commentData['replies'] = $subchildren;
						$children[] = $commentData;
					}
				}
			}

			return $children;
		};

		return $getChildren();
	}
}