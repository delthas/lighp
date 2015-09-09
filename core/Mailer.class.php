<?php
namespace core;

use core\apps\Application;
use core\fs\Pathfinder;
use core\Config;

use Swift_SmtpTransport;
use Swift_SendmailTransport;
use Swift_Mailer;
use Swift_Message;

class Mailer extends ApplicationComponent {
	protected $mailer;
	protected $cfg;

	public function __construct(Application $app) {
		parent::__construct($app);

		$this->cfg = new Config(Pathfinder::getPathFor('config').'/core/email.json');
	}

	protected function mailer() {
		if (!empty($this->mailer)) {
			return $this->mailer;
		}

		$cfg = $this->cfg->read();

		$transport = null;
		switch ($cfg['transport']) {
			case 'smtp':
				$host = (!empty($cfg['host'])) ? $cfg['host'] : '127.0.0.1';
				$port = (!empty($cfg['port'])) ? $cfg['port'] : 25;
				$password = (!empty($cfg['password'])) ? $cfg['password'] : '';

				$transport = new Swift_SmtpTransport($host, $port);

				if (!empty($cfg['username'])) {
					$transport->setUsername($cfg['username']);
				}
				if (!empty($cfg['password'])) {
					$transport->setPassword($cfg['password']);
				}
				if (!empty($cfg['encryption'])) {
					$transport->setEncryption($cfg['encryption']);
				}
				break;
			case 'sendmail':
			default:
				$transport = new Swift_SendmailTransport();
		}

		return $this->mailer = new Swift_Mailer($transport);
	}

	public function send(Swift_Message $message) {
		if (empty($message->getFrom())) {
			$cfg = $this->cfg->read();
			$message->setFrom($cfg['from']);
		}

		return $this->mailer()->send($message);
	}
}