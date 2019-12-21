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
		$rootDir = realpath($container->getParameter('kernel.root_dir').'/..');
		$updateDir = "$rootDir/update";

		$echo = function ($msg) use ($output) {
			$output->writeln(date('H:i:s').": ".$msg);
		};

		$mutex = new Mutex($updateDir);
		if ( ! $mutex->acquireLock(1800/*secs*/)) {
			$echo("There is a lock file ($updateDir). If no other update is running, you can safely delete the lock file and run the update again.");
			return;
		}

		$echo("Update started on ".date('Y-m-d').".");
		if ($input->getOption('no-wait') === false) {
			// this will spread check requests from mirrors in time
			$pause = rand(0, 30);
			$echo("Pause for $pause seconds.");
			sleep($pause);
		}
		if ($input->getOption('skip-src') === false) {
			$echo("Executing source update...");
			$this->executeSrcUpdate($rootDir, $container->getParameter('rsync.url.src'));
		}
		if ($input->getOption('skip-content') === false) {
			$echo("Executing content update...");
			$this->executeContentUpdate($this->contentDir(), $container->getParameter('rsync.url.content'));
		}
		if ($input->getOption('skip-db') === false) {
			$echo("Executing database update...");
			$this->executeDbUpdate($container->getParameter('update_db_url'), "$updateDir/db");
		}
		$mutex->releaseLock();

		$echo('Done.');
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

	private function executeContentUpdate(string $contentDir, string $contentRsyncUrl): bool {
		$response = $this->runRsyncCommand("$contentRsyncUrl/", $contentDir, '--delete');
		return $response->hasUpdates();
	}

	private function executeSrcUpdate(string $rootDir, string $srcRsyncUrl): bool {
		try {
			$response = $this->runGitPullCommand($rootDir);
			if (!$response->hasUpdates()) {
				return false;
			}
		} catch (\Exception $e) {
			$response = $this->runRsyncCommand("$srcRsyncUrl/", $rootDir);
			if (!$response->hasUpdates()) {
				return false;
			}
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
			error_log(__METHOD__.': '.$e->getMessage());
		}
	}

	private function runCommand(string $commandName) {
		$php = PHP_BINARY;
		$binDir = realpath($this->getKernel()->getRootDir().'/../bin');
		$environment = $this->getKernel()->getEnvironment();
		$this->runShellCommand("\"$php\" \"$binDir/console\" $commandName --env=$environment");
	}

	public function runGitPullCommand(string $targetDir): FetchGitResponse {
		$gitBinary = $this->getContainer()->getParameter('git.path');
		if (!$gitBinary) {
			throw new \Exception('The git binary is not configured.');
		}
		return new FetchGitResponse($this->runShellCommand("cd \"$targetDir\" && \"$gitBinary\" pull"));
	}

	public function runRsyncCommand(string $remoteSource, string $localTarget, string $options = null): FetchRsyncResponse {
		$rsyncBinary = $this->getContainer()->getParameter('rsync.path');
		if (!$rsyncBinary) {
			throw new \Exception('The rsync binary is not configured.');
		}
		// rsync does not support absolute windows paths
		if (strpos($localTarget, ':') !== false) {
			$localTarget = '/cygdrive/'. strtr($localTarget, [':' => '', '\\' => '/']);
		}
		return new FetchRsyncResponse($this->runShellCommand("\"$rsyncBinary\" -az --out-format=\"%n\" $options \"$remoteSource\" \"$localTarget\""));
	}

	private function runShellCommand(string $command): string {
		return (string) shell_exec($command);
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
	 * @return \ZipArchive|null
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

class FetchCommandResponse {

	public $text;

	public function __construct(string $text) {
		$this->text = trim($text);
	}

	public function hasUpdates(): bool {
		return !empty($this->text);
	}
}

class FetchGitResponse extends FetchCommandResponse {

	public function hasUpdates(): bool {
		// before version 2.15 the message was: "Already up-to-date"
		// after that: "Already up to date"
		// https://github.com/git/git/commit/7560f547e614244fe1d4648598d4facf7ed33a56
		return strpos(str_replace('-', ' ', $this->text), 'Already up to date') === false;
	}
}

class FetchRsyncResponse extends FetchCommandResponse {
}
