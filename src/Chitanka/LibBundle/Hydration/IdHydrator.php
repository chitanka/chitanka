<?php

namespace Chitanka\LibBundle\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class IdHydrator extends AbstractHydrator
{
	protected function _hydrateAll()
	{
		return $this->_stmt->fetchAll(\PDO::FETCH_COLUMN);
	}
}
