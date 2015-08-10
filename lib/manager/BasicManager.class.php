<?php

namespace lib\manager;

use core\Entity;

interface BasicManager {
	//protected $entity;

	public function get($entityKey);
	public function listAll();

	public function insert(Entity $entity);
	public function update(Entity $entity);
	public function delete($entityKey);
}