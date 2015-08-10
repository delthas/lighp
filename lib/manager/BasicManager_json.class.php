<?php

namespace lib\manager;

use core\Entity;

trait BasicManager_json {
	//protected $path;
	//protected $primaryKey = 'id';

	protected $file;

	protected function open() {
		if (empty($this->file)) {
			$this->file = $this->dao->open($this->path);
		}

		return $this->file;
	}

	public function get($entityKey) {
		$file = $this->open();
		$items = $file->read()->filter(array($this->primaryKey => $entityKey));

		if (empty($items)) {
			throw new \RuntimeException('Cannot find an entity '.$this->entity.' with '.$this->primaryKey.' "'.$entityKey.'"');
		}

		return new $this->entity($items[0]);
	}

	public function listAll() {
		$file = $this->open();
		$items = $file->read();

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
