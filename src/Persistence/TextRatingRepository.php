<?php namespace App\Persistence;

use App\Entity\Text;
use App\Entity\TextRating;
use App\Entity\User;
use Doctrine\ORM\NoResultException;

/**
 *
 */
class TextRatingRepository extends EntityRepository {

	/**
	 * Get user rating for a given text.
	 * Return new Rating object if none exists.
	 * @param Text $text
	 * @param User $user
	 * @return TextRating
	 */
	public function getByTextAndUser(Text $text, User $user) {
		if ($user->isAnonymous()) {
			return new TextRating($text, $user);
		}
		try {
			return $this->createQueryBuilder('r')
				->andWhere('r.text = :text')
				->andWhere('r.user = :user')
				->setParameters([
					'text' => $text->getId(),
					'user' => $user->getId(),
				])
				->setMaxResults(1)
				->getQuery()
				->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
				->getSingleResult();
		} catch (NoResultException $e) {
			return new TextRating($text, $user);
		}
	}

	/**
	 * Get all ratings for a given text.
	 * @param Text $text
	 * @return array Ratings with users who gave them
	 */
	public function getByText(Text $text) {
		return $this->getQueryBuilder()
			->select('e', 'u')
			->leftJoin('e.user', 'u')
			->where('e.text = ?1')->setParameter(1, $text)
			->orderBy('e.date', 'desc')
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
	}

	/**
	 * Get all ratings of a given user.
	 * @param User $user
	 * @return array All user ratings
	 */
	public function getByUser(User $user) {
		return $this->getQueryBuilder()
			->select('e', 't')
			->leftJoin('e.text', 't')
			->where('e.user = ?1')->setParameter(1, $user)
			->orderBy('e.date', 'desc')
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
	}
}
