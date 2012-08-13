<?php
namespace Chitanka\LibBundle\Entity;

/**
 * An abstract class for all entities in this bundle
 */
abstract class Entity
{

	public function __call($method, $arguments)
	{
		$getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $method)));
		if (method_exists($this, $getter)) {
			return $this->$getter();
		}
		throw new \Exception(sprintf('Method "%s" for entity "%s" does not exist', $method, get_class($this)));
	}
}
