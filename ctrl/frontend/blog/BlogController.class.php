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
		$nbrPages = ceil((float) $nbrPosts / $postsPerPage);
		$listPostsFrom = ($pageNbr - 1) * $postsPerPage;
		$postsList = $manager->listBy(null, array(
			'offset' => $listPostsFrom,
			'limit' => $postsPerPage,
			'sortBy' => 'publishedAt desc'
		));

		$isFirstPage = ($pageNbr == 1);
		$isLastPage = ($pageNbr == $nbrPages);

		$this->page()->addVar('isFirstPage', $isFirstPage);
		$this->page()->addVar('isLastPage', $isLastPage);
		$this->page()->addVar('previousPage', $pageNbr - 1);
		$this->page()->addVar('nextPage', $pageNbr + 1);

		$this->page()->addVar('introduction', $config['introduction']);

		$this->_showPostsList($postsList);
	}

	protected function _showPostsList($postsList) {
		$router = $this->app->router();
		$commentsManager = $this->managers->getManagerOf('blogComments');

		$published = array();
		foreach ($postsList as $i => $post) {
			if ($post['isDraft']) {
				continue;
			}

			$postData = $post->toArray();

			$postData['content'] = nl2br($post->excerpt());
			$postData['hasExcerpt'] = $post->hasExcerpt();
			$postData['commentsCount'] = $commentsManager->countByPost($post['name']);

			$tags = array();
			foreach ($post['tags'] as $i => $tagName) {
				$tags[] = array(
					'name' => $tagName,
					'url' => $router->getUrl('blog', 'showTag', array($tagName)),
					'first?' => ($i == 0)
				);
			}
			$postData['tagsData'] = $tags;

			$published[] = $postData;
		}

		$this->page()->addVar('postsList', $published);
		$this->page()->addVar('postsListNotEmpty?', (count($published) > 0));

		$router = $this->app->router();
		$this->page()->addVar('rssFeed', $router->getUrl('blog', 'showRssFeed'));
		$this->page()->addVar('atomFeed', $router->getUrl('blog', 'showAtomFeed'));
	}

	public function executeIndex(HTTPRequest $request) {
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsPage(1);
	}

	public function executeShowPage(HTTPRequest $request) {
		$this->translation()->setSection('index');
		$dict = $this->translation()->read();

		$this->page()->addVar('title', $dict['title']);

		$this->_showPostsPage((int) $request->getData('pageNbr'));
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
				'inReplyTo' => ($request->postExists('comment-in-reply-to')) ? (int) $request->postData('comment-in-reply-to') : null,
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

			// Send a notification to the post author
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

			// If it's a reply, send an e-mail to the comment author
			if (!empty($comment['inReplyTo'])) {
				$parent = $commentsManager->get($comment['inReplyTo']);

				if (!empty($parent['authorEmail'])) {
					$websiteConfig = $this->app->websiteConfig()->read();

					$prefix = '> ';
					$content = $prefix.str_replace("\n", "\n".$prefix, $comment['content']);

					// TODO: translations
					$br = "\r\n";
					$message = (new \Swift_Message())
						->setSubject('Nouvelle réponse à votre commentaire sur '.$websiteConfig['name'])
						->setTo($parent['authorEmail'])
						->setBody('Bonjour,'.$br.$br.
							$comment['authorPseudo'].' a répondu à votre commentaire sur le billet '.$post['title'].' :'.$br.$br.
							$content.$br.$br.
							'Pour y répondre, cliquez ici : '.$request->origin().$request->path().'#comment-'.$comment['id']);

					try {
						$this->app->mailer()->send($message);
					} catch (\Exception $e) {
						// TODO: silently ignore error
					}
				}
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
			'sortBy' => 'createdAt desc',
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
