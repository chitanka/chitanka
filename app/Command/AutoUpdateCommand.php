<?php namespace App\Command;

use App\Service\Mutex;
use App\Service\SourceUpdater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class AutoUpdateCommand extends Command {

	private $output;

	public function getName() {
		return 'auto-update';
	}

	public function getDescription() {
		return 'Execute an auto-update of the whole system';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> updates the whole system - software, database, and content.';
	}

	protected function getBooleanOptions() {
		return [
			'no-wait' => 'Skip waiting time at the beginning. Not recommended for mirror servers.',
			'skip-content' => 'Skip content update',
			'skip-db' => 'Skip database update',
			'skip-src' => 'Skip software update',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
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
			$this->executeSrcUpdate($rootDir, $container->getParameter('git.path'));
		}
		if ($input->getOption('skip-content') === false) {
			$this->executeContentUpdate($this->contentDir(), $container->getParameter('content_urls'), $container->getParameter('git.path'), $container->getParameter('rsync.path'));
		}
		if ($input->getOption('skip-db') === false) {
			$this->executeDbUpdate($container->getParameter('update_db_url'), "$updateDir/db");
		}
		$mutex->releaseLock();

		$output->writeln('Done.');
	}

	/**
	 * @param string $fetchUrl
	 * @param string $updateDir
	 * @return boolean
	 */
	private function executeDbUpdate($fetchUrl, $updateDir) {
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
		$this->runCommand('db:update-counts');

		return true;
	}

	private function createSqlImporter() {
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

	private function deleteRemovedNoticesIfDisallowed() {
		if ($this->getContainer()->getParameter('allow_removed_notice') === false) {
			$this->getEntityManager()->getTextRepository()->execute('UPDATE text SET removed_notice = NULL');
			$this->getEntityManager()->getBookRepository()->execute('UPDATE book SET removed_notice = NULL');
		}
	}

	/**
	 * @param string $contentDir
	 * @param array $contentUrls
	 * @param string $git Path to git executable
	 * @param string $rsync Path to rync executable
	 * @return boolean
	 */
	private function executeContentUpdate($contentDir, $contentUrls, $git, $rsync) {
		shell_exec("$rsync -avz --delete rsync.chitanka.info::content/ $contentDir");
//		if (file_exists("$contentDir/text/.git")) {
//			foreach (glob("$contentDir/*", GLOB_ONLYDIR) as $dir) {
//				chdir($dir);
//				shell_exec("$git pull");
//			}
//		} else {
//			$fs = new \Symfony\Component\Filesystem\Filesystem;
//			foreach ($contentUrls as $subDir => $contentUrl) {
//				$targetDir = "$contentDir/$subDir";
//				$fs->remove($targetDir);
//				shell_exec("$git clone --depth=1 $contentUrl $targetDir");
//			}
//		}
		return true;
	}

	/**
	 * @param string $rootDir
	 * @param string $git Path to git executable
	 * @return boolean
	 */
	private function executeSrcUpdate($rootDir, $git) {
		$response = shell_exec("cd $rootDir; LC_ALL=C $git pull");
		if (strpos($response, 'up-to-date') !== false) {
			return false;
		}
		$updater = new SourceUpdater($rootDir);
		$updater->lockFrontController();
		$this->clearAppCache();
		$updater->unlockFrontController();
		return true;
	}

	private function clearAppCache() {
		$cacheDir = $this->getKernel()->getCacheDir();
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

	/**
	 * @param string $commandName
	 */
	private function runCommand($commandName) {
		$php = PHP_BINDIR.'/php'; // PHP_BINARY available since 5.4
		$binDir = realpath($this->getKernel()->getRootDir().'/../bin');
		$environment = $this->getKernel()->getEnvironment();
		shell_exec("$php $binDir/console $commandName --env=$environment");
	}

	/**
	 * @param string $fetchUrl
	 * @param string $updateDir
	 * @param string $now
	 * @return \ZipArchive|null
	 */
	private function fetchUpdate($fetchUrl, $updateDir, $now) {
		$url = $this->prepareFetchUrl($fetchUrl, $updateDir, $now);
		if ($url == null) {
			return null;
		}
		$this->output->writeln("Fetching update from $url");

		try {
			$response = $this->downloadUpdate($url, $updateDir);
		} catch (\RuntimeException $e) {
			error_log("fetch error by $url ({$e->getMessage()})");
			return null;
		}
		if ($response->isRedirection()) { // most probably not modified - 304
			return null;
		}
		if ( ! $response->isSuccessful()) {
			error_log("fetch error by $url (code {$response->getStatusCode()})");
			return null;
		}
		return $this->initZipFileFromContent($response->getContent());
	}

	/**
	 * @param string $fetchUrl
	 * @param string $updateDir
	 * @param string $now
	 * @return string
	 */
	private function prepareFetchUrl($fetchUrl, $updateDir, $now) {
		$lastmodFile = "$updateDir/.last";
		if ( ! file_exists($lastmodFile)) {
			file_put_contents($lastmodFile, $now);
			return null;
		}
		$lastmod = trim(file_get_contents($lastmodFile));
		return "$fetchUrl/$lastmod";
	}

	/**
	 * @param string $url
	 * @param string $updateDir
	 * @return \Buzz\Message\Response
	 */
	private function downloadUpdate($url, $updateDir) {
		$browser = $this->getContainer()->get('buzz');
		$client = new \App\Service\ResumeCurlClient();
		$client->setSaveDir($updateDir);
		$browser->setClient($client);
		$browser->addListener($client);

		return $browser->get($url, ['User-Agent: Mylib (http://chitanka.info)']);
	}

	/**
	 * @param string $content
	 * @return \ZipArchive
	 */
	private function initZipFileFromContent($content) {
		if (empty($content)) {
			return null;
		}
		$tmpfile = sys_get_temp_dir().'/chitanka-'.uniqid().'.zip';
		file_put_contents($tmpfile, $content);
		$zip = new \ZipArchive;
		if ($zip->open($tmpfile) === true) {
			return $zip;
		}
		return null;
	}
}
