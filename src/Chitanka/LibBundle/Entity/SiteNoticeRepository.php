<?php
namespace Chitanka\LibBundle\Entity;

/**
 *
 */
class SiteNoticeRepository extends EntityRepository {

	public function findForFrontPage() {
		return $this->findBy(array('isForFrontPage' => true));
	}

	public function getGlobalRandom() {
		return $this->getRandom('e.isActive = 1 AND e.isForFrontPage = 0');
	}
}
