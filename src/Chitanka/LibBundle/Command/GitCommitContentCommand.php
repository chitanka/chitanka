<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GitCommitContentCommand extends CommonDbCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('git:commit-content')
			->setDescription('Commit content changes from last X minutes')
			->addArgument('desc', InputArgument::REQUIRED, 'Brief description of changes')
			->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Number of last minutes to check for changes', 30)
			->setHelp(<<<EOT
The <info>git:commit-content</info> commit the content changes form last minutes in its repository.
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;
		$this->em = $this->getEntityManager();

		$description = $input->getArgument('desc');
		$lastMinutes = $input->getOption('from');
		$this->commitChanges($this->webDir('content'), $this->createMessageFile($description, $lastMinutes));

		$output->writeln('Done.');
	}

	private function commitChanges($contentDir, $messageFile)
	{
		if ( ! file_exists($messageFile)) {
			throw new \Exception("Message file '$messageFile' does not exist.");
		}
		chdir($contentDir);
		$this->output->writeln('Pulling eventual changes');
		shell_exec('git pull');
		$this->output->writeln('Staging current changes');
		shell_exec('git add .; git add -u .');
		$this->output->writeln('Commiting current changes');
		shell_exec("git commit --file='$messageFile'");
		$this->output->writeln('Pushing commit to remote server');
		shell_exec('git push');
	}

	private function createMessageFile($description, $fromTime)
	{
		$filename = sys_get_temp_dir().'/chitanka-commit-content-'.time();
		file_put_contents($filename, $this->createMessageFileContents($description, $fromTime));
		return $filename;
	}

	private function createMessageFileContents($description, $fromTime)
	{
		$booksChanges = $this->getChangedBooksDescriptions($fromTime);
		$booksMessage = '';
		if (count($booksChanges) > 0) {
			$booksMessage .= count($booksChanges) == 1 ? 'Book:' : 'Books:';
			$booksMessage .= "\n" . implode("\n", $booksChanges);
		}

		$textsChanges = $this->getChangedTextsDescriptions($fromTime);
		$textsMessage = '';
		if (count($textsChanges) > 0) {
			$textsMessage .= count($textsChanges) == 1 ? 'Text:' : 'Texts:';
			$textsMessage .= "\n" . implode("\n", $textsChanges);
		}

		return <<<MSG
$description

$booksMessage

$textsMessage

MSG;
	}

	/**
	 * @RawSql
	 */
	private function getChangedBooksDescriptions($fromTime)
	{
		$sql = "SELECT CONCAT(b.id, ' / ', br.comment, ' / ', IFNULL(b.title_author, ''), ' — ', b.title, ' / ', b.type)
FROM `book_revision` br
LEFT JOIN book b on br.book_id = b.id
WHERE br.date > DATE_SUB(NOW(), INTERVAL '$fromTime' MINUTE)
LIMIT 1000";
		return $this->em->getConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * @RawSql
	 */
	private function getChangedTextsDescriptions($fromTime)
	{
		$sql = "SELECT CONCAT(t.id, ' / ', tr.comment, ' / ', GROUP_CONCAT(p.name SEPARATOR ', '), ' — ', t.title, ' / ', t.type)
FROM `text_revision` tr
LEFT JOIN `text` t ON tr.text_id = t.id
LEFT JOIN text_author ta ON ta.text_id = t.id
LEFT JOIN person p ON p.id = ta.person_id
WHERE tr.date > DATE_SUB(NOW(), INTERVAL '$fromTime' MINUTE)
GROUP BY tr.id
LIMIT 1000";
		return $this->em->getConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);
	}
}
