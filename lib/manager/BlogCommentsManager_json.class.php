<?php
namespace lib\manager;

class BlogCommentsManager_json extends BlogCommentsManager {
	use BasicManager_json;

	protected $path = 'blog/comments';

	public function countByPost($postName) {
		$file = $this->open();
		$items = $file->read()->filter(array('postName' => $postName));

		return count($items);
	}
}