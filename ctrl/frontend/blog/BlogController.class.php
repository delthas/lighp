<?php
namespace ctrl\frontend\blog;

use core\http\HTTPRequest;
use lib\Captcha;

class BlogController extends \core\BackController {
	protected function _showPostsPage($pageNbr) {
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

		$isFirstPage = ($pageNbr == 1);
		$isLastPage = ($pageNbr == $nbrPages);

		$this->page()->addVar('isFirstPage', $isFirstPage);
		$this->page()->addVar('isLastPage', $isLastPage);
		$this->page()->addVar('previousPage', $pageNbr - 1);
		$this->page()->addVar('nextPage', $pageNbr + 1);

		$this->_showPostsList($postsList);
	}

	protected function _showPostsList($postsList) {
		$config = $this->config()->read();

		foreach ($postsList as $i => $post) {
			if ($post['isDraft']) {
				unset($postsList[$i]);
				continue;
			}

			$postData = $post->toArray();

			$postData['creationDate'] = date($config['dateFormat'], $postData['creationDate']);
			$postData['content'] = nl2br($postData['content']);

			$postsList[$i] = $postData;
		}

		// Important: make sure $postsList is not indexed
		// because of unset(), otherwise mustache doesn't
		// want to loop through it.
		$postsList = array_values($postsList);

		$this->page()->addVar('postsList', $postsList);
		$this->page()->addVar('postsListNotEmpty?', (count($postsList) > 0));

		$router = $this->app->router();
		$this->page()->addVar('rssFeed', $router->getUrl('blog', 'showRssFeed'));
		$this->page()->addVar('atomFeed', $router->getUrl('blog', 'showRssFeed'));
	}

	public function executeIndex(HTTPRequest $request) {
		$config = $this->config()->read();
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsPage(1);

		$this->page()->addVar('introduction', $config['introduction']);
	}

	public function executeShowPage(HTTPRequest $request) {
		$this->translation()->setSection('index');

		$config = $this->config()->read();
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsPage((int) $request->getData('pageNbr'));

		$this->page()->addVar('introduction', $config['introduction']);
	}

	public function executeShowTag(HTTPRequest $request) {
		$this->translation()->setSection('index');

		$manager = $this->managers->getManagerOf('blog');

		$tagName = $request->getData('tagName');
		$this->page()->addVar('title', $tagName);

		$postsList = $manager->listPostsByTag($tagName);
		
		if (count($postsList) === 0) {
			return $this->app->httpResponse()->redirect404($this->app);
		}

		$this->_showPostsList($postsList);

		// TODO: pagination support here
		$this->page()->addVar('isFirstPage', true);
		$this->page()->addVar('isLastPage', true);
	}

	public function executeShowPost(HTTPRequest $request) {
		$manager = $this->managers->getManagerOf('blog');
		$config = $this->config()->read();

		$postName = $request->getData('postName');

		try {
			$post = $manager->getPost($postName);
		} catch(\Exception $e) {
			$this->app->httpResponse()->redirect404($this->app);
			return;
		}

		if ($post['isDraft']) {
			$this->app->httpResponse()->redirect404($this->app);
			return;
		}

		$this->page()->addVar('title', $post['title']);
		$this->page()->addVar('type', 'article');
		$this->page()->addVar('post', $post);
		$this->page()->addVar('postCreationDate', date($config['dateFormat'], $post['creationDate']));
		$this->page()->addVar('postContent', nl2br($post['content']));

		// Tags
		$tagsNames = $post['tags'];
		$router = $this->app->router();

		$tags = array();
		foreach ($tagsNames as $i => $tagName) {
			$tags[] = array(
				'name' => $tagName,
				'url' => $router->getUrl('blog', 'showTag', array($tagName)),
				'first?' => ($i == 0)
			);
		}
		$this->page()->addVar('postTags', $tags);

		// Comments
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

			$notificationsManager = $this->managers->getManagerOf('notifications');
			try {
				$notificationsManager->insert(array(
					'title' => 'Nouveau commentaire de <em>'.htmlspecialchars($comment['authorPseudo']).'</em> pour <em>'.htmlspecialchars($post['title']).'</em>',
					'description' => nl2br(htmlspecialchars($comment['content'])),
					'receiver' => $post['author']
				));
			} catch(\Exception $e) {
				// TODO: non-blocking error handling
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('commentInserted?', true);
			$this->page()->addVar('comment', null);
		}

		// Listing comments
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

	protected function executeShowFeed() {
		$router = $this->app->router();

		$this->setResponseType('FeedResponse');

		$res = $this->responseContent();

		$websiteConfig = $this->app->websiteConfig()->read();
		$link = (!empty($websiteConfig['root'])) ? $websiteConfig['root'] : '/'; // TODO
		$res->setMetadata(array(
			'title' => $websiteConfig['name'],
			'link' => $link,
			'description' => $websiteConfig['description']
		));

		$items = array();
		$manager = $this->managers->getManagerOf('blog');
		$postsList = $manager->listPosts(0, 20);
		foreach ($postsList as $post) {
			if ($post['isDraft']) {
				continue;
			}

			$link = $router->getUrl('blog', 'showPost', array(
				'postName' => $post['name']
			));

			$items[] = array(
				'title' => $post['title'],
				'link' => $link,
				'content' => $post['content'],
				'createdAt' => $post['creationDate']
			);
		}

		$res->setItems($items);
	}

	public function executeShowRssFeed() {
		$this->executeShowFeed();
		$this->responseContent()->setFormat('rss');
	}

	public function executeShowAtomFeed() {
		return $this->executeShowFeed();
		$this->responseContent()->setFormat('atom');
	}

	public function executeSearchPosts(HTTPRequest $request) {
		$this->page()->addVar('title', 'Rechercher des billets');
		$this->translation()->setSection('index');

		$manager = $this->managers->getManagerOf('blog');

		if ($request->getExists('q')) {
			$searchQuery = $request->getData('q');
			$this->page()->addVar('searchQuery', $searchQuery);

			if (strlen($searchQuery) < 3) {
				$this->page()->addVar('error', 'Votre requête doit contenir 3 caractères au minimum.');
				return;
			} 

			$postsList = $manager->searchPosts($searchQuery);
			$this->_showPostsList($postsList);
		}

		// TODO: pagination support here
		$this->page()->addVar('isFirstPage', true);
		$this->page()->addVar('isLastPage', true);
	}
}