<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateNewsletterCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('lib:generate-newsletter')
			->setDescription('Generate newsletter')
			->addArgument('month', InputArgument::REQUIRED, 'Month (3 or 2011-3)')
			->setHelp(<<<EOT
The <info>lib:generate-newsletter</info> generates the newsletter for a given month.
EOT
		);
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract class is not implemented
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
		$this->em->getConfiguration()->addCustomHydrationMode('id', 'App\Hydration\IdHydrator');
		$this->em->getConfiguration()->addCustomHydrationMode('key_value', 'App\Hydration\KeyValueHydrator');
		$this->input = $input;
		$this->output = $output;
		$this->generateNewsletter($input->getArgument('month'));
	}


	/**
	*/
	protected function generateNewsletter($month)
	{
		$this->output->writeln("\n= Книги =\n");
		$booksByCat = $this->_getBooks($month);
		ksort($booksByCat);
		foreach ($booksByCat as $cat => $bookRows) {
			$this->output->writeln("\n== $cat ==\n");
			ksort($bookRows);
			foreach ($bookRows as $bookRow) {
				$this->output->writeln($bookRow);
			}
		}

		$this->output->writeln("\n\n= Произведения, невключени в книги =\n");
		$textRows = $this->_getTexts($month);
		ksort($textRows);
		foreach ($textRows as $textRow) {
			$this->output->writeln($textRow);
		}
	}

	private function _getBooks($month)
	{
		$repo = $this->em->getRepository('App:BookRevision');
		$booksByCat = array();
		#foreach ($repo->getByDate(array('2011-07-01', '2011-08-31 23:59'), 1, null, false) as $revision) {
		foreach ($repo->getByMonth($month) as $revision) {
			$authors = array();
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
	private function _getTexts($month)
	{
		$repo = $this->em->getRepository('App:TextRevision');
		$texts = array();
		#foreach ($repo->getByDate(array('2011-07-01', '2011-08-31 23:59'), 1, null, false) as $revision) {
		foreach ($repo->getByMonth($month) as $revision) {
			$authors = array();
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
