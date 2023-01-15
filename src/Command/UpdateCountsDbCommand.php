<?php namespace App\Command;

use App\Entity\Category;
use App\Entity\Label;
use App\Persistence\EntityManager;
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
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->updateCounts($output, $this->getEntityManager());
		$output->writeln('Done.');
		return self::SUCCESS;
	}

	private function updateCounts(OutputInterface $output, $em) {
		$this->updateTextCountByLabels($output, $em);
		$this->updateTextCountByLabelsParents($output, $em);
		$this->updateTextCountByTypes($output, $em);
		$this->updateTextCountByLanguages($output, $em);
		$this->updateBookCountBySequences($output, $em);
		$this->updateBookCountByCategories($output, $em);
		$this->updateBookCountByCategoriesParents($output, $em);
		$this->updateCommentCountByTexts($output, $em);
		$this->updatePersonCountByCountries($output, $em);
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
		$this->updateCountByParents($em, Label::class, 'NrOfTexts');
	}

	/**
	 * @RawSql
	 */
	private function updateTextCountByTypes(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating text counts by types');
		$update = $this->maintenanceSql('UPDATE text_type tt SET nr_of_texts = (SELECT COUNT(*) FROM text WHERE type = tt.code)');
		$em->getConnection()->executeUpdate($update);
	}

	/** @RawSql */
	private function updateTextCountByLanguages(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating text counts by languages');
		$em->getConnection()->executeUpdate($this->maintenanceSql('UPDATE language l SET nr_of_texts = (SELECT COUNT(*) FROM text WHERE lang = l.code)'));
		$em->getConnection()->executeUpdate($this->maintenanceSql('UPDATE language l SET nr_of_translated_texts = (SELECT COUNT(*) FROM text WHERE orig_lang = l.code)'));
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
		$this->updateCountByParents($em, Category::class, 'NrOfBooks');
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
	 * @RawSql
	 */
	private function updatePersonCountByCountries(OutputInterface $output, EntityManager $em) {
		$output->writeln('Updating person counts by countries');
		$update1 = $this->maintenanceSql('UPDATE country c SET nr_of_authors = (SELECT COUNT(*) FROM person WHERE is_author = 1 AND country = c.code)');
		$update2 = $this->maintenanceSql('UPDATE country c SET nr_of_translators = (SELECT COUNT(*) FROM person WHERE is_translator = 1 AND country = c.code)');
		$em->getConnection()->executeUpdate($update1);
		$em->getConnection()->executeUpdate($update2);
	}

	/**
	 * @param EntityManager $em
	 * @param string $entity
	 * @param string $field
	 */
	private function updateCountByParents(EntityManager $em, $entity, $field) {
		$repo = $em->getRepository($entity);
		$originalCounts = [];
		foreach ($repo->findAll() as $item) {
			$originalCounts[$item->getId()] = call_user_func([$item, "get{$field}"]);
		}
		foreach ($repo->findAll() as $item) {
			$count = $originalCounts[$item->getId()];
			if ($count == 0) {
				continue;
			}
			$parent = $item->getParent();
			if ($parent) {
				do {
					call_user_func(array($parent, "inc{$field}"), $count);
					$em->persist($parent);
				} while (null !== ($parent = $parent->getParent()));
			}
		}

		$em->flush();
	}
}
