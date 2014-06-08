<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountsDbCommand extends Command {

	protected function configure() {
		parent::configure();

		$this
			->setName('db:update-counts')
			->setDescription('Update some total counts in the database')
			->setHelp(<<<EOT
The <info>db:update-counts</info> command updates some total counts in the database. For example number of texts by every label, or number of books by every category.
EOT
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->updateCounts($output, $this->getEntityManager());
		$output->writeln('Done.');
	}

	protected function updateCounts(OutputInterface $output, $em) {
		$this->updateTextCountByLabels($output, $em);
		$this->updateTextCountByLabelsParents($output, $em);
		$this->updateCommentCountByTexts($output, $em);
		$this->updateBookCountByCategories($output, $em);
		// disable for now, TODO fix pagination by parent categories
		//$this->updateBookCountByCategoriesParents($output, $em);
	}

}
