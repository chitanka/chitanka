<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Util\Fb2Validator;
use Doctrine\ORM\EntityManager;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\Book;
use Chitanka\LibBundle\Legacy\Setup;

class ValidateFb2Command extends ContainerAwareCommand
{
	/** @var EntityManager */
	private $em;

	protected function configure()
	{
		parent::configure();

		$command = 'lib:validate-fb2';
		$this
			->setName($command)
			->addArgument('id', InputArgument::IS_ARRAY, 'A text or a book ID or an ID range')
			->setDescription('Validate FB2 archives of texts and books')
			->setHelp(<<<EOT
The <info>$command</info> allows validation of text and book archives.

Example calls:

	<info>$command</info> text:1
	<info>$command</info> 1
		Validate the text with an ID 1

	<info>$command</info> text:1-10
	<info>$command</info> 1-10
		Validates texts with IDs between 1 and 10

	<info>$command</info> book:1-10
		Validates books with IDs between 1 and 10

	<info>$command</info> text:1-5 book:1-10
		Validates texts with IDs between 1 and 5, and books with IDs between 1 and 10
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
		$this->output = $output;

		Setup::doSetup($this->getContainer());
		list($textIds, $bookIds) = $this->parseInputIds($input->getArgument('id'));
		$this->validator = new Fb2Validator();
		$this->validateTexts($textIds);
		$this->validateBooks($bookIds);
	}

	private function parseInputIds($inputIds)
	{
		$ids = array(
			'text' => array(),
			'book' => array(),
		);
		foreach ($inputIds as $inputId) {
			if (strpos($inputId, ':') === false) {
				$inputId = 'text:'.$inputId;
			}
			list($type, $idRange) = explode(':', $inputId);
			if (strpos($idRange, '-') !== false) {
				list($firstId, $lastId) = explode('-', $idRange);
				$ids[$type] = array_merge($ids[$type], range($firstId, $lastId));
			} else {
				$ids[$type][] = (int) $idRange;
			}
		}
		foreach ($ids as $type => $typeIds) {
			$ids[$type] = array_unique($typeIds);
		}

		return array_values($ids);
	}

	private function validateTexts($textIds)
	{
		foreach ($textIds as $textId) {
			$this->output->writeln("Validating text $textId");
			/* @var $text Text */
			$text = $this->em->getRepository('LibBundle:Text')->find($textId);
			if (!$this->validator->isValid($text->getContentAsFb2())) {
				throw new \Exception($this->validator->getErrors());
			}
		}
	}

	private function validateBooks($bookIds)
	{
		foreach ($bookIds as $bookId) {
			$this->output->writeln("Validating book $bookId");
			/* @var $book Book */
			$book = $this->em->getRepository('LibBundle:Book')->find($bookId);
			if (!$this->validator->isValid($book->getContentAsFb2())) {
				throw new \Exception($this->validator->getErrors());
			}
		}
	}

}
