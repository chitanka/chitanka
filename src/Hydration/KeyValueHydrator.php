<?php namespace App\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class KeyValueHydrator extends AbstractHydrator {
	protected function hydrateAllData() {
		$pairs = [];
		foreach ($this->_stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
			$pairs[$row[0]] = $row[1];
		}

		return $pairs;
	}
}
