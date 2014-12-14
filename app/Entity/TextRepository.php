<?php namespace App\Entity;

use Doctrine\ORM\NoResultException;

class TextRepository extends EntityRepository {
	static public $types = array(
		'anecdote',
		'fable',
		'biography',
		'dialogue',
		'docu',
		'essay',
		'interview',
		'gamebook',
		'memo',
		'science',
		'popscience',
		'novelette',
		'ocherk',
		'shortstory',
		'review',
		'novel',
		#'parable',
		'play',
		'letter',
		'poetry',
		'poem',
		'novella',
		'outro',
		'intro',
		'tale',
		'pritcha',
		'travelnotes',
		'speech',
		'article',
		'prosepoetry',
		'screenplay',
		'textbook',
		'feuilleton',
		'haiku',
		'jure',
		'critique',
		'philosophy',
		'religion',
		'historiography',
		'collection',
		'other',
	);

	protected $queryableFields = array(
		'id',
		'title',
		'subtitle',
		'origTitle',
		'origSubtitle',
	);

	/**
	 * @param int $id
	 * @return Text
	 */
	public function get($id, $fetchRelations = true) {
		if (!$fetchRelations) {
			return $this->find($id);
		}
		return $this->_em->createQueryBuilder()
			->select('t', 'ta', 'tt', 's', 'l', 'b', 'ol', 'tl', 'r')
			->from($this->getEntityName(), 't')
			->leftJoin('t.textAuthors', 'ta')
			->leftJoin('t.textTranslators', 'tt')
			->leftJoin('t.series', 's')
			->leftJoin('t.labels', 'l')
			->leftJoin('t.books', 'b')
			->leftJoin('t.origLicense', 'ol')
			->leftJoin('t.transLicense', 'tl')
			//->leftJoin('t.headers', 'h') // takes up too much memory by many rows
			->leftJoin('t.curRev', 'r')
			->where('t.id = ?1')->setParameter(1, $id)
			->getQuery()->getSingleResult();
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 */
	public function getByPrefix($prefix, $page = 1, $limit = null) {
		try {
			$ids = $this->getIdsByPrefix($prefix, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 */
	public function getIdsByPrefix($prefix, $page, $limit) {
		$where = $prefix ? "WHERE t.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT t.id FROM %s t %s ORDER BY t.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		if (empty($ids)) {
			throw new NoResultException;
		}

		return $ids;
	}

	/**
	 * @param string $prefix
	 */
	public function countByPrefix($prefix) {
		$where = $prefix ? "WHERE t.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT COUNT(t.id) FROM %s t %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $type
	 * @param int $page
	 * @param int $limit
	 */
	public function getByType($type, $page = 1, $limit = null) {
		try {
			$ids = $this->getIdsByType($type, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	/**
	 * @param string $type
	 * @param int $page
	 * @param int $limit
	 */
	public function getIdsByType($type, $page, $limit) {
		$where = "WHERE t.type = '$type'";
		$dql = sprintf('SELECT t.id FROM %s t %s ORDER BY t.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		if (empty($ids)) {
			throw new NoResultException;
		}

		return $ids;
	}

	/**
	 * @param string $type
	 */
	public function countByType($type) {
		$where = "WHERE t.type = '$type'";
		$dql = sprintf('SELECT COUNT(t.id) FROM %s t %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	/**
	 * @param array $labels
	 * @param int $page
	 * @param int $limit
	 */
	public function getByLabel($labels, $page = 1, $limit = null) {
		try {
			$ids = $this->getIdsByLabel($labels, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	/**
	 * @param array $labels
	 * @param int $page
	 * @param int $limit
	 */
	protected function getIdsByLabel($labels, $page, $limit) {
		$dql = sprintf('SELECT DISTINCT t.id FROM %s t JOIN t.labels l WHERE l.id IN (%s) ORDER BY t.title', $this->getEntityName(), implode(',', $labels));
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		if (empty($ids)) {
			throw new NoResultException;
		}

		return $ids;
	}

	/**
	 * RAW_SQL
	 */
	public function countByLabel($labels) {
		return $this->_em->getConnection()->fetchColumn(sprintf('SELECT COUNT(DISTINCT tl.text_id) FROM text_label tl WHERE tl.label_id IN (%s)', implode(',', $labels)), array(), 0);
	}

	/**
	 * @param Person $author
	 */
	public function findByAuthor($author, $groupBySeries = true) {
		$texts = $this->getQueryBuilder()
			->leftJoin('e.textAuthors', 'ta')
			->where('ta.person = ?1')->setParameter(1, $author->getId())
			->orderBy('s.name, e.sernr, e.type, e.title')
			->getQuery()->getArrayResult();

		$texts = WorkSteward::joinPersonKeysForTexts($texts);

		if ($groupBySeries) {
			$texts = $this->groupTexts($texts);
		}

		return $texts;
	}

	/**
	 * @param Person $translator
	 */
	public function findByTranslator($translator) {
		$texts = $this->getQueryBuilder()
			->leftJoin('e.textTranslators', 'tt')
			->where('tt.person = ?1')->setParameter(1, $translator->getId())
			->orderBy('e.type, e.title')
			->getQuery()->getArrayResult();

		$texts = WorkSteward::joinPersonKeysForTexts($texts);
		$texts = $this->groupTexts($texts, false);

		return $texts;
	}

	/**
	 * @param array $ids
	 * @param string $orderBy
	 * @return array
	 */
	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return WorkSteward::joinPersonKeysForTexts($texts);
	}

	/**
	 * @param string $title
	 * @param int $limit
	 */
	public function getByTitles($title, $limit = null) {
		$q = $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.origTitle LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($title))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return WorkSteward::joinPersonKeysForTexts($q->getArrayResult());
	}

	/**
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		return $this->_em->createQueryBuilder()
			->select('e', 'a', 'ap', 't', 'tp', 's')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.series', 's')
			->leftJoin('e.textAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('e.textTranslators', 't')
			->leftJoin('t.person', 'tp')
			->orderBy('e.title');
	}

	/**
	 * @param Series $series
	 * @return array
	 */
	public function getBySeries($series) {
		$texts = $this->_em->createQueryBuilder()
			->select('e', 'a', 'ap', 'ol', 'tl')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.textAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('e.origLicense', 'ol')
			->leftJoin('e.transLicense', 'tl')
			->where('e.series = ?1')->setParameter(1, $series->getId())
			->addOrderBy('e.sernr, e.title')
			->getQuery()->getArrayResult();
		$texts = $this->putIdAsKey($texts);
		$texts = WorkSteward::joinPersonKeysForTexts($texts);

		return $texts;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getByQuery($params) {
		return WorkSteward::joinPersonKeysForTexts(parent::getByQuery($params));
	}

	public function getCountsByType() {
		return $this->_em->createQueryBuilder()
			->select('e.type', 'COUNT(e.id)')
			->from($this->getEntityName(), 'e')
			->groupBy('e.type')
			->getQuery()->getResult('key_value');
	}

	public function getRandomId($where = '') {
		return parent::getRandomId("e.removedNotice IS NULL");
	}

	public function isValidType($type) {
		return in_array($type, self::$types);
	}

	public function getTypes() {
		return self::$types;
	}

	protected function groupTexts($texts, $groupBySeries = true) {
		$bySeries = $byType = array();

		foreach ($texts as $text) {
			if ($groupBySeries && $text['series']) {
				$bySeries[ $text['series']['id'] ]['data'] = $text['series'];
				$bySeries[ $text['series']['id'] ]['texts'][$text['id']] = $text;
			} else {
				$byType[ $text['type'] ]['data']['name'] = $text['type'];
				$byType[ $text['type'] ]['texts'][$text['id']] = $text;
			}
		}

		return $bySeries + $byType;
	}

	protected function putIdAsKey($texts) {
		$textsById = array();
		foreach ($texts as $text) {
			$textsById[$text['id']] = $text;
		}

		return $textsById;
	}

}
