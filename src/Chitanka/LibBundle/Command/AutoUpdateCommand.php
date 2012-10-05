<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Legacy\Legacy;

class AutoUpdateCommand extends CommonDbCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('auto-update')
			->setDescription('Execute an auto-update of the whole system')
			->addOption('skip-db', null, InputOption::VALUE_NONE, 'Skip database update')
			->addOption('skip-content', null, InputOption::VALUE_NONE, 'Skip content update')
			->addOption('skip-src', null, InputOption::VALUE_NONE, 'Skip software update')
			->setHelp(<<<EOT
The <info>auto-update</info> auto-update the whole system - software, database, and content.
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
		$this->updateUrl = $this->getContainer()->getParameter('update_url');
		$this->updateDir = $this->getContainer()->getParameter('kernel.root_dir').'/../update';

		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate();
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate();
		}
		if ($input->getOption('skip-src') === false) {
			$this->executeSrcUpdate();
		}

		$output->writeln('Done.');
	}

	private function executeDbUpdate()
	{
		$updateDir = $this->updateDir.'/db';
		$lockFile = "$updateDir/.lock";
		if (file_exists($lockFile)) {
			return false;
		}
		touch($lockFile);
		$lastModFile = "$updateDir/.last";
		$lastmod = file_get_contents($lastModFile);
		$file = Legacy::getFromUrl("$this->updateUrl/db/$lastmod");
		if (strlen($file) == 0) {
			return false;
		}
		$tmpfile = tempnam(sys_get_temp_dir(), 'chitanka-db').'.zip';
		file_put_contents($tmpfile, $file);
		$zip = new \ZipArchive;
		if ($zip->open($tmpfile) !== true) {
			return false;
		}
		$zip->extractTo($updateDir);
		$zip->close();

		$sqlImporter = $this->createSqlImporter();
		$sqlImporter->importFile("$updateDir/db.sql");
		unlink("$updateDir/db.sql");

		unlink($lockFile);

		return true;
	}

	private function createSqlImporter()
	{
		$c = $this->getContainer();
		require_once $c->getParameter('kernel.root_dir').'/../maintenance/sql_importer.lib.php';

		$dbhost = $c->getParameter('database_host');
		$dbname = $c->getParameter('database_name');
		$dbport = $c->getParameter('database_port');
		$dbuser = $c->getParameter('database_user');
		$dbpassword = $c->getParameter('database_password');
		$dsn = "mysql:host=$dbhost;dbname=$dbname";
		if ($dbport) {
			$dsn .= ";port=$dbport";
		}

		return new \SqlImporter($dsn, $dbuser, $dbpassword);
	}

	// TODO
	private function executeContentUpdate()
	{
		$updateDir = $this->updateDir.'/content';
		$lockFile = "$updateDir/.lock";
		if (file_exists($lockFile)) {
			return false;
		}
		touch($lockFile);
		unlink($lockFile);

		return true;
	}

	// TODO
	private function executeSrcUpdate()
	{
		$updateDir = $this->updateDir.'/src';
		$lockFile = "$updateDir/.lock";
		if (file_exists($lockFile)) {
			return false;
		}
		touch($lockFile);
		unlink($lockFile);

		return true;
	}

}
