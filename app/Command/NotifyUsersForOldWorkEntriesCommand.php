<?php namespace App\Command;

use App\Entity\WorkEntry;
use App\Mail\WorkroomNotifier;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyUsersForOldWorkEntriesCommand extends Command {

	protected function configure() {
		parent::configure();

		$this
			->setName('lib:notify-old-work-entries')
			->setDescription('Notify all users with too old work entries')
			->addArgument('age', InputArgument::REQUIRED, 'Threshold age for notification (in months)')
			->addArgument('stalk-interval', InputArgument::OPTIONAL, 'Number of days between two subsequent notifications. Default: 7', 7)
			->addArgument('skip-users', InputArgument::OPTIONAL, 'List of users by name which should not get notifications. Format: USERNAME1[,USERNAME2]*')
			->setHelp(<<<EOT
Notify all users with too old work entries.
EOT
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$repo = $this->getEntityManager()->getWorkEntryRepository();
		$oldEntries = $repo->findOlderThan($this->getThresholdDate($input));
		$skippedUsers = $this->getSkippedUsers($input);
		$notifer = new WorkroomNotifier($this->getContainer()->get('mailer'));
		foreach ($oldEntries as $entry) {
			if ($this->shouldSkipEntry($entry, $skippedUsers)) {
				continue;
			}
			$this->sendNotification($notifer, $entry, $input->getArgument('stalk-interval'));
		}
		$repo->flush();

		$output->writeln('/*Done.*/');
	}

	private function sendNotification(WorkroomNotifier $notifier, WorkEntry $entry, $stalkInterval) {
		if ($entry->isNotifiedWithin("$stalkInterval days")) {
			return;
		}
		$notifier->sendMailByOldWorkEntry($entry);
		$entry->setLastNotificationDate(new \DateTime);
	}

	private function getThresholdDate(InputInterface $input) {
		$age = $input->getArgument('age');
		return date('Y-m-d', strtotime("-$age months"));
	}

	private function getSkippedUsers(InputInterface $input) {
		return explode(',', $input->getArgument('skip-users'));
	}

	private function shouldSkipEntry(WorkEntry $entry, array $skippedUsers) {
		return in_array($entry->getUser()->getUsername(), $skippedUsers);
	}
}
