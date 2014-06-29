<?php namespace App\Command;

use App\Legacy\Setup;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

abstract class Command extends ContainerAwareCommand {

	protected function configure() {
		$this->setName($this->getName());
		$this->setDescription($this->getDescription());
		$this->setHelp($this->getHelp());
		foreach ($this->getRequiredArguments() as $argument => $description) {
			$this->addArgument($argument, InputArgument::REQUIRED, $description);
		}
		foreach ($this->getOptionalArguments() as $argument => $descriptionAndValue) {
			list($description, $defaultValue) = $descriptionAndValue;
			$this->addArgument($argument, InputArgument::OPTIONAL, $description, $defaultValue);
		}
		foreach ($this->getArrayArguments() as $argument => $description) {
			$this->addArgument($argument, InputArgument::IS_ARRAY, $description);
		}
		foreach ($this->getBooleanOptions() as $option => $description) {
			$this->addOption($option, null, InputOption::VALUE_NONE, $description);
		}
		foreach ($this->getOptionalOptions() as $option => $descriptionAndValue) {
			list($description, $defaultValue) = $descriptionAndValue;
			$this->addOption($option, null, InputOption::VALUE_OPTIONAL, $description, $defaultValue);
		}
	}

	/**
	 * Return an array with all required arguments.
	 * Format is:
	 *     argument name => argument description
	 * @return array
	 */
	protected function getRequiredArguments() {
		return array();
	}

	/**
	 * Return an array with all optional arguments.
	 * Format is:
	 *     argument name => [argument description, default value]
	 * @return array
	 */
	protected function getOptionalArguments() {
		return array();
	}

	/**
	 * Return an array with all arguments which values are arrays.
	 * Format is:
	 *     argument name => argument description
	 * @return array
	 */
	protected function getArrayArguments() {
		return array();
	}

	/**
	 * Return an array with all boolean options.
	 * Format is:
	 *     option name => option description
	 * @return array
	 */
	protected function getBooleanOptions() {
		return array();
	}

	/**
	 * Return an array with all optional options.
	 * Format is:
	 *     option name => [option description, default value]
	 * @return array
	 */
	protected function getOptionalOptions() {
		return array();
	}

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
		$this->updateCountByParents($em, 'App:Label', 'NrOfTexts');
	}

	protected function updateBookCountByCategoriesParents(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating books count by categories parents');
		$this->updateCountByParents($em, 'App:Category', 'NrOfBooks');
	}

	/**
	 * @param EntityManager $em
	 * @param string $entity
	 * @param string $field
	 */
	private function updateCountByParents(EntityManager $em, $entity, $field) {
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

	public function printQueries($queries) {
		echo str_replace('*/;', '*/', implode(";\n", $queries) . ";\n");
	}

	public function webDir($file = null) {
		return __DIR__ . '/../../web' . ($file ? "/$file" : '');
	}

	public function contentDir($file = null) {
		return __DIR__ . '/../../web/content' . ($file ? "/$file" : '');
	}

	private $olddb;
	/** @return \App\Legacy\mlDatabase */
	protected function olddb() {
		if (!$this->olddb) {
			Setup::doSetup($this->getContainer());
			$this->olddb = Setup::db();
		}
		return $this->olddb;
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
