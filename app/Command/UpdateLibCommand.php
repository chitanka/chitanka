<?php namespace App\Command;

use App\Service\ContentImporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLibCommand extends Command {

	protected function configure() {
		$this
			->setName('lib:update')
			->setDescription('Add or update new texts and books')
			->addArgument('input', InputArgument::REQUIRED, 'Directory with input files or other input directories')
			->addOption('save', null, InputOption::VALUE_NONE, 'Save generated files in corresponding directories')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp('The <info>%command.name%</info> command adds or updates texts and books.');
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
		$queries = array();
		$dir = $inputDir;
		if (count(glob("$dir/*.data")) == 0) {
			foreach (glob("$dir/*", GLOB_ONLYDIR) as $dir) {
				$queries = array_merge($queries, $importer->processPacket($dir));
			}
		} else {
			$queries = $importer->processPacket($dir);
		}
		array_unshift($queries, 'SET NAMES utf8');
		$queries = array_merge($queries, $importer->getNextIdUpdateQueries());

		return $queries;
	}

}
