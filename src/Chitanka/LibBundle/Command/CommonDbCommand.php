<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;


class CommonDbCommand extends Command
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
		$output->writeln('Updating text counts by labels');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT label_id, COUNT(text_id) count FROM text_label GROUP BY label_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE label SET nr_of_texts = %d WHERE id = %d', $data['count'], $data['label_id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function updateTextCountByLabelsParents(OutputInterface $output, $em)
	{
		$output->writeln('Updating text counts by labels parents');
		$this->_updateCountByParents($em, 'LibBundle:Label', 'NrOfTexts');
	}

	protected function updateBookCountByCategoriesParents(OutputInterface $output, $em)
	{
		$output->writeln('Updating book counts by categories parents');
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

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT text_id, COUNT(text_id) count FROM text_comment GROUP BY text_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE text SET comment_count = %d WHERE id = %d', $data['count'], $data['text_id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	/**
	* @RawSql
	*/
	protected function updateBookCountByCategories(OutputInterface $output, $em)
	{
		$output->writeln('Updating book count by categories');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT category_id, COUNT(id) count FROM book GROUP BY category_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE category SET nr_of_books = %d WHERE id = %d', $data['count'], $data['category_id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function executeUpdates($updates, $connection)
	{
		$connection->beginTransaction();
		foreach ($updates as $update) {
			$connection->executeUpdate($update);
		}
		$connection->commit();
	}
}
