<?php

namespace lib\manager;

use lib\entities\Page;

class PagesManager_json extends PagesManager {
	use BasicManager_json;

	protected $path = 'pages/pages';
	protected $primaryKey = 'name';
}