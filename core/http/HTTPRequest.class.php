<?php

namespace core\http;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * The HTTP request.
 * @author emersion
 * @since 1.0alpha1
 */
class HTTPRequest {
	protected $session;

	public function __construct(Session $session) {
		$this->session = $session;
	}

	/**
	 * Get a cookie's content.
	 * @param string $key The cookie's name.
	 * @return string The cookie's content.
	 */
	public function cookieData($key) {
		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
	}

	/**
	 * Determine if a cookie exists.
	 * @param string $key The cookie's name.
	 * @return bool True if it exists, false otherwise.
	 */
	public function cookieExists($key) {
		return isset($_COOKIE[$key]);
	}

	/**
	 * Get this request's GET data.
	 * @param string $key The data name.
	 * @return string The data's content.
	 */
	public function getData($key) {
		return isset($_GET[$key]) ? $_GET[$key] : null;
	}

	/**
	 * Determine if a GET data exists.
	 * @param string $key The data name.
	 * @return bool True if the data exists, false otherwise.
	 */
	public function getExists($key) {
		return isset($_GET[$key]);
	}

	/**
	 * Get this request's POST data.
	 * @param string $key The data name.
	 * @return string The data's content.
	 */
	public function postData($key) {
		return isset($_POST[$key]) ? $_POST[$key] : null;
	}

	/**
	 * Determine if a POST data exists.
	 * @param string $key The data name.
	 * @return bool True if the data exists, false otherwise.
	 */
	public function postExists($key) {
		return isset($_POST[$key]);
	}

	/**
	 * Get this request's method.
	 * The method is returned in lower case.
	 * @return string
	 */
	public function method() {
		return strtolower($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Get this request's host.
	 * @return string
	 */
	public function protocol() {
		if (empty($_SERVER['HTTPS']) or $_SERVER['HTTPS'] == 'off') {
			return 'http';
		}
		return 'https';
	}

	/**
	 * Get this request's host.
	 * @return string
	 */
	public function host() {
		return $_SERVER['HTTP_HOST'];
	}

	/**
	 * Get this request's URI.
	 * @return string
	 */
	public function requestURI() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get this request's path.
	 * @return string
	 */
	public function path() {
		$path = $_SERVER['REQUEST_URI'];
		
		// Remove query string
		if (($i = strpos($path, '?')) !== false) {
			$path = substr($path, 0, $i);
		}

		return $path;
	}

	/**
	 * Get this request's query string.
	 * @return string
	 */
	public function queryString() {
		return (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : null;
	}

	/**
	 * Get this request's URL origin.
	 * @return string
	 */
	public function origin() {
		return $this->protocol() . '://' . $this->host();
	}

	/**
	 * Get this request's full URL.
	 * @return string
	 */
	public function href() {
		return $this->origin() . $this->requestURI();
	}

	/**
	 * Get this request's HTTP referer.
	 * @return string
	 */
	public function referer() {
		return (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
	}

	/**
	 * Get this request's session.
	 * @return Session 
	 */
	public function session() {
		return $this->session;
	}

	/**
	 * Get the user's prefered language.
	 * @return string The user's language.
	 */
	public function lang() {
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return null;
		}

		$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		if (count($languages) == 0) {
			return null;
		}

		return $languages[0];
	}
}