<?php
namespace lib\manager;

abstract class NotificationsManager extends \core\Manager {
	use BasicManager;

	protected $entity = '\lib\entities\Notification';
	protected $primaryKey = 'id';
}