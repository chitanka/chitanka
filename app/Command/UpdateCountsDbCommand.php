<?php namespace App\Command;

use App\Entity\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountsDbCommand extends Command {

	public function getName() {
		return 'db:update-counts';
	}

	public function getDescription() {
		return 'Update some total counts in the database';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command updates some total counts in the database. For example number of texts by every label, or number of books by every category.';
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->updateCounts($output, $this->getEntityManager());
		$output->writeln('Done.');
	}

	private function updateCounts(OutputInterface $output, $em) {
		$this->updateTextCountByLabels($output, $em);
		$this->updateTextCountByLabelsParents($output, $em);
		$this->updateBookCountBySequences($output, $em);
		$this->updateBookCountByCategories($output, $em);
		$this->updateCommentCountByTexts($output, $em);
		// disable for now, TODO fix pagination by parent categories
		//$this->updateBookCountByCategoriesParents($output, $em);
	}

	/**
	 * @RawSql
	 */
	private function updateTextCountByLabels(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating text counts by labels');
		$update = $this->maintenanceSql('UPDATE label l SET nr_of_texts = (SELECT COUNT(*) FROM text_label WHERE label_id = l.id)');
		$em->getConnection()->executeUpdate($update);
	}

	private function updateTextCountByLabelsParents(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating text counts by labels parents');
		$this->updateCountByParents($em, 'App:Label', 'NrOfTexts');
	}

	/**
	 * @RawSql
	 */
	private function updateBookCountBySequences(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating book counts by sequences');
		$update = $this->maintenanceSql('UPDATE sequence s SET nr_of_books = (SELECT COUNT(*) FROM book WHERE sequence_id = s.id)');
		$em->getConnection()->executeUpdate($update);
	}

	/**
	 * @RawSql
	 */
	private function updateBookCountByCategories(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating book counts by categories');
		$update = $this->maintenanceSql('UPDATE category c SET nr_of_books = (SELECT COUNT(*) FROM book WHERE category_id = c.id)');
		$em->getConnection()->executeUpdate($update);
	}

	private function updateBookCountByCategoriesParents(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating book counts by categories parents');
		$this->updateCountByParents($em, 'App:Category', 'NrOfBooks');
	}

	/**
	 * @RawSql
	 */
	private function updateCommentCountByTexts(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating comment counts by texts');
		$update = $this->maintenanceSql('UPDATE text t SET comment_count = (SELECT COUNT(*) FROM text_comment WHERE text_id = t.id)');
		$em->getConnection()->executeUpdate($update);
	}

	/**
	 * @param EntityManager $em
	 * @param string $entity
	 * @param string $field
	 */
	private function updateCountByParents(EntityManager $em, $entity, $field) {
		$dirty = [];
		$repo = $em->getRepository($entity);
		foreach ($repo->findAll() as $item) {
			if (in_array($item->getId(), $dirty)) {
				$item = $repo->find($item->getId());
			}
			$parent = $item->getParent();
			if ($parent) {
				$count = call_user_func([$item, "get{$field}"]);
				do {
					call_user_func([$parent, "inc{$field}"], $count);
					$em->persist($parent);
					$dirty[] = $parent->getId();
				} while (null !== ($parent = $parent->getParent()));
			}
		}

		$em->flush();
	}
}
