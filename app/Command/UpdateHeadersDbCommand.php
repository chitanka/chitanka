<?php namespace App\Command;

use App\Service\TextService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateHeadersDbCommand extends Command {

	private $em;
	private $output;

	public function getName() {
		return 'db:update-headers';
	}

	public function getDescription() {
		return 'Update text headers in the database';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command updates the text headers in the database.';
	}

	protected function getOptionalArguments() {
		return [
			'texts' => 'Texts which headers should be updated (comma separated)',
		];
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
		$texts = trim($input->getArgument('texts'));
		$dumpSql = $input->getOption('dump-sql') === true;
		$this->updateHeaders($texts, $dumpSql);
		$output->writeln('/*Done.*/');
	}

	/**
	 * @param string $texts
	 * @param bool $dumpSql
	 */
	private function updateHeaders($texts, $dumpSql) {
		$queries = [];
		$dql = 'SELECT t FROM App:Text t WHERE t.headlevel > 0';
		if ($texts) {
			$dql .= " AND t.id IN ($texts)";
		}
		$iterableResult = $this->em->createQuery($dql)->iterate();
		$textService = new TextService($this->olddb());
		foreach ($iterableResult AS $row) {
			$text = $row[0];
			if ($text->isCompilation()) {
				$file = tempnam(sys_get_temp_dir(), 'text');
				file_put_contents($file, $text->getRawContent());
			} else {
				$file = $this->webDir($text->getMainContentFile());
			}
			$queries = array_merge($queries, $textService->buildTextHeadersUpdateQuery($file, $text->getId(), $text->getHeadlevel()));
			$this->em->setFree($text); // free memory
		}

		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->em->getConnection());
		}
	}

}
