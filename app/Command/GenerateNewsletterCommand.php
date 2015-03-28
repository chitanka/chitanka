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
			$authors = [];
			foreach ($revision['book']['authors'] as $author) {
				$authors[] = $author['name'];
			}
			$bookKey = $revision['book']['title'] . $revision['book']['subtitle'];
			$cat = $revision['book']['category']['name'];
			$booksByCat[$cat][$bookKey] = sprintf('* „%s“%s%s — http://chitanka.info/book/%d',
				$revision['book']['title'],
				($revision['book']['subtitle'] ? " ({$revision['book']['subtitle']})" : ''),
				($authors ? ' от ' . implode(', ', $authors) : ''),
				$revision['book']['id']);
		}
		return $booksByCat;
	}

	// TODO fetch only texts w/o books
	private function getTexts($month) {
		$repo = $this->getEntityManager()->getTextRevisionRepository();
		$texts = [];
		#foreach ($repo->getByDate(array('2011-07-01', '2011-08-31 23:59'), 1, null, false) as $revision) {
		foreach ($repo->getByMonth($month) as $revision) {
			$authors = [];
			foreach ($revision['text']['authors'] as $author) {
				$authors[] = $author['name'];
			}
			$key = $revision['text']['title'] . $revision['text']['subtitle'];
			$texts[$key] = sprintf('* „%s“%s%s — http://chitanka.info/text/%d',
				$revision['text']['title'],
				($revision['text']['subtitle'] ? " ({$revision['text']['subtitle']})" : ''),
				($authors ? ' от ' . implode(', ', $authors) : ''),
				$revision['text']['id']);
		}
		return $texts;
	}
}
