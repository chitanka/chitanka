<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateHeadersDbCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('db:update-headers')
			->setDescription('Update text headers in the database')
			->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Output SQL queries instead of executing them')
			->setHelp(<<<EOT
The <info>db:update-headers</info> command updates the text headers in the database.
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
		$this->em = $this->container->get('doctrine.orm.default_entity_manager');
		$this->output = $output;
		$this->dumpSql = $input->getOption('dump-sql') === true;
		$this->updateHeaders($this->dumpSql);
		$output->writeln('/*Done.*/');
	}


	protected function updateHeaders($dumpSql)
	{
		$queries = array();
		$iterableResult = $this->em->createQuery('SELECT t FROM LibBundle:Text t WHERE t.headlevel > 0')->iterate();
		foreach ($iterableResult AS $row) {
			$text = $row[0];
			$queries = array_merge($queries, $this->buildTextHeadersUpdateQuery($this->webDir($text->getMainContentFile()), $text->getId(), $text->getHeadlevel()));
			$this->em->detach($text);
		}

		if ($dumpSql) {
			$this->printQueries($queries);
		} else {
			$this->executeUpdates($queries, $this->em->getConnection());
		}
	}

}
