<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TODO Usage of book.titleAuthor is deprecated
 */
class UpdateBookTitleAuthorDbCommand extends Command {

	private $em;
	private $output;
	private $dumpSql;

	public function getName() {
		return 'db:update-book-title-author';
	}

	public function getDescription() {
		return 'Update legacy field book.titleAuthor';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command updates the legacy field book.title_author.';
	}

	protected function getBooleanOptions() {
		return [
			'dump-sql' => 'Output SQL queries instead of executing them',
		];
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->em = $this->getEntityManager();
		$this->output = $output;
		$this->dumpSql = $input->getOption('dump-sql') === true;
		$this->updateBookTitleAuthor($this->dumpSql);
		$output->writeln('/*Done.*/');
	}

	/**
	 * @param bool $dumpSql
	 */
	private function updateBookTitleAuthor($dumpSql) {
		$queries = [];
		$iterableResult = $this->em->createQuery('SELECT b FROM App:Book b WHERE b.titleAuthor IS NULL')->iterate();
		foreach ($iterableResult AS $row) {
			$book = $row[0];
			if (count($book->getAuthors()) == 0) {
				continue;
			}
			$authorNames = [];
			foreach ($book->getAuthors() as $author) {
				$authorNames[] = $author->getName();
			}
			$titleAuthor = implode(', ', $authorNames);
			$queries[] = "UPDATE book SET title_author = '$titleAuthor' WHERE id = ".$book->getId();
			$this->em->setFree($book); // free memory
		}

		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->em->getConnection());
		}
	}

}
