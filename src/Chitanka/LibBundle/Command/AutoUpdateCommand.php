<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Service\Mutex;
use Chitanka\LibBundle\Service\DbUpdater;
use Chitanka\LibBundle\Service\FileUpdater;
use Chitanka\LibBundle\Service\SourceUpdater;

class AutoUpdateCommand extends CommonDbCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('auto-update')
			->setDescription('Execute an auto-update of the whole system')
			->addOption('no-wait', null, InputOption::VALUE_NONE, 'Skip waiting time at the beginning. Not recommended for mirror servers.')
			->addOption('skip-content', null, InputOption::VALUE_NONE, 'Skip content update')
			->addOption('skip-db', null, InputOption::VALUE_NONE, 'Skip database update')
			->addOption('skip-src', null, InputOption::VALUE_NONE, 'Skip software update')
			->setHelp(<<<EOT
The <info>auto-update</info> updates the whole system - software, database, and content.
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;

		$container = $this->getContainer();
		$rootDir = $container->getParameter('kernel.root_dir').'/..';
		$updateDir = "$rootDir/update";
		$mutex = new Mutex($updateDir);
		if ( ! $mutex->acquireLock()) {
			return;
		}
		if ($input->getOption('no-wait') === false) {
			// this will spread check requests from mirrors in time
			sleep(rand(0, 30));
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate($container->getParameter('update_content_url'), "$updateDir/content", $this->contentDir());
		}
		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate($container->getParameter('update_db_url'), "$updateDir/db");
		}
		if ($input->getOption('skip-src') === false) {
			$this->executeSrcUpdate($container->getParameter('update_src_url'), "$updateDir/src", $rootDir);
		}
		$mutex->releaseLock();

		$output->writeln('Done.');
	}

	private function executeDbUpdate($fetchUrl, $updateDir)
	{
		$zip = $this->fetchUpdate($fetchUrl, $updateDir, date('Y-m-d/1'));
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

	private function executeContentUpdate($fetchUrl, $updateDir, $contentDir)
	{
		$zip = $this->fetchUpdate($fetchUrl, $updateDir, time());
		if ( ! $zip) {
			return false;
		}
		$updater = new FileUpdater($contentDir, $updateDir);
		$updater->extractArchive($zip);
		return true;
	}

	private function executeSrcUpdate($fetchUrl, $updateDir, $rootDir)
	{
		$zip = $this->fetchUpdate($fetchUrl, $updateDir, time());
		if ( ! $zip) {
			return false;
		}
		$updater = new SourceUpdater($rootDir, $updateDir);
		$updater->lockFrontController();
		$updater->extractArchive($zip);
		// update app/config/parameters.yml if needed
		$this->clearCache();
		// make sure cache dir is world-writable (somehow the 0000 umask is sometimes not enough)
		chmod("$rootDir/app/cache/prod", 0777);
		$updater->unlockFrontController();
		return true;
	}

	private function clearCache()
	{
		$commandName = 'cache:clear';
		$command = $this->getApplication()->find($commandName);
		$arguments = array('command' => $commandName);
		return $command->run(new ArrayInput($arguments), $this->output);
	}

	/** @return \ZipArchive */
	private function fetchUpdate($fetchUrl, $updateDir, $now)
	{
		$lastmodFile = "$updateDir/.last";
		if ( ! file_exists($lastmodFile)) {
			file_put_contents($lastmodFile, $now);
			return false;
		}
		$lastmod = trim(file_get_contents($lastmodFile));
		$url = "$fetchUrl/$lastmod";
		$this->output->writeln("Fetching update from $url");
		$browser = $this->getContainer()->get('buzz');
		$browser->getClient()->setTimeout(120);
		$response = $browser->get($url, array('User-Agent: Mylib (http://chitanka.info)'));
		if ($response->isRedirection()) { // most probably not modified - 304
			return false;
		}
		if ( ! $response->isSuccessful()) {
			error_log("fetch error by $url (code {$response->getStatusCode()})");
			return false;
		}
		return $this->initZipFileFromContent($response->getContent());
	}

	/** @return \ZipArchive */
	private function initZipFileFromContent($content)
	{
		if (empty($content)) {
			return false;
		}
		$tmpfile = sys_get_temp_dir().'/chitanka-'.uniqid().'.zip';
		file_put_contents($tmpfile, $content);
		$zip = new \ZipArchive;
		if ($zip->open($tmpfile) === true) {
			return $zip;
		}
		return false;
	}
}
