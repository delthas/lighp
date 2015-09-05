<?php
namespace ctrl\backend\blog;

use core\http\HTTPRequest;
use lib\entities\BlogPost;

class BlogController extends \core\BackController {
	protected function _addBreadcrumb($additionnalBreadcrumb = array(array())) {
		$breadcrumb = array(
			array(
				'url' => $this->app->router()->getUrl('main', 'showModule', array(
					'module' => $this->module()
				)),
				'title' => 'Blog'
			)
		);

		$this->page()->addVar('breadcrumb', array_merge($breadcrumb, $additionnalBreadcrumb));
	}

	public function executeInsertPost(HTTPRequest $request) {
		$this->page()->addVar('title', 'Créer un billet');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('blog');

		if ($request->postExists('post-name')) {
			$postData = array(
				'name' => $request->postData('post-name'),
				'title' => $request->postData('post-title'),
				'content' => $request->postData('post-content'),
				'author' => $this->app->user()->username(),
				'isDraft' => $request->postExists('post-is-draft'),
				'tags' => preg_split('/\s*,\s*/', $request->postData('post-tags'))
			);

			$this->page()->addVar('post', $postData);

			try {
				$post = new BlogPost($postData);
				$manager->insert($post);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('inserted?', true);
		}
	}

	public function executeUpdatePost(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier un billet');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('blog');

		$postName = $request->getData('postName');
		$post = $manager->get($postName);

		$this->page()->addVar('post', $post);

		if ($request->postExists('post-name')) {
			$postData = array(
				//'name' => $request->postData('post-name'),
				'name' => $postName,
				'title' => $request->postData('post-title'),
				'content' => $request->postData('post-content'),
				'isDraft' => $request->postExists('post-is-draft'),
				'tags' => preg_split('/\s*,\s*/', $request->postData('post-tags'))
			);

			$this->page()->addVar('post', $postData);

			try {
				$post->hydrate($postData);
				$manager->update($post);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('updated?', true);
		}
	}

	public function executeDeletePost(HTTPRequest $request) {
		$this->page()->addVar('title', 'Supprimer un billet');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('blog');

		$postName = $request->getData('postName');

		if ($request->postExists('check')) {
			if (!$manager->exists($postName)) {
				$this->page()->addVar('error', 'Cannot find the post named "'.$postName.'"');
				return;
			}

			try {
				$manager->delete($postName);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('deleted?', true);
		} else {
			$post = $manager->get($postName);
			$this->page()->addVar('post', $post);
		}
	}

	public function executeListPostComments(HTTPRequest $request) {
		$this->page()->addVar('title', 'Lister les commentaires d\'un billet');
		$this->_addBreadcrumb();

		$manager = $this->managers->getManagerOf('blogComments');

		$postName = $request->getData('postName');

		$comments = $manager->listByPost($postName, array(
			'sortBy' => 'createdAt desc'
		));

		$this->page()->addVar('comments', $comments);
	}

	public function executeUpdateComment(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier un commentaire');
		$this->_addBreadcrumb();

		$commentId = (int) $request->getData('commentId');

		$manager = $this->managers->getManagerOf('blogComments');

		$comment = $manager->get($commentId);
		$this->page()->addVar('comment', $comment);

		if ($request->postExists('comment-content')) {
			$commentData = array(
				'authorPseudo' => trim($request->postData('comment-author-pseudo')),
				'authorEmail' => $request->postData('comment-author-email'),
				'authorWebsite' => trim($request->postData('comment-author-website')),
				'content' => trim($request->postData('comment-content'))
			);

			$this->page()->addVar('comment', $commentData);

			try {
				$comment->hydrate($commentData);
			} catch(\InvalidArgumentException $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			try {
				$manager->update($comment);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('updated?', true);
		}
	}

	public function executeDeleteComment(HTTPRequest $request) {
		$this->page()->addVar('title', 'Supprimer un commentaire');
		$this->_addBreadcrumb();

		$commentId = (int) $request->getData('commentId');

		$manager = $this->managers->getManagerOf('blogComments');

		$comment = $manager->get($commentId);
		$this->page()->addVar('comment', $comment);

		if ($request->postExists('check')) {
			try {
				$manager->delete($commentId);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('deleted?', true);
		}
	}

	public function executeUpdateConfig(HTTPRequest $request) {
		$this->page()->addVar('title', 'Modifier la configuration');
		$this->_addBreadcrumb();

		$config = $this->config();

		if ($request->postExists('config-introduction')) {
			$configData = array(
				'introduction' => $request->postData('config-introduction'),
				'postsPerPage' => (int) $request->postData('config-postsPerPage'),
				'dateFormat' => $request->postData('config-dateFormat'),
				'enableComments' => ($request->postData('config-enableComments') == 'on')
			);

			try {
				$config->write($configData, 'frontend');
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('updated?', true);
		} else {
			$this->page()->addVar('config', $config->read('frontend'));
		}
	}


	// LISTERS

	public function listPosts() {
		$manager = $this->managers->getManagerOf('blog');

		$posts = $manager->listAll();
		$list = array();

		foreach($posts as $post) {
			$desc = 'Par '.$post['author'];
			if ($post['isDraft']) {
				$desc .= ' [brouillon]';
			}

			$item = array(
				'title' => $post['title'],
				'shortDescription' => $desc,
				'vars' => array('postName' => $post['name'])
			);

			$list[] = $item;
		}

		return array_reverse($list);
	}
}