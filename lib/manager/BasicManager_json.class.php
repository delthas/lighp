<?php

namespace lib\manager;

use core\Entity;

/**
 * An implementation of `BasicManager` with jsondb.
 * @author emersion <contact@emersion.fr>
 * @since 1.0alpha3
 */
trait BasicManager_json {
	//protected $path;

	/**
	 * The entities file.
	 * @var \core\dao\json\File
	 */
	protected $file;

	protected function open() {
		if (empty($this->file)) {
			$this->file = $this->dao->open($this->path);
		}

		return $this->file;
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
		if (isset($options['offset'])) {
			$items = array_slice($items, $options['offset']);
		}
		if (isset($options['limit'])) {
			$items = array_chunk($items, $options['limit']);
		}
		if (isset($options['sortBy'])) {
			$sortKey = $options['sortBy'];
			$items = usort($items, function ($a, $b) {
				if ($a[$sortKey] == $b[$sortKey]) {
					return 0;
				}
				if ($a[$sortKey] < $b[$sortKey]) {
					return 1;
				}
				return -1;
			});
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

	public function insert(Entity $entity) {
		$this->checkEntityType($entity);

		$file = $this->open();
		$items = $file->read();

		$item = $this->dao->createItem($entity->toArray());
		$items[] = $item;
		$file->write($items);
	}

	public function update(Entity $entity) {
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
