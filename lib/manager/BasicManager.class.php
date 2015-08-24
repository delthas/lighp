<?php

namespace lib\manager;

use core\Entity;

/**
 * A basic enity manager.
 * @author emersion <contact@emersion.fr>
 * @since 1.0alpha3
 */
trait BasicManager {
	//protected $entity;
	//protected $primaryKey = 'id';
	
	public function __construct($dao) {
		parent::__construct($dao);

		if (!isset($this->entity)) {
			throw new \LogicException(__CLASS__.' has no $entity');
		}
		if (!isset($this->primaryKey)) {
			$this->primaryKey = 'id';
		}
	}

	// GETTERS

	/**
	 * Get a single entity.
	 * @param  mixed $entityKey The entity key, as defined in `$primaryKey`.
	 * @return Entity The entity.
	 */
	abstract public function get($entityKey);

	/**
	 * List entities given a criteria and options.
	 * 
	 * If `$filter` is an array, only elements having the same attributes will be returned.
	 * If it is a function, each entity will be processed through this function and will be keeped only if `true` is returned.
	 *
	 * Options can contain several fields:
	 * * `offset` and `limit`
	 * * `sortBy`
	 * 
	 * @param  array|callable $filter A filter.
	 * @param  array $options Options.
	 * @return Entity[] A list of entities.
	 */
	abstract public function listBy($filter = array(), array $options = array());

	/**
	 * List all entities.
	 * @return Entity[] A list of entities.
	 */
	abstract public function listAll();

	// SETTERS

	/**
	 * Insert a new entity in the database.
	 * @param Entity|array $entity The new entity.
	 */
	abstract public function insert($entity);

	/**
	 * Update an entity already stored in the database.
	 * @param Entity|array $entity The entity to update.
	 */
	abstract public function update($entity);

	/**
	 * Delete an entity from the database.
	 * @param mixed $entityKey The entity key.
	 */
	abstract public function delete($entityKey);
}