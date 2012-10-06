<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Service\Mutex;
use Chitanka\LibBundle\Service\DirectoryCopier;

class AutoUpdateCommand extends CommonDbCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('auto-update')
			->setDescription('Execute an auto-update of the whole system')
			->addOption('skip-content', null, InputOption::VALUE_NONE, 'Skip content update')
			->addOption('skip-db', null, InputOption::VALUE_NONE, 'Skip database update')
			->addOption('skip-src', null, InputOption::VALUE_NONE, 'Skip software update')
			->setHelp(<<<EOT
The <info>auto-update</info> auto-update the whole system - software, database, and content.
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;

		$this->updateUrl = $this->getContainer()->getParameter('update_url');
		$this->updateDir = $this->getContainer()->getParameter('kernel.root_dir').'/../update';

		$mutex = new Mutex($this->updateDir);
		if ( ! $mutex->acquireLock()) {
			return;
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate();
		}
		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate();
		}
		if ($input->getOption('skip-src') === false) {
			$this->executeSrcUpdate();
		}
		$mutex->releaseLock();

		$output->writeln('Done.');
	}

	private function executeDbUpdate()
	{
		$updateDir = "$this->updateDir/db";
		$zip = $this->fetchUpdate("$this->updateUrl/db", $updateDir);
		if ( ! $zip) {
			return false;
		}
		$zip->extractTo($updateDir);
		$zip->close();

		$sqlImporter = $this->createSqlImporter();
		$sqlImporter->importFile("$updateDir/db.sql");
		unlink("$updateDir/db.sql");

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

	private function executeContentUpdate()
	{
		$updateDir = "$this->updateDir/content";
		$zip = $this->fetchUpdate("$this->updateUrl/content", $updateDir);
		if ( ! $zip) {
			return false;
		}
		$extractDir = sys_get_temp_dir().'/chitanka-content-'.time();
		mkdir($extractDir);
		$zip->extractTo($extractDir);
		$zip->close();

		foreach (file("$extractDir/.deleted", FILE_IGNORE_NEW_LINES) as $filename) {
			unlink($this->contentDir($filename));
		}
		copy("$extractDir/.last", "$updateDir/.last");
		$copier = new DirectoryCopier;
		$copier->copy($extractDir, $this->contentDir());

		return true;
	}

	// TODO
	private function executeSrcUpdate()
	{
		$updateDir = "$this->updateDir/src";

		return true;
	}

	/** @return \ZipArchive */
	private function fetchUpdate($fetchUrl, $updateDir)
	{
		$lastModFile = "$updateDir/.last";
		$lastmod = file_get_contents($lastModFile);
		$url = "$fetchUrl/$lastmod";
		$this->output->writeln("Fetching update from $url");
		$file = Legacy::getFromUrl($url);
		if (strlen($file) == 0) {
			return false;
		}
		return $this->initZipFileFromContent($file);
	}

	/** @return \ZipArchive */
	private function initZipFileFromContent($content)
	{
		$tmpfile = sys_get_temp_dir().'/chitanka-'.uniqid().'.zip';
		file_put_contents($tmpfile, $content);
		$zip = new \ZipArchive;
		if ($zip->open($tmpfile) === true) {
			return $zip;
		}
		return false;
	}
}
