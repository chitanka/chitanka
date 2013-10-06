<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\NoResultException;

class TextRepository extends EntityRepository
{
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
		'other',
	);

	protected $queryableFields = array(
		'id',
		'title',
		'subtitle',
		'orig_title',
		'orig_subtitle',
	);


	public function get($id)
	{
		return $this->_em->createQueryBuilder()
			->select('t', 'ta', 'tt', 's', 'l', 'b', 'ol', 'tl', 'r')
			->from($this->getEntityName(), 't')
			->leftJoin('t.textAuthors', 'ta')
			->leftJoin('t.textTranslators', 'tt')
			->leftJoin('t.series', 's')
			->leftJoin('t.labels', 'l')
			->leftJoin('t.books', 'b')
			->leftJoin('t.orig_license', 'ol')
			->leftJoin('t.trans_license', 'tl')
			//->leftJoin('t.headers', 'h') // takes up too much memory by many rows
			->leftJoin('t.cur_rev', 'r')
			->where('t.id = ?1')->setParameter(1, $id)
			->getQuery()->getSingleResult();
	}

	public function getByPrefix($prefix, $page = 1, $limit = null)
	{
		try {
			$ids = $this->getIdsByPrefix($prefix, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	public function getIdsByPrefix($prefix, $page, $limit)
	{
		$where = $prefix ? "WHERE t.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT t.id FROM %s t %s ORDER BY t.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		if (empty($ids)) {
			throw new NoResultException;
		}

		return $ids;
	}

	public function countByPrefix($prefix)
	{
		$where = $prefix ? "WHERE t.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT COUNT(t.id) FROM %s t %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}


	public function getByType($type, $page = 1, $limit = null)
	{
		try {
			$ids = $this->getIdsByType($type, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	public function getIdsByType($type, $page, $limit)
	{
		$where = "WHERE t.type = '$type'";
		$dql = sprintf('SELECT t.id FROM %s t %s ORDER BY t.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		if (empty($ids)) {
			throw new NoResultException;
		}

		return $ids;
	}

	public function countByType($type)
	{
		$where = "WHERE t.type = '$type'";
		$dql = sprintf('SELECT COUNT(t.id) FROM %s t %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}


	public function getByLabel($labels, $page = 1, $limit = null)
	{
		try {
			$ids = $this->getIdsByLabel($labels, $page, $limit);
		} catch (NoResultException $e) {
			return array();
		}

		return $this->getByIds($ids);
	}

	protected function getIdsByLabel($labels, $page, $limit)
	{
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
	public function countByLabel($labels)
	{
		return $this->_em->getConnection()->fetchColumn(sprintf('SELECT COUNT(DISTINCT tl.text_id) FROM text_label tl WHERE tl.label_id IN (%s)', implode(',', $labels)), array(), 0);
	}


	/**
	 * RAW_SQL
	 */
	public function deleteTextLabel($text, $label)
	{
		if ($text instanceof Text) {
			$text = $text->getId();
		}
		if ($label instanceof Label) {
			$label = $label->getId();
		}
		$this->_em->getConnection()->executeUpdate(sprintf('DELETE FROM text_label WHERE text_id = %d AND label_id = %d', $text, $label));

		return $this;
	}

	public function findByAuthor($author, $groupBySeries = true)
	{
		$texts = $this->getQueryBuilder()
			->leftJoin('e.textAuthors', 'ta')
			->where('ta.person = ?1')->setParameter(1, $author->getId())
			->orderBy('s.name, e.sernr, e.type, e.title')
			->getQuery()->getArrayResult();

		if ($groupBySeries) {
			$texts = $this->groupTexts($texts);
		}

		return $texts;
	}


	public function findByTranslator($translator)
	{
		$texts = $this->getQueryBuilder()
			->leftJoin('e.textTranslators', 'tt')
			->where('tt.person = ?1')->setParameter(1, $translator->getId())
			->orderBy('e.type, e.title')
			->getQuery()->getArrayResult();

		$texts = $this->groupTexts($texts, false);

		return $texts;
	}

	public function getByIds($ids, $orderBy = null)
	{
		$texts = $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return static::joinPersonKeysForTexts($texts);
	}


	public function getByTitles($title, $limit = null)
	{
		$q = $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.orig_title LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($title))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return static::joinPersonKeysForTexts($q->getArrayResult());
	}


	public function getQueryBuilder($orderBys = null)
	{
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


	public function getBySeries($series)
	{
		$texts = $this->_em->createQueryBuilder()
			->select('e', 'a', 'ap', 'ol', 'tl')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.textAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('e.orig_license', 'ol')
			->leftJoin('e.trans_license', 'tl')
			->where('e.series = ?1')->setParameter(1, $series->getId())
			->addOrderBy('e.sernr, e.title')
			->getQuery()->getArrayResult();

		return $this->putIdAsKey($texts);
	}

	public function getCountsByType()
	{
		return $this->_em->createQueryBuilder()
			->select('e.type', 'COUNT(e.id)')
			->from($this->getEntityName(), 'e')
			->groupBy('e.type')
			->getQuery()->getResult('key_value');
	}

	public function getRandomId($where = '')
	{
		return parent::getRandomId("e.removed_notice IS NULL");
	}

	public function isValidType($type)
	{
		return in_array($type, self::$types);
	}

	public function getTypes()
	{
		return self::$types;
	}


	protected function groupTexts($texts, $groupBySeries = true)
	{
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


	protected function putIdAsKey($texts)
	{
		$textsById = array();
		foreach ($texts as $text) {
			$textsById[$text['id']] = $text;
		}

		return $textsById;
	}

	public static function joinPersonKeysForTexts($texts)
	{
		foreach ($texts as $k => $text) {
			if (isset($text['textAuthors'])) {
				$authors = array();
				foreach ($text['textAuthors'] as $textAuthor) {
					if ($textAuthor['pos'] >= 0) {
						$authors[] = $textAuthor['person'];
					}
				}
				$texts[$k]['authors'] = $authors;
			}
		}
		return $texts;
	}
}
