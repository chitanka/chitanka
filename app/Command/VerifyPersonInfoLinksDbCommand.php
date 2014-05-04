<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Util\HttpAgent;

class VerifyPersonInfoLinksDbCommand extends Command {

	private $em;
	private $output;
	private $secsBetweenRequests = 5;

	protected function configure() {
		parent::configure();

		$this
			->setName('db:verify-person-info-links')
			->setDescription('Verify the person wiki info links')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>db:verify-person-info-links</info> command verifies the existance of the person wiki info links and removes the non-existing ones.
EOT
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
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
		$iterableResult = $this->em->createQuery('SELECT p FROM App:Person p WHERE p.info LIKE \'%:%\'')->iterate();
		$siteRepo = $this->em->getRepository('App:WikiSite');
		$httpAgent = new HttpAgent;

		$ids = array();
		foreach ($iterableResult AS $i => $row) {
			$person = $row[0];
			list($prefix, $name) = explode(':', $person->getInfo(), 2);
			$site = $siteRepo->findOneBy(array('code' => $prefix));
			$url = $site->getUrl($name);
			$this->output->writeln("/* ({$person->getId()}) Checking $url */");
			if ( ! $httpAgent->urlExists($url)) {
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
		$queries = array();
		if (count($personIds)) {
			$queries[] = sprintf('UPDATE person SET info = NULL WHERE id IN ('.implode(',', $personIds).')');
			if ($dumpSql) {
				$this->printQueries($queries);
			} else {
				$this->executeUpdates($queries, $this->em->getConnection());
			}
		}
	}
}
