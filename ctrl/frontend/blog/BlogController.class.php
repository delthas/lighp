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

		$nbrPosts = $manager->count();
		$postsPerPage = (int) $config['postsPerPage'];
		$nbrPages = ceil($nbrPosts / $postsPerPage);
		$listPostsFrom = ($pageNbr - 1) * $postsPerPage;
		$postsList = $manager->listBy(null, array(
			'offset' => $listPostsFrom,
			'limit' => $postsPerPage
		));

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

		$commentsManager = $this->managers->getManagerOf('blogComments');

		foreach ($postsList as $i => $post) {
			if ($post['isDraft']) {
				unset($postsList[$i]);
				continue;
			}

			$postData = $post->toArray();

			$postData['content'] = nl2br($postData['content']);
			$postData['commentsCount'] = $commentsManager->countByPost($post['name']);

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
		$this->page()->addVar('atomFeed', $router->getUrl('blog', 'showAtomFeed'));
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

		$postsList = $manager->listByTag($tagName);
		
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
		$session = $request->session();

		$postName = $request->getData('postName');

		try {
			$post = $manager->get($postName);
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
		$this->page()->addVar('postContent', nl2br($post['content']));
		$this->page()->addVar('postUrl', $request->href());

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

		// Pre-fill author data
		$this->page()->addVar('comment', array(
			'authorPseudo' => $session->get('blog.comment.author.pseudo'),
			'authorEmail' => $session->get('blog.comment.author.email'),
			'authorWebsite' => $session->get('blog.comment.author.website'),
			'inReplyTo' => ($request->getExists('replyTo')) ? $request->getData('replyTo') : null
		));

		if ($request->postExists('comment-content')) {
			$commentData = array(
				'authorPseudo' => trim($request->postData('comment-author-pseudo')),
				'authorEmail' => $request->postData('comment-author-email'),
				'authorWebsite' => trim($request->postData('comment-author-website')),
				'content' => trim($request->postData('comment-content')),
				'inReplyTo' => (int) $request->postData('comment-in-reply-to'),
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

			// Save author data
			$session->set('blog.comment.author.pseudo', $comment['authorPseudo']);
			$session->set('blog.comment.author.email', $comment['authorEmail']);
			$session->set('blog.comment.author.website', $comment['authorWebsite']);

			$notificationsManager = $this->managers->getManagerOf('notifications');
			try {
				$postUrl = $request->href();
				$commentUrl = $postUrl.'#comment-'.$comment['id'];
				$title = '<a href="'.$commentUrl.'" target="_blank">Nouveau commentaire de <em>'.htmlspecialchars($comment['authorPseudo']).'</em></a>';
				$title .= ' pour <a href="'.$postUrl.'" target="_blank">'.htmlspecialchars($post['title']).'</a>';

				$notificationsManager->insert(array(
					'title' => $title,
					'description' => nl2br(htmlspecialchars($comment['content'])),
					'icon' => 'comment',
					'receiver' => $post['author'],
					'actions' => array(
						array(
							'action' => array('module' => 'blog', 'action' => 'listPostComments', 'vars' => array('postName' => $postName)),
							'title' => 'Gérer les commentaires'
						),
						array(
							'action' => array('module' => 'blog', 'action' => 'updateComment', 'vars' => array('commentId' => $comment['id'])),
							'title' => 'Modifier'
						),
						array(
							'action' => array('module' => 'blog', 'action' => 'deleteComment', 'vars' => array('commentId' => $comment['id'])),
							'title' => 'Supprimer'
						)
					)
				));
			} catch(\Exception $e) {
				// TODO: non-blocking error handling
				$this->page()->addVar('error', $e->getMessage());
				return;
			}

			$this->page()->addVar('commentInserted?', true);

			// Pre-fill author data
			$keep = array('authorPseudo', 'authorEmail', 'authorWebsite');
			foreach ($commentData as $key => $value) {
				if (!in_array($key, $keep)) {
					unset($commentData[$key]);
				}
			}
			$this->page()->addVar('comment', $commentData);
		}

		// Listing comments
		$comments = $commentsManager->getTreeByPost($postName, array(
			'sortBy' => 'creationDate desc',
			'levels' => 1,
			'includeParent' => true
		));

		$this->page()->addVar('comments', $comments);
		$this->page()->addVar('commentsCount', count($comments));
		$this->page()->addVar('comments?', (count($comments) > 0));
	}

	protected function executeShowFeed() {
		$router = $this->app->router();
		$manager = $this->managers->getManagerOf('blog');

		$this->setResponseType('FeedResponse');
		$res = $this->responseContent();

		$websiteConfig = $this->app->websiteConfig()->read();
		$baseUrl = $this->app->httpRequest()->origin() . $websiteConfig['root'] . '/';

		$link = $baseUrl . $router->getUrl('blog', 'index');
		$res->setMetadata(array(
			'title' => $websiteConfig['name'],
			'link' => $link,
			'description' => $websiteConfig['description']
		));

		$postsList = $manager->listBy(null, array(
			'limit' => 20
		));

		$items = array();
		foreach ($postsList as $post) {
			if ($post['isDraft']) {
				continue;
			}

			$link = $baseUrl . $router->getUrl('blog', 'showPost', array(
				'postName' => $post['name']
			));

			$items[] = array(
				'title' => $post['title'],
				'link' => $link,
				'content' => $post['content'],
				'publishedAt' => $post['publishedAt'],
				'updatedAt' => $post['updatedAt'],
				'categories' => $post['tags']
			);
		}

		$res->setItems($items);
	}

	public function executeShowRssFeed() {
		$this->executeShowFeed();
		$this->responseContent()->setFormat('rss');
	}

	public function executeShowAtomFeed() {
		$this->executeShowFeed();
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

			$postsList = $manager->search($searchQuery);
			$this->_showPostsList($postsList);
		}

		// TODO: pagination support here
		$this->page()->addVar('isFirstPage', true);
		$this->page()->addVar('isLastPage', true);
	}
}