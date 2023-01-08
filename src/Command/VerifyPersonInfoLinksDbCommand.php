<?php namespace App\Command;

use App\Util\HttpAgent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyPersonInfoLinksDbCommand extends Command {

	private $output;
	private $secsBetweenRequests = 5;

	public function getName() {
		return 'db:verify-person-info-links';
	}

	public function getDescription() {
		return 'Verify the person wiki info links';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command verifies the existance of the person wiki info links and removes the non-existing ones.';
	}

	protected function getBooleanOptions() {
		return [
			'dump-sql' => 'Output SQL queries instead of executing them',
		];
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;
		$dumpSql = $input->getOption('dump-sql') === true;
		$this->verifyWikiInfoLinks($dumpSql);
		$output->writeln('/*Done.*/');
	}

	/**
	 * @param bool $dumpSql
	 */
	private function verifyWikiInfoLinks($dumpSql) {
		$personIds = $this->getIdsForPersonsWithInvalidInfoLinks();
		$this->removeInvalidInfoLinksByPersons($personIds, $dumpSql);
	}

	private function getIdsForPersonsWithInvalidInfoLinks() {
		$iterableResult = $this->getEntityManager()->createQuery('SELECT p FROM App:Person p WHERE p.info LIKE \'%:%\'')->iterate();
		$siteRepo = $this->getEntityManager()->getWikiSiteRepository();
		$httpAgent = new HttpAgent;

		$ids = [];
		foreach ($iterableResult AS $i => $row) {
			$person = $row[0];
			list($prefix, $name) = explode(':', $person->getInfo(), 2);
			$site = $siteRepo->findOneBy(['code' => $prefix]);
			$url = $site->getUrl($name);
			$this->output->writeln("/* ({$person->getId()}) Checking $url */");
			if (!$httpAgent->urlExists($url)) {
				$ids[] = $person->getId();
				$this->output->writeln("/* {$person->getName()}: $url is a broken link */");
			}
			sleep($this->secsBetweenRequests);
		}

		return $ids;
	}

	/**
	 * @param array $personIds
	 * @param bool $dumpSql
	 */
	private function removeInvalidInfoLinksByPersons($personIds, $dumpSql) {
		if (count($personIds) == 0) {
			return;
		}
		$queries = [sprintf('UPDATE person SET info = NULL WHERE id IN ('.implode(',', $personIds).')')];
		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->getEntityManager()->getConnection());
		}
	}
}
