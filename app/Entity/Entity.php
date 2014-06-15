<?php namespace App\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * An abstract class for all entities in this bundle
 */
abstract class Entity {

	public static function clearCollection(Collection $collection) {
		$collection->forAll(function($key) use ($collection) {
			$collection->remove($key);
			return true;
		});
	}

}
