<?php

namespace core\responses;

use SimpleXMLElement;
use DateTime;

/**
 * A news feed response.
 * @author emersion
 * @since 1.0alpha2
 */
class FeedResponse extends ResponseContent {
	/**
	 * The response's metadata.
	 * @var array
	 */
	protected $metadata = array();

	/**
	 * The response's items.
	 * @var array
	 */
	protected $items = array();

	/**
	 * The respinse's output format.
	 * @var string
	 */
	protected $format = 'rss';

	/**
	 * Generate the feed XML output.
	 * @return string The XML output.
	 */
	public function generate() {
		$generate = 'generate'.ucfirst($this->format);
		return $this->$generate();
	}

	/**
	 * Generate an RSS feed.
	 * @return string The XML output.
	 * @see http://www.rssboard.org/rss-specification
	 */
	protected function generateRss() {
		$this->app->httpResponse()->addHeader('Content-Type: text/xml');

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
			. '<rss version="2.0"></rss>');

		$xml->addChild('channel');
		$xml->channel->title = $this->metadata['title'];
		$xml->channel->link = $this->metadata['link'];
		$xml->channel->description = $this->metadata['description'];

		foreach($this->items as $data) {
			$item = $xml->channel->addChild('item');

			$item->title = $data['title'];
			$item->link = $data['link'];
			$item->description = $data['content'];
			$item->pubDate = date(DateTime::RSS, $data['publishedAt']);

			if (!empty($data['author'])) {
				$item->author = $data['author'];
			}
			if (!empty($data['categories'])) {
				$i = 0;
				foreach ($data['categories'] as $category) {
					$item->addChild('category');
					if (isset($category['title'])) {
						$item->category[$i] = $category['title'];
						if (isset($category['link'])) {
							$item->category[$i]->domain = $category['link'];
						}
					} else {
						$item->category[$i] = $category;
					}
					$i++;
				}
			}
		}

		return $xml->asXML();
	}

	/**
	 * Generate an ATOM feed.
	 * @return string The XML output.
	 * @see http://tools.ietf.org/html/rfc4287
	 */
	protected function generateAtom() {
		$this->app->httpResponse()->addHeader('Content-Type: application/atom+xml');

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
			. '<feed xmlns="http://www.w3.org/2005/Atom"></feed>');

		$xml->title = $this->metadata['title'];
		$xml->addChild('link')->addAttribute('href', $this->metadata['link']);

		foreach($this->items as $data) {
			$item = $xml->addChild('entry');
			$item->title = $data['title'];
			$item->addChild('link')->addAttribute('href', $data['link']);
			//$item->addChild('summary', $data['description']);
			$item->addChild('published', date(DateTime::ATOM, $data['publishedAt']));

			$content = $item->addChild('content');
			$content->addAttribute('type', 'xhtml');

			// TODO
			/*libxml_use_internal_errors(true);
			$doc = new \DOMDocument('1.0', 'UTF-8');
			$doc->loadHTML('<div>'.$data['content'].'</div>');
			$content->div = simplexml_import_dom($doc)->body->div;*/
			$content->div = $data['content'];
			$content->div->addAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

			if (!empty($data['author'])) {
				$content->addChild('author');
				$content->author->name = $data['author'];
			}
			if (!empty($data['updatedAt'])) {
				$item->addChild('updated', date(DateTime::ATOM, $data['updatedAt']));
			}
			if (!empty($data['categories'])) {
				$i = 0;
				foreach ($data['categories'] as $category) {
					$catTag = $item->addChild('category');
					if (isset($category['title'])) {
						$catTag->label = $category['title'];
						if (isset($category['link'])) {
							$catTag->term = $category['link'];
						}
					} else {
						$catTag->label = $category;
					}
					$i++;
				}
			}
		}

		return $xml->asXML();
	}

	public function metadata() {
		return $this->metadata;
	}

	public function items() {
		return $this->items;
	}

	public function format() {
		return $this->format;
	}

	/**
	 * Set this feed's metadata.
	 * The metadata can contain several fields:
	 * * `title`: the feed title
	 * * `link`: the website URL
	 * * `description`: this feed description
	 * @param array $metadata The metadata.
	 */
	public function setMetadata(array $metadata) {
		$this->metadata = $metadata;
	}

	/**
	 * Set this feed's items.
	 * Each item can contain several fields:
	 * * `title`: the item title
	 * * `link`: the item URL
	 * * `description`: the item description
	 * * `content`: the item content
	 * * `createdAt`: the item creation date
	 * * `updatedAt`: the item update date
	 * @param array $items Items for this feed.
	 */
	public function setItems(array $items) {
		$this->items = $items;
	}

	/**
	 * Set this feed format.
	 * Can be either `rss` or `atom`.
	 * @param string $format The feed format.
	 */
	public function setFormat($format) {
		$supportedFormats = array('rss', 'atom');
		if (!in_array($format, $supportedFormats)) {
			throw new \InvalidArgumentException('Invalid output format: '.$format);
		}

		$this->format = $format;
	}
}