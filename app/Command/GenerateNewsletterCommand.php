<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateNewsletterCommand extends Command {

	private $input;
	private $output;

	public function getName() {
		return 'lib:generate-newsletter';
	}

	public function getDescription() {
		return 'Generate newsletter';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> generates the newsletter for a given month.';
	}

	protected function getRequiredArguments() {
		return [
			'month' => 'Month (3 or 2011-3)',
		];
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$this->generateNewsletter($input->getArgument('month'));
	}

	/**
	 * @param int $month
	 */
	private function generateNewsletter($month) {
		$this->output->writeln("\n= Книги =\n");
		$booksByCat = $this->getBooks($month);
		ksort($booksByCat);
		foreach ($booksByCat as $cat => $bookRows) {
			$this->output->writeln("\n== $cat ==\n");
			ksort($bookRows);
			foreach ($bookRows as $bookRow) {
				$this->output->writeln($bookRow);
			}
		}

		$this->output->writeln("\n\n= Произведения, невключени в книги =\n");
		$textRows = $this->getTexts($month);
		ksort($textRows);
		foreach ($textRows as $textRow) {
			$this->output->writeln($textRow);
		}
	}

	private function getBooks($month) {
		$repo = $this->getEntityManager()->getBookRevisionRepository();
		$booksByCat = [];
		foreach ($repo->getByMonth($month) as $revision) {
			$book = $revision->getBook();
			$authors = [];
			foreach ($book->getAuthors() as $author) {
				$authors[] = $author->getName();
			}
			$bookKey = $book->getTitle() . $book->getSubtitle();
			$cat = $book->getCategory()->getName();
			$booksByCat[$cat][$bookKey] = sprintf('* „%s“%s%s — http://chitanka.info/book/%d',
				$book->getTitle(),
				($book->getSubtitle() ? " ({$book->getSubtitle()})" : ''),
				($authors ? ' от ' . implode(', ', $authors) : ''),
				$book->getId());
		}
		return $booksByCat;
	}

	// TODO fetch only texts w/o books
	private function getTexts($month) {
		$repo = $this->getEntityManager()->getTextRevisionRepository();
		$texts = [];
		#foreach ($repo->getByDate(array('2011-07-01', '2011-08-31 23:59'), 1, null, false) as $revision) {
		foreach ($repo->getByMonth($month) as $revision) {
			$text = $revision->getText();
			$authors = [];
			foreach ($text->getAuthors() as $author) {
				$authors[] = $author->getName();
			}
			$key = $text->getTitle() . $text->getSubtitle();
			$texts[$key] = sprintf('* „%s“%s%s — http://chitanka.info/text/%d',
				$text->getTitle(),
				($text->getSubtitle() ? " ({$text->getSubtitle()})" : ''),
				($authors ? ' от ' . implode(', ', $authors) : ''),
				$text->getId());
		}
		return $texts;
	}
}
