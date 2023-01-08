<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserGroupsCommand extends Command {

	public function getName() {
		return 'sys:change-user-groups';
	}

	public function getDescription() {
		return 'Change groups for given users';
	}

	protected function getRequiredArguments() {
		return [
			'users' => 'Users which groups should be modified (comma separated)',
			'groups' => 'Groups to add or remove (comma separated). Ex.: "+workroom-admin,-admin" adds the user to "workroom-admin" and removes him from "admin"',
		];
	}

	/** @inheritdoc */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$userNames = $this->readUsers($input);
		list($groupsToAdd, $groupsToRemove) = $this->readGroups($input);
		$users = $this->getEntityManager()->getUserRepository()->findByUsernames($userNames);
		$this->modifyUserGroups($users, $groupsToAdd, $groupsToRemove);
		$output->writeln("Done.");
	}

	/**
	 *
	 * @param \App\Entity\User[] $users
	 * @param array $groupsToAdd
	 * @param array $groupsToRemove
	 */
	private function modifyUserGroups($users, $groupsToAdd, $groupsToRemove) {
		$repo = $this->getEntityManager()->getUserRepository();
		foreach ($users as $user) {
			$user->addGroups($groupsToAdd);
			$user->removeGroups($groupsToRemove);
			$repo->save($user);
		}
	}

	private function readUsers(InputInterface $input) {
		return array_map('trim', explode(',', $input->getArgument('users')));
	}

	/**
	 * Process input and prepare groups to be added and to be removed
	 * @param InputInterface $input
	 * @return array Array with two subarrays - groups for additions and groups for removal
	 */
	private function readGroups(InputInterface $input) {
		$groupsToAdd = $groupsToRemove = [];
		foreach (array_map('trim', explode(',', $input->getArgument('groups'))) as $groupIdent) {
			switch ($groupIdent[0]) {
				case '-':
					$groupsToRemove[] = substr($groupIdent, 1);
					break;
				case '+':
					$groupsToAdd[] = substr($groupIdent, 1);
					break;
				default:
					$groupsToAdd[] = $groupIdent;
			}
		}
		return [$groupsToAdd, $groupsToRemove];
	}
}
