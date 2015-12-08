<?php
namespace lib\manager;

use lib\ArraySearcher;
use lib\entities\BlogPost;

class BlogManager_json extends BlogManager {
	use BasicManager_json {
		buildAllEntities as _buildAllEntities;
		insert as protected _insert;
		update as protected _update;
	}

	protected $path = 'blog/posts';

	protected function buildAllEntities($items) {
		$list = $this->_buildAllEntities($items);

		// Sort by publication date
		usort($list, function ($a, $b) {
			$aDate = (!empty($a['publishedAt'])) ? $a['publishedAt'] : $a['updatedAt'];
			$bDate = (!empty($b['publishedAt'])) ? $b['publishedAt'] : $b['updatedAt'];

			return ($aDate > $bDate) ? -1 : 1;
		});

		return $list;
	}

	public function listByTag($tag) {
		return $this->listBy(function ($item) use ($tag) {
			return in_array($tag, $item['tags']);
		});
	}

	public function search($query) {
		$query = strtolower($query);

		$file = $this->open();
		$items = $file->read();

		$searcher = new ArraySearcher($items);
		$items = $searcher->search($query, array('title', 'content'));

		return $this->buildAllEntities($items);
	}

	public function count() {
		$file = $this->open();
		$items = $file->read();

		return count($items);
	}

	public function listAllTags() {
		$posts = $this->listAll();

		$tags = array();
		foreach ($posts as $post) {
			foreach ($post['tags'] as $tag) {
				if (!empty($tag) && !in_array($tag, $tags)) {
					$tags[] = $tag;
				}
			}
		}

		return $tags;
	}

	public function exists($postName) {
		return !empty($this->get($postName));
	}


	public function insert($post) {
		if (!$post['isDraft']) {
			$post['publishedAt'] = time();
		}

		return $this->_insert($post);
	}

	public function update($post) {
		if (!$post['isDraft']) {
			$current = $this->get($post['name']);
			if ($current['isDraft']) {
				$post['publishedAt'] = time();
			}
		}

		return $this->_update($post);
	}
}
