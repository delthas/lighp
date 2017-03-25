<?php
namespace core\dao\json;

class Collection implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable {
	protected $data;

	public function __construct($data = array()) {
		if (!$data instanceof \Traversable && !is_array($data)) {
			throw new \InvalidArgumentException('Invalid data : variable must be an array or traversable');
		}

		$this->data = array();

		foreach($data as $id => $item) {
			if ($item instanceof Item) {
				$this->data[] = $item;
			} else if (is_array($item) || $item instanceof \Traversable) {
				$this->data[] = new Item($item);
			}
		}
	}

	public function filter($filter = array()) {
		$data = $this->data;
		$filteredItems = array();

		if (is_array($filter) && count($filter) > 0) {
			foreach($this->data as $id => $item) {
				foreach($filter as $key => $value) {
					if (isset($item[$key]) && $item[$key] == $value) {
						$filteredItems[] = $item;
						break;
					}
				}
			}
		} elseif (is_callable($filter)) {
			foreach($this->data as $id => $item) {
				if ($filter($item)) {
					$filteredItems[] = $item;
				}
			}
		}

		return new $this($filteredItems);
	}

	public function sort($sortBy) {
		if (is_string($sortBy)) {
			if (strpos($sortBy, ' ') !== false) {
				$sortByOptions = explode(' ', $sortBy, 2);
				$sortBy = array($sortByOptions[0] => $sortByOptions[1]);
			} else {
				$sortBy = array($sortBy => null);
			}
		} elseif (is_array($sortBy)) {
			if (array_values($sortBy) === $sortBy) {
				// It's a list, convert it to associative array
				$sortByKeys = $sortBy;
				$sortBy = array();
				foreach ($sortByKeys as $key) {
					$sortBy[$key] = null;
				}
			}
		}

		usort($this->data, function ($a, $b) use($sortBy) {
			foreach ($sortBy as $key => $flag) {
				if ($a[$key] == $b[$key]) {
					continue;
				}

				if ($a[$key] < $b[$key]) {
					return ($flag == 'desc') ? 1 : -1;
				} else {
					return ($flag == 'desc') ? -1 : 1;
				}
			}
			return 0;
		});

		return $this;
	}

	public function getRange($from, $limit = -1) {
		$from = (int) $from;
		$len = count($this->data);
		if ($limit < 0) {
			$limit = $len;
		} else {
			$limit = min($from + $limit, $len);
		}

		$items = array();

		for ($i = $from; $i < $limit; $i++) {
			$items[] = $this->data[$i];
		}

		return new $this($items);
	}

	public function convertToArray($class = null) {
		$list = array();

		foreach($this->data as $item) {
			if (!empty($class)) {
				$item = new $class($item);
			} else {
				$item = $item->toArray();
			}

			$list[] = $item;
		}

		return $list;
	}

	public function offsetGet($id) {
		return isset($this->data[(int) $id]) ? $this->data[(int) $id] : null;
	}

	public function offsetSet($id, $item) {
		if (!$item instanceof Item) {
			throw new \InvalidArgumentException('A collection item must be an instance of Item');
		}

		if ($id === null) {
			$this->data[] = $item;
		} else {
			$this->data[(int) $id] = $item;
		}
	}

	public function offsetExists($id) {
		return isset($this->data[(int) $id]);
	}

	public function offsetUnset($id) {
		unset($this->data[(int) $id]);
	}

	public function getIterator() {
		return new \ArrayIterator($this->data);
	}

	public function count() {
		return count($this->data);
	}

	public function jsonSerialize() {
		return $this->data;
	}

	public function last() {
		return end($this->data);
	}
}
