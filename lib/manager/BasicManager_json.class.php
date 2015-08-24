<?php

namespace lib\manager;

use core\Entity;

/**
 * An implementation of `BasicManager` with jsondb.
 * @author emersion <contact@emersion.fr>
 * @since 1.0alpha3
 */
trait BasicManager_json {
	use BasicManager;

	//protected $path;

	// Doesn't work with PHP 5.4
	/*public function __construct($dao) {
		parent::__construct($dao);

		if (!isset($this->path)) {
			throw new \LogicException(__CLASS__.' has no $path');
		}
	}*/

	/**
	 * The entities file.
	 * @var \core\dao\json\File
	 */
	protected $file;

	/**
	 * The last entity's id.
	 * @var int
	 */
	protected $lastInsertedId;

	protected function open() {
		if (empty($this->file)) {
			$this->file = $this->dao->open($this->path);
		}

		return $this->file;
	}

	protected function lastInsertedId() {
		if ($this->lastInsertedId === null) {
			// Find greatest id

			$file = $this->open();
			$items = $file->read();

			$lastId = 0;
			foreach ($items as $item) {
				$id = $item['id'];
				if ($id > $lastId) {
					$lastId = $id;
				}
			}

			$this->lastInsertedId = $lastId;
		}

		return $this->lastInsertedId;
	}

	protected function buildAllEntities($items) {
		$list = array();

		foreach($items as $item) {
			try {
				$list[] = new $this->entity($item);
			} catch(\InvalidArgumentException $e) {
				continue;
			}
		}

		return $list;
	}

	public function get($entityKey) {
		$file = $this->open();
		$items = $file->read()->filter(array($this->primaryKey => $entityKey));

		if (empty($items)) {
			throw new \RuntimeException('Cannot find an entity '.$this->entity.' with '.$this->primaryKey.' "'.$entityKey.'"');
		}

		return new $this->entity($items[0]);
	}

	public function listBy($filter = array(), array $options = array()) {
		$file = $this->open();
		$items = $file->read()->filter($filter);

		// TODO: check this
		if (isset($options['sortBy'])) {
			$items = $items->sort($options['sortBy']);
		}
		if (isset($options['limit']) || isset($options['offset'])) {
			$offset = (isset($options['offset'])) ? $options['offset'] : 0;
			$limit = (isset($options['limit'])) ? $options['limit'] : -1;
			$items = $items->getRange($offset, $limit);
		}

		return $this->buildAllEntities($items);
	}

	public function listAll() {
		$file = $this->open();
		$items = $file->read();
		return $this->buildAllEntities($items);
	}

	protected function checkEntityType(Entity $entity) {
		if (!($entity instanceof $this->entity)) {
			throw new \InvalidArgumentException('Invalid entity type: must be a '.$this->entity);
		}
	}

	public function insert($entity) {
		// Auto-create entity if we got its data
		if (is_array($entity)) {
			$entity = new $this->entity($entity);
		}

		$this->checkEntityType($entity);

		if ($this->primaryKey == 'id') {
			$entity['id'] = $this->lastInsertedId() + 1;
		}

		$now = time();
		$entity['createdAt'] = $now;
		$entity['updatedAt'] = $now;

		$file = $this->open();
		$items = $file->read();

		$item = $this->dao->createItem($entity->toArray());
		$items[] = $item;
		$file->write($items);

		if ($this->primaryKey == 'id') {
			$this->lastInsertedId++;
		}
	}

	public function update($entity) {
		// Auto-hydrate entity if we got its data
		if (is_array($entity)) {
			$entityData = $entity;
			$entity = $this->get($entity[$this->primaryKey]);
			$entity->hydrate($entityData);
		}

		$this->checkEntityType($entity);

		$entity['updatedAt'] = time();

		$file = $this->open();
		$items = $file->read();
		
		foreach ($items as $i => $item) {
			if ($item[$this->primaryKey] == $entity[$this->primaryKey]) {
				$items[$i] = $this->dao->createItem($entity->toArray());
				$file->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find an entity '.$this->entity.' with '.$this->primaryKey.' "'.$entity[$this->primaryKey].'"');
	}

	public function delete($entityKey) {
		// If the entity object is given, get its primary key
		if ($entityKey instanceof $this->entity) {
			$entityKey = $entityKey[$this->primaryKey];
		}

		$file = $this->open();
		$items = $file->read();

		foreach ($items as $i => $item) {
			if ($item[$this->primaryKey] == $entityKey) {
				unset($items[$i]);
				$file->write($items);
				return;
			}
		}

		throw new \RuntimeException('Cannot find an entity '.$this->entity.' with '.$this->primaryKey.' "'.$entityKey.'"');
	}
}
