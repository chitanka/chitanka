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
			->addOption('no-wait', null, InputOption::VALUE_NONE, 'Skip waiting time at the beginning. Not recommended for mirror servers.')
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

		$container = $this->getContainer();
		$updateDir = $container->getParameter('kernel.root_dir').'/../update';
		$mutex = new Mutex($updateDir);
		if ( ! $mutex->acquireLock()) {
			return;
		}
		if ($input->getOption('no-wait') === false) {
			// this will spread check requests from mirrors in time
			sleep(rand(0, 30));
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate($container->getParameter('update_content_url'), "$updateDir/content");
		}
		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate($container->getParameter('update_db_url'), "$updateDir/db");
		}
		if ($input->getOption('skip-src') === false) {
			$this->executeSrcUpdate($container->getParameter('update_src_url'), "$updateDir/src");
		}
		$mutex->releaseLock();

		$output->writeln('Done.');
	}

	private function executeDbUpdate($fetchUrl, $updateDir)
	{
		$zip = $this->fetchUpdate($fetchUrl, $updateDir);
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

	private function executeContentUpdate($fetchUrl, $updateDir)
	{
		$zip = $this->fetchUpdate($fetchUrl, $updateDir);
		if ( ! $zip) {
			return false;
		}
		$extractDir = sys_get_temp_dir().'/chitanka-content-'.time();
		mkdir($extractDir);
		$zip->extractTo($extractDir);
		$zip->close();

		$copier = new DirectoryCopier;
		$copier->copy($extractDir, $this->contentDir());

		foreach (file("$extractDir/.deleted", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $filename) {
			unlink($this->contentDir($filename));
		}

		copy("$extractDir/.last", "$updateDir/.last");

		return true;
	}

	// TODO
	private function executeSrcUpdate($fetchUrl, $updateDir)
	{
		return true;
	}

	/** @return \ZipArchive */
	private function fetchUpdate($fetchUrl, $updateDir)
	{
		$lastmod = trim(file_get_contents("$updateDir/.last"));
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
