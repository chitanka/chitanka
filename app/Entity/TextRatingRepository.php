<?php namespace App\Entity;

use Doctrine\ORM\NoResultException;

/**
 *
 */
class TextRatingRepository extends EntityRepository {
	static public $ratings = array(
		6 => '6 — Шедьовър',
		5 => '5 — Много добро',
		4 => '4 — Добро',
		3 => '3 — Посредствено',
		2 => '2 — Лошо',
		1 => '1 — Отвратително',
	);

	/**
	 * Get user rating for a given text
	 * @param Text|int $text
	 * @param User|int $user
	 * @return float
	 */
	public function getByTextAndUser($text, $user) {
		$dql = sprintf('SELECT r FROM %s r WHERE r.text = %d AND r.user = %d',
			$this->getEntityName(),
			(is_object($text) ? $text->getId() : $text),
			(is_object($user) ? $user->getId() : $user)
		);
		$query = $this->_em->createQuery($dql)->setMaxResults(1);

		try {
			return $query->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		}
	}

	/**
	 * Get all ratings for a given text.
	 * @param Text $text
	 * @return array  Ratings with users who gave them
	 */
	public function getByText(Text $text) {
		return $this->getQueryBuilder()
			->select('e', 'u')
			->leftJoin('e.user', 'u')
			->where('e.text = ?1')->setParameter(1, $text)
			->orderBy('e.date', 'desc')
			->getQuery()->getArrayResult();
	}
}
