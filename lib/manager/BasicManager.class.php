<?php

namespace lib\manager;

use core\Entity;

/**
 * A basic enity manager.
 * @author emersion <contact@emersion.fr>
 * @since 1.0alpha3
 */
interface BasicManager {
	//protected $entity;
	//protected $primaryKey = 'id';
	
	// GETTERS

	/**
	 * Get a single entity.
	 * @param  mixed $entityKey The entity key, as defined in `$primaryKey`.
	 * @return Entity The entity.
	 */
	public function get($entityKey);

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
	public function listBy($filter = array(), array $options = array());

	/**
	 * List all entities.
	 * @return Entity[] A list of entities.
	 */
	public function listAll();

	// SETTERS

	/**
	 * Insert a new entity in the database.
	 * @param Entity $entity The new entity.
	 */
	public function insert(Entity $entity);

	/**
	 * Update an entity already stored in the database.
	 * @param Entity $entity The entity to update.
	 */
	public function update(Entity $entity);

	/**
	 * Delete an entity from the database.
	 * @param mixed $entityKey The entity key.
	 */
	public function delete($entityKey);
}