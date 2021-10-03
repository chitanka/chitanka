<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\ContentService;

class UpdateBookInfoFromBibliomanCommand extends Command {

	public function getName() {
		return 'db:update-book-info-from-biblioman';
	}

	protected function getRequiredArguments() {
		return [
			'id' => 'A book ID',
		];
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$book = $this->getEntityManager()->getBookRepository()->find($input->getArgument('id'));
		$file = ContentService::getInternalContentFilePath('book-info', $book->getId());
		file_put_contents($file, ContentService::generateBookInfoFromBiblioman($book->getBibliomanId()));
		$output->writeln("Info written to $file");
	}
}
