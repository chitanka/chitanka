<?php namespace App\Service;

class GitCommitCommand extends \GitElephant\Command\MainCommand {

	/**
	 * Commit
	 *
	 * @param string $message  the commit message
	 * @param string $author   author of the commit
	 * @param bool   $stageAll commit all changes
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function commitWithAuthor($message, $author, $stageAll = false) {
		$this->clearAll();
		if (trim($message) == '' || $message == null) {
			throw new \InvalidArgumentException(sprintf('You can\'t commit without message'));
		}
		$this->addCommandName(self::GIT_COMMIT);

		if ($stageAll) {
			$this->addCommandArgument('-a');
		}

		if ($author) {
			$this->addCommandArgument('--author');
			$this->addCommandArgument($author);
		}

		$this->addCommandArgument('-m');
		$this->addCommandSubject($message);

		return $this->getCommand();
	}
}
