<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TODO Usage of book.title_author is deprecated
 */
class UpdateBookTitleAuthorDbCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('db:update-book-title-author')
			->setDescription('Update legacy field book.title_author')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>db:update-book-title-author</info> command updates the legacy field book.title_author.
EOT
		);
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract class is not implemented
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
		$this->output = $output;
		$this->dumpSql = $input->getOption('dump-sql') === true;
		$this->updateBookTitleAuthor($this->dumpSql);
		$output->writeln('/*Done.*/');
	}


	protected function updateBookTitleAuthor($dumpSql)
	{
		$queries = array();
		$iterableResult = $this->em->createQuery('SELECT b FROM App:Book b WHERE b.title_author IS NULL')->iterate();
		foreach ($iterableResult AS $row) {
			$book = $row[0];
			if (count($book->getAuthors()) == 0) {
				continue;
			}
			$authorNames = array();
			foreach ($book->getAuthors() as $author) {
				$authorNames[] = $author->getName();
			}
			$titleAuthor = implode(', ', $authorNames);
			$queries[] = "UPDATE book SET title_author = '$titleAuthor' WHERE id = ".$book->getId();
			$this->em->detach($book);
		}

		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->em->getConnection());
		}
	}

}
