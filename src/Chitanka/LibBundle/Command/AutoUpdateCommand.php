<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
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
		if ( ! $mutex->acquireLock(1800/*secs*/)) {
			return;
		}
		if ($input->getOption('no-wait') === false) {
			// this will spread check requests from mirrors in time
			sleep(rand(0, 30));
		}
		if ($input->getOption('skip-src') === false) {
			$this->executeSrcUpdate($container->getParameter('update_src_url'), "$updateDir/src", $rootDir);
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate($container->getParameter('update_content_url'), "$updateDir/content", $this->contentDir());
		}
		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate($container->getParameter('update_db_url'), "$updateDir/db");
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

		$this->deleteRemovedNoticesIfDisallowed();

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

	private function deleteRemovedNoticesIfDisallowed()
	{
		$c = $this->getContainer();
		$param = 'allow_removed_notice';
		if ($c->hasParameter($param) && $c->getParameter($param) === false) {
			$db = $c->get('doctrine.orm.default_entity_manager')->getConnection();
			$db->executeUpdate('UPDATE text SET removed_notice = NULL');
			$db->executeUpdate('UPDATE book SET removed_notice = NULL');
		}
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
		$this->clearAppCache();
		$updater->unlockFrontController();

		return true;
	}

	private function clearAppCache()
	{
		$cacheDir = $this->getApplication()->getKernel()->getCacheDir();
		$cacheDirOld = $cacheDir.'_old_'.time();
		$fs = new \Symfony\Component\Filesystem\Filesystem;
		try {
			$fs->rename($cacheDir, $cacheDirOld);
			$fs->mkdir($cacheDir);
			$this->runCommand('cache:warmup');
			$this->runCommand('cache:create-cache-class');
			$fs->remove($cacheDirOld);
		} catch (IOException $e) {
			error_log("Auto-update: ".$e->getMessage());
		}
	}

	private function runCommand($commandName)
	{
		$php = isset($_SERVER['_']) ? $_SERVER['_'] : 'php';
		$rootDir = $this->getApplication()->getKernel()->getRootDir();
		$environment = $this->getApplication()->getKernel()->getEnvironment();
		shell_exec("$php $rootDir/console $commandName --env=$environment");
	}

	/** @return \ZipArchive */
	private function fetchUpdate($fetchUrl, $updateDir, $now)
	{
		$url = $this->prepareFetchUrl($fetchUrl, $updateDir, $now);
		if ($url == null) {
			return false;
		}
		$this->output->writeln("Fetching update from $url");

		try {
			$response = $this->downloadUpdate($url, $updateDir);
		} catch (\RuntimeException $e) {
			error_log("fetch error by $url ({$e->getMessage()})");
			return false;
		}
		if ($response->isRedirection()) { // most probably not modified - 304
			return false;
		}
		if ( ! $response->isSuccessful()) {
			error_log("fetch error by $url (code {$response->getStatusCode()})");
			return false;
		}
		return $this->initZipFileFromContent($response->getContent());
	}

	private function prepareFetchUrl($fetchUrl, $updateDir, $now)
	{
		$lastmodFile = "$updateDir/.last";
		if ( ! file_exists($lastmodFile)) {
			file_put_contents($lastmodFile, $now);
			return null;
		}
		$lastmod = trim(file_get_contents($lastmodFile));
		return "$fetchUrl/$lastmod";
	}

	private function downloadUpdate($url, $updateDir)
	{
		$browser = $this->getContainer()->get('buzz');
		$client = new \Chitanka\LibBundle\Service\ResumeCurlClient();
		$client->setSaveDir($updateDir);
		$browser->setClient($client);
		$browser->addListener($client);

		return $browser->get($url, array('User-Agent: Mylib (http://chitanka.info)'));
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
