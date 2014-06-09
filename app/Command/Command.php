<?php namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use App\Legacy\Setup;
use App\Util\String;

abstract class Command extends ContainerAwareCommand {

	/**
	 * Override only to replace return value in the docblock
	 * @return \Symfony\Bundle\FrameworkBundle\Console\Application
	 */
	public function getApplication() {
		return parent::getApplication();
	}

	protected function getKernel() {
		return $this->getApplication()->getKernel();
	}

	/**
	 * @RawSql
	 */
	protected function updateTextCountByLabels(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating texts count by labels');
		$update = $this->maintenanceSql('UPDATE label l SET nr_of_texts = (SELECT COUNT(*) FROM text_label WHERE label_id = l.id)');
		$em->getConnection()->executeUpdate($update);
	}

	protected function updateTextCountByLabelsParents(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating texts count by labels parents');
		$this->_updateCountByParents($em, 'App:Label', 'NrOfTexts');
	}

	protected function updateBookCountByCategoriesParents(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating books count by categories parents');
		$this->_updateCountByParents($em, 'App:Category', 'NrOfBooks');
	}

	/**
	 * @param EntityManager $em
	 * @param string $entity
	 * @param string $field
	 */
	protected function _updateCountByParents(EntityManager $em, $entity, $field) {
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
	protected function updateCommentCountByTexts(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating comments count by texts');
		$update = $this->maintenanceSql('UPDATE text t SET comment_count = (SELECT COUNT(*) FROM text_comment WHERE text_id = t.id)');
		$em->getConnection()->executeUpdate($update);
	}

	/**
	 * @RawSql
	 */
	protected function updateBookCountByCategories(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating books count by categories');
		$update = $this->maintenanceSql('UPDATE category c SET nr_of_books = (SELECT COUNT(*) FROM book WHERE category_id = c.id)');
		$em->getConnection()->executeUpdate($update);
	}

	protected function executeUpdates($updates, \Doctrine\DBAL\Connection $connection) {
		$connection->beginTransaction();
		foreach ($updates as $update) {
			$connection->executeUpdate($update);
		}
		$connection->commit();
	}

	public function buildTextHeadersUpdateQuery($file, $textId, $headlevel) {
		require_once __DIR__ . '/../Legacy/SfbParserSimple.php';

		$data = array();
		foreach (\App\Legacy\makeDbRows($file, $headlevel) as $row) {
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

	public function printQueries($queries) {
		echo str_replace('*/;', '*/', implode(";\n", $queries) . ";\n");
	}

	public function webDir($file = null) {
		return __DIR__ . '/../../web' . ($file ? "/$file" : '');
	}

	public function contentDir($file = null) {
		return __DIR__ . '/../../web/content' . ($file ? "/$file" : '');
	}

	private $_olddb;
	/** @return \App\Legacy\mlDatabase */
	protected function olddb() {
		if ( ! $this->_olddb) {
			Setup::doSetup($this->getContainer());
			$this->_olddb = Setup::db();
		}
		return $this->_olddb;
	}

	/**
	 * @param string $sql
	 */
	private function maintenanceSql($sql) {
		return '/*MAINTENANCESQL*/'.$sql;
	}

	/** @return \App\Entity\EntityManager */
	protected function getEntityManager() {
		return $this->getContainer()->get('app.entity_manager');
	}
}
