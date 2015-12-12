<?php namespace App\Command;

use App\Service\ContentImporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLibCommand extends Command {

	public function getName() {
		return 'lib:update';
	}

	public function getDescription() {
		return 'Add or update new texts and books';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command adds or updates texts and books.';
	}

	protected function getRequiredArguments() {
		return [
			'input' => 'Directory with input files or other input directories',
		];
	}

	protected function getBooleanOptions() {
		return [
			'save' => 'Save generated files in corresponding directories',
			'dump-sql' => 'Output SQL queries instead of executing them',
		];
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$saveFiles = $input->getOption('save') === true;
		$dumpSql = $input->getOption('dump-sql') === true;
		$contentDir = $this->getContainer()->getParameter('kernel.root_dir').'/../web/content';
		$importer = new ContentImporter($this->getEntityManager(), $contentDir, $saveFiles, $this->olddb());
		$queries = $this->conquerTheWorld($importer, $input->getArgument('input'));

		if ($dumpSql) {
			$this->printQueries($queries);
		}

		$output->writeln('/*Done.*/');
	}

	private function conquerTheWorld(ContentImporter $importer, $inputDir) {
		$queries = [];
		$queries[] = 'SET NAMES utf8';
		$queries[] = 'START TRANSACTION';
		$dir = $inputDir;
		if (count(glob("$dir/*.data")) == 0) {
			foreach (glob("$dir/*", GLOB_ONLYDIR) as $dir) {
				$queries = array_merge($queries, $importer->processPacket($dir));
			}
		} else {
			$queries = array_merge($queries, $importer->processPacket($dir));
		}
		$queries = array_merge($queries, $importer->getNextIdUpdateQueries());
		$queries[] = 'COMMIT';

		return $queries;
	}

}
