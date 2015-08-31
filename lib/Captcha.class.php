<?php
namespace lib;

use core\submodules\ModuleTranslation;
use core\ApplicationComponent;
use \RuntimeException;

class Captcha extends ApplicationComponent {
	protected $id, $question, $result;

	protected function _generate() {
		$dict = new ModuleTranslation($this->app(), 'captcha');

		$n1 = mt_rand(0, 10);
		$n2 = mt_rand(0, 10);

		$humanNbr = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'height', 'nine', 'ten');

		foreach($humanNbr as $i => $englishNbr) {
			$humanNbr[$i] = $dict->get('numbers.'.$englishNbr);
		}
		$this->question = $dict->get('howMuchIs') . ' ' . $humanNbr[$n1] .' '.$dict->get('plus').' ' . $humanNbr[$n2] . ' ?';
		
		$this->result = $n1 + $n2;
	}

	protected function _remember() {
		$session = $this->app()->httpRequest()->session();

		$captchas = array();
		if ($session->has('captcha')) {
			$captchas = $session->get('captcha');
		}
		
		if ($this->id === null) {
			$this->id = count($captchas);
		}

		$captchas[$this->id] = array(
			'result' => $this->result
		);

		$session->set('captcha', $captchas);

		return $this->id;
	}

	public function question() {
		if (empty($this->question)) {
			$this->_generate();
			$this->_remember();
		}

		return $this->question;
	}

	public function id() {
		if ($this->id === null) {
			$this->_remember();
		}

		return $this->id;
	}


	public static function build($app) {
		return new self($app);
	}

	public static function check($app, $id, $result) {
		$session = $app->httpRequest()->session();

		if (!$session->has('captcha')) {
			throw new RuntimeException('Your session has expired. Please try again');
		}

		$captchas = $session->get('captcha');
		$id = (int) $id;

		if (!isset($captchas[$id])) {
			throw new RuntimeException('Your session has expired. Please try again');
		}

		$captchaData = $captchas[$id];
		$result = (int) $result;

		// Delete captcha
		unset($captchas[$id]);
		$session->set('captcha', $captchas);

		if ($captchaData['result'] !== $result) {
			throw new RuntimeException('Invalid captcha');
		}
	}
}