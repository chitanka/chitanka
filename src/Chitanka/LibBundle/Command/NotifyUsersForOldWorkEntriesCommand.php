<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Entity\WorkEntry;
use Chitanka\LibBundle\Service\Notifier;

class NotifyUsersForOldWorkEntriesCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('lib:notify-old-work-entries')
			->setDescription('Notify all users with too old work entries')
			->addArgument('age', InputArgument::REQUIRED, 'Threshold age for notification (in months)')
			->addArgument('stalk-interval', InputArgument::OPTIONAL, 'Number of days between two subsequent notifications. Default: 7', 7)
			->setHelp(<<<EOT
Notify all users with too old work entries.
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$oldEntries = $this->getRepository('WorkEntry')->findOlderThan($this->getThresholdDate($input));
		$notifer = new Notifier($this->getContainer()->get('mailer'));
		foreach ($oldEntries as $entry) {
			$this->sendNotification($notifer, $entry, $input->getArgument('stalk-interval'));
		}
		$this->getEntityManager()->flush();

		$output->writeln('/*Done.*/');
	}

	private function sendNotification(Notifier $notifier, WorkEntry $entry, $stalkInterval)
	{
		if ($entry->isNotifiedWithin("$stalkInterval days")) {
			return;
		}
		$notifier->sendMailByOldWorkEntry($entry);
		$entry->setLastNotificationDate(new \DateTime);
	}

	private function getThresholdDate(InputInterface $input)
	{
		$age = $input->getArgument('age');
		return date('Y-m-d', strtotime("-$age months"));
	}
}
