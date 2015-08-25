<?php

namespace core;

use core\data\JsonSerializable;

/**
 * A data entity.
 * @author Simon Ser
 * @since 1.0alpha1
 */
abstract class Entity implements \ArrayAccess, JsonSerializable {
	/**
	 * This entity's unique identifier. Its type depends on the database backend.
	 * @var int|string
	 */
	protected $id;

	/**
	 * The creation date of this entity.
	 * @var int
	 */
	protected $createdAt;

	/**
	 * The last update date of this entity.
	 * @var int
	 */
	protected $updatedAt;

	/**
	 * Initialize this entity.
	 * @param array $data The data to store in this entity.
	 */
	public function __construct($data = array()) {
		if (!$data instanceof \Traversable && !is_array($data)) {
			throw new \InvalidArgumentException('Invalid data : variable must be an array or traversable');
		}

		if (!empty($data)) {
			$this->hydrate($data);
		}
	}

	/**
	 * Determine if this entity is new.
	 * @return boolean True if it is new, false otherwise.
	 */
	public function isNew() {
		return empty($this->id);
	}

	/**
	 * Get this entity's identifier.
	 * @return int
	 */
	public function id() {
		return $this->id;
	}

	public function createdAt() {
		return $this->createdAt;
	}

	public function updatedAt() {
		return $this->updatedAt;
	}

	/**
	 * Set this entity's identifier.
	 * @param int $id The new identifier.
	 */
	public function setId($id) {
		$this->id = (int) $id;
	}

	public function setCreatedAt($createdAt) {
		if (!is_int($createdAt) && $createdAt !== null) {
			throw new \InvalidArgumentException('Invalid entity creation timestamp: not a timestamp');
		}
		$this->createdAt = $createdAt;
	}

	public function setUpdatedAt($updatedAt) {
		if (!is_int($updatedAt) && $updatedAt !== null) {
			throw new \InvalidArgumentException('Invalid entity update timestamp: not a timestamp');
		}
		$this->updatedAt = $updatedAt;
	}

	/**
	 * Store data in this entity.
	 * @param array $data The data to store.
	 */
	public function hydrate($data) {
		if (!$data instanceof \Traversable && !is_array($data)) {
			throw new \InvalidArgumentException('Invalid data : variable must be an array or traversable');
		}

		foreach ($data as $key => $value) {
			$method = 'set'.ucfirst($key);

			if (is_callable(array($this, $method))) {
				$this->$method($value);
			}
		}
	}

	public function offsetGet($var) {
		if (property_exists($this, $var) && is_callable(array($this, $var))) {
			return $this->$var();
		}
	}

	public function offsetSet($var, $value) {
		$method = 'set'.ucfirst($var);

		if (property_exists($this, $var) && is_callable(array($this, $method))) {
			$this->$method($value);
		} else {
			throw new \Exception('Cannot set '.$var.': this property doesn\'t exist or is read-only');
		}
	}

	public function offsetExists($var) {
		return property_exists($this, $var) && is_callable(array($this, $var));
	}

	public function offsetUnset($var) {
		throw new \Exception('Cannot delete any field');
	}

	/**
	 * Convert this entity to an array containing the data.
	 * @return array This entity's data.
	 */
	public function toArray() {
		$data = array();

		foreach(get_object_vars($this) as $key => $value) {
			if (isset($this[$key])) {
				$data[$key] = $this[$key];
			}
		}

		return $data;
	}

	public function jsonSerialize() {
		return $this->toArray();
	}
}
