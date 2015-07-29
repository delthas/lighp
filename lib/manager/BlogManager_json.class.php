<?php
namespace lib\manager;

class BlogManager_json extends BlogManager {
	protected function _buildPost($postData) {
		return new \lib\entities\BlogPost($postData);
	}

	public function listPosts($fromIndex = 0, $toIndex = null) {
		$postsFile = $this->dao->open('blog/posts');
		$postsData = $postsFile->read();

		$postsList = array();

		foreach($postsData as $postData) {
			try {
				$post = $this->_buildPost($postData);
				$postsList[$post['creationDate']] = $post;
			} catch(\InvalidArgumentException $e) {
				continue;
			}
		}

		krsort($postsList); //Sort posts by creation date

		return array_slice($postsList, $fromIndex, $toIndex, false);
	}

	public function countPosts() {
		$postsFile = $this->dao->open('blog/posts');
		$postsData = $postsFile->read();

		return count($postsData);
	}

	public function getPost($postName) {
		$postsFile = $this->dao->open('blog/posts');
		$postsData = $postsFile->read()->filter(array('name' => $postName));

		if (!isset($postsData[0])) {
			throw new \RuntimeException('Cannot find a blog post named "'.$postName.'"');
		}

		return $this->_buildPost($postsData[0]);
	}

	public function postExists($postName) {
		$postsFile = $this->dao->open('blog/posts');
		$postsData = $postsFile->read()->filter(array('name' => $postName));

		return (count($postsData) > 0);
	}


	public function insertPost(\lib\entities\BlogPost $post) {
		$postsFile = $this->dao->open('blog/posts');
		$items = $postsFile->read();

		$post->setCreationDate(time());

		$item = $this->dao->createItem($post->toArray());
		$items[] = $item;

		$postsFile->write($items);
	}

	public function updatePost(\lib\entities\BlogPost $post) {
		$postsFile = $this->dao->open('blog/posts');
		$items = $postsFile->read();
		
		foreach ($items as $i => $item) {
			if ($item['name'] == $post['name']) {
				$items[$i] = $this->dao->createItem($post->toArray());
				$postsFile->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find a blog post named "'.$post['name'].'"');
	}

	public function deletePost($postName) {
		$postsFile = $this->dao->open('blog/posts');
		$items = $postsFile->read();

		foreach ($items as $i => $item) {
			if ($item['name'] == $postName) {
				unset($items[$i]);
				$postsFile->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find a blog post named "'.$postName.'"');
	}
}