<?php
namespace ctrl\frontend\blog;

use core\http\HTTPRequest;
use lib\Captcha;

class BlogController extends \core\BackController {
	protected function _showPostsList($pageNbr) {
		$manager = $this->managers->getManagerOf('blog');
		$config = $this->config()->read();

		if ($pageNbr < 1) {
			return;
		}

		$nbrPosts = $manager->countPosts();
		$postsPerPage = (int) $config['postsPerPage'];
		$nbrPages = ceil($nbrPosts / $postsPerPage);
		$listPostsFrom = ($pageNbr - 1) * $postsPerPage;
		$postsList = $manager->listPosts($listPostsFrom, $postsPerPage);

		foreach ($postsList as $i => $post) {
			$postData = $post->toArray();

			$postData['creationDate'] = date($config['dateFormat'], $postData['creationDate']);
			$postData['content'] = nl2br($postData['content']);

			$postsList[$i] = $postData;
		}

		$isFirstPage = ($pageNbr == 1);
		$isLastPage = ($pageNbr == $nbrPages);

		$this->page()->addVar('postsList', $postsList);
		$this->page()->addVar('postsListNotEmpty?', (count($postsList) > 0));
		$this->page()->addVar('isFirstPage', $isFirstPage);
		$this->page()->addVar('isLastPage', $isLastPage);
		$this->page()->addVar('previousPage', $pageNbr - 1);
		$this->page()->addVar('nextPage', $pageNbr + 1);
	}

	public function executeIndex(HTTPRequest $request) {
		$config = $this->config()->read();
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsList(1);

		$this->page()->addVar('introduction', $config['introduction']);
	}

	public function executeShowPage(HTTPRequest $request) {
		$this->translation()->setSection('index');

		$config = $this->config()->read();
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsList((int) $request->getData('pageNbr'));

		$this->page()->addVar('introduction', $config['introduction']);
	}

	public function executeShowPost(HTTPRequest $request) {
		$manager = $this->managers->getManagerOf('blog');
		$config = $this->config()->read();

		$postName = $request->getData('postName');

		try {
			$post = $manager->getPost($postName);
		} catch(\Exception $e) {
			return;
		}

		$this->page()->addVar('title', $post['title']);
		$this->page()->addVar('post', $post);
		$this->page()->addVar('postCreationDate', date($config['dateFormat'], $post['creationDate']));
		$this->page()->addVar('postContent', nl2br($post['content']));

		//Comments
		$commentsManager = $this->managers->getManagerOf('blogComments');

		$captcha = Captcha::build($this->app());
		$this->page()->addVar('captcha', $captcha);

		if ($request->postExists('comment-content')) {
			$commentData = array(
				'authorPseudo' => trim($request->postData('comment-author-pseudo')),
				'authorEmail' => $request->postData('comment-author-email'),
				'authorWebsite' => trim($request->postData('comment-author-website')),
				'content' => trim($request->postData('comment-content')),
				'postName' => $postName
			);

			$this->page()->addVar('comment', $commentData);

			try {
				$comment = new \lib\entities\BlogComment($commentData);
			} catch(\InvalidArgumentException $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$captchaId = (int) $request->postData('captcha-id');
			$captchaValue = $request->postData('captcha-value');

			try {
				Captcha::check($this->app(), $captchaId, $captchaValue);
			} catch (\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			try {
				$commentsManager->insert($comment);
			} catch(\Exception $e) {
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('commentInserted?', true);
			$this->page()->addVar('comment', null);
		}

		//Listing comments
		$comments = $commentsManager->listByPost($postName);

		foreach ($comments as $i => $comment) {
			$commentData = $comment->toArray();

			$commentData['creationDate'] = date($config['dateFormat'], $commentData['creationDate']);

			$comments[$i] = $commentData;
		}

		$this->page()->addVar('comments', $comments);
		$this->page()->addVar('comments?', (count($comments) > 0));

		$this->page()->addVar('user', $this->app()->user());
	}
}