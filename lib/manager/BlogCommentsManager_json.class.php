<?php
namespace lib\manager;

class BlogCommentsManager_json extends BlogCommentsManager {
	protected function _buildComment($commentData) {
		return new \lib\entities\BlogComment($commentData);
	}

	public function listByPost($postName, $fromIndex = 0, $toIndex = null) {
		$commentsFile = $this->dao->open('blog/comments');
		$commentsData = $commentsFile->read()->filter(array('postName' => $postName));

		$commentsList = array();

		foreach($commentsData as $commentData) {
			try {
				$comment = $this->_buildComment($commentData);
				$commentsList[$comment['creationDate']] = $comment; //Warning: if two comments have the same timestamp, only one will be shown !
			} catch(\InvalidArgumentException $e) {
				continue;
			}
		}

		krsort($commentsList); //Sort posts by creation date

		return array_slice($commentsList, $fromIndex, $toIndex, false);
	}

	public function countByPost($postName) {
		$commentsFile = $this->dao->open('blog/comments');
		$commentsData = $commentsFile->read()->filter(array('postName' => $postName));

		return count($commentsData);
	}

	public function getById($commentId) {
		$commentsFile = $this->dao->open('blog/comments');
		$commentsData = $commentsFile->read()->filter(array('id' => (int) $commentId));

		if (!isset($commentsData[0])) {
			throw new \RuntimeException('Cannot find a blog comment with id "'.$commentId.'"');
		}

		return $this->_buildComment($commentsData[0]);
	}


	public function insert(\lib\entities\BlogComment $comment) {
		$commentsFile = $this->dao->open('blog/comments');
		$items = $commentsFile->read();

		$comment->setCreationDate(time());

		$commentId = (count($items) > 0) ? $items->last()['id'] + 1 : 0;
		$comment->setId($commentId);

		$item = $this->dao->createItem($comment->toArray());

		$items[] = $item;

		$commentsFile->write($items);
	}

	public function update(\lib\entities\BlogComment $comment) {
		$commentsFile = $this->dao->open('blog/comments');
		$items = $commentsFile->read();

		$commentItem = $this->dao->createItem($comment->toArray());

		foreach ($items as $i => $currentItem) {
			if ($currentItem['id'] == $comment['id']) {
				$items[$i] = $commentItem;
				$commentsFile->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find a blog comment with id "'.$comment['id'].'"');
	}

	public function delete($commentId) {
		$commentsFile = $this->dao->open('blog/comments');
		$items = $commentsFile->read();

		foreach ($items as $i => $item) {
			if ($item['id'] == $commentId) {
				unset($items[$i]);
				$commentsFile->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find a blog comment with id "'.$commentId.'"');
	}
}