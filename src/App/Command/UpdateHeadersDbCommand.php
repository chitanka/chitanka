<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateHeadersDbCommand extends CommonDbCommand {

	protected function configure() {
		parent::configure();

		$this
			->setName('db:update-headers')
			->setDescription('Update text headers in the database')
			->addArgument('texts', InputArgument::OPTIONAL, 'Texts which headers should be updated (comma separated)')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>db:update-headers</info> command updates the text headers in the database.
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
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
		$this->output = $output;
		$texts = trim($input->getArgument('texts'));
		$dumpSql = $input->getOption('dump-sql') === true;
		$this->updateHeaders($texts, $dumpSql);
		$output->writeln('/*Done.*/');
	}

	/**
	 * @param string $texts
	 * @param boolean $dumpSql
	 */
	protected function updateHeaders($texts, $dumpSql) {
		$queries = array();
		$dql = 'SELECT t FROM App:Text t WHERE t.headlevel > 0';
		if ($texts) {
			$dql .= " AND t.id IN ($texts)";
		}
		$iterableResult = $this->em->createQuery($dql)->iterate();
		foreach ($iterableResult AS $row) {
			$text = $row[0];
			if ($text->isCompilation()) {
				$file = tempnam(sys_get_temp_dir(), 'text');
				file_put_contents($file, $text->getRawContent());
			} else {
				$file = $this->webDir($text->getMainContentFile());
			}
			$queries = array_merge($queries, $this->buildTextHeadersUpdateQuery($file, $text->getId(), $text->getHeadlevel()));
			$this->em->detach($text);
		}

		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->em->getConnection());
		}
	}

}
