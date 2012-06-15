<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Util\String;


class CommonDbCommand extends ContainerAwareCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('db:common')
			->setDescription('Does nothing. Only encapsulates common database stuff')
		;
	}

	/**
	 * @RawSql
	 */
	protected function updateTextCountByLabels(OutputInterface $output, $em)
	{
		$output->writeln('Updating texts count by labels');
		$update = 'UPDATE label l SET nr_of_texts = (SELECT COUNT(*) FROM text_label WHERE label_id = l.id)';
		$em->getConnection()->executeUpdate($update);
	}


	protected function updateTextCountByLabelsParents(OutputInterface $output, $em)
	{
		$output->writeln('Updating texts count by labels parents');
		$this->_updateCountByParents($em, 'LibBundle:Label', 'NrOfTexts');
	}

	protected function updateBookCountByCategoriesParents(OutputInterface $output, $em)
	{
		$output->writeln('Updating books count by categories parents');
		$this->_updateCountByParents($em, 'LibBundle:Category', 'NrOfBooks');
	}

	protected function _updateCountByParents($em, $entity, $field)
	{
		$dirty = array();
		$repo = $em->getRepository($entity);
		foreach ($repo->findAll() as $item) {
			if (in_array($item->getId(), $dirty)) {
				$item = $repo->find($item->getId());
			}
			$parent = $item->getParent();
			if ($parent) {
				$count = call_user_func(array($item, "get{$field}"));
				do {
					call_user_func(array($parent, "inc{$field}"), $count);
					$em->persist($parent);
					$dirty[] = $parent->getId();
				} while (null !== ($parent = $parent->getParent()));
			}
		}

		$em->flush();
	}


	/**
	 * @RawSql
	 */
	protected function updateCommentCountByTexts(OutputInterface $output, $em)
	{
		$output->writeln('Updating comments count by texts');
		$update = 'UPDATE text t SET comment_count = (SELECT COUNT(*) FROM text_comment WHERE text_id = t.id)';
		$em->getConnection()->executeUpdate($update);
	}


	/**
	 * @RawSql
	 */
	protected function updateBookCountByCategories(OutputInterface $output, $em)
	{
		$output->writeln('Updating books count by categories');
		$update = 'UPDATE category c SET nr_of_books = (SELECT COUNT(*) FROM book WHERE category_id = c.id)';
		$em->getConnection()->executeUpdate($update);
	}


	protected function executeUpdates($updates, $connection)
	{
		$connection->beginTransaction();
		foreach ($updates as $update) {
			$connection->executeUpdate($update);
		}
		$connection->commit();
	}


	public function buildTextHeadersUpdateQuery($file, $textId, $headlevel)
	{
		require_once __DIR__ . '/../Legacy/headerextract.php';

		$data = array();
		foreach (\Chitanka\LibBundle\Legacy\makeDbRows($file, $headlevel) as $row) {
			$name = $row[2];
			$name = strtr($name, array('_'=>''));
			$name = $this->olddb()->escape(String::my_replace($name));
			$data[] = array($textId, $row[0], $row[1], $name, $row[3], $row[4]);
		}
		$qs = array();
		$qs[] = $this->olddb()->deleteQ('text_header', array('text_id' => $textId));
		if ( !empty($data) ) {
			$fields = array('text_id', 'nr', 'level', 'name', 'fpos', 'linecnt');
			$qs[] = $this->olddb()->multiinsertQ('text_header', $data, $fields);
		}

		return $qs;
	}

	public function printQueries($queries)
	{
		echo str_replace('*/;', '*/', implode(";\n", $queries) . ";\n");
	}

	public function webDir($file = null)
	{
		return __DIR__ . '/../../../../web' . ($file ? "/$file" : '');
	}

	private $_olddb;
	/** @return mlDatabase */
	protected function olddb()
	{
		if ( ! $this->_olddb) {
			Setup::doSetup($this->getContainer());
			$this->_olddb = Setup::db();
		}
		return $this->_olddb;
	}
}
