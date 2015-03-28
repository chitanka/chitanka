<?php namespace App\Command;

use App\Legacy\Setup;
use App\Util\Fb2Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateFb2Command extends Command {

	private $output;
	private $validator;

	public function getName() {
		return 'lib:validate-fb2';
	}

	public function getDescription() {
		return 'Validate FB2 archives of texts and books';
	}

	public function getHelp() {
		return <<<EOT
The <info>%command.name%</info> allows validation of text and book archives.

Example calls:

	<info>%command.name%</info> text:1
	<info>%command.name%</info> 1
		Validate the text with an ID 1

	<info>%command.name%</info> text:1-10
	<info>%command.name%</info> 1-10
		Validates texts with IDs between 1 and 10

	<info>%command.name%</info> book:1-10
		Validates books with IDs between 1 and 10

	<info>%command.name%</info> text:1-5 book:1-10
		Validates texts with IDs between 1 and 5, and books with IDs between 1 and 10
EOT;
	}

	protected function getArrayArguments() {
		return [
			'id' => 'A text or a book ID or an ID range',
		];
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;

		Setup::doSetup($this->getContainer());
		list($textIds, $bookIds) = $this->parseInputIds($input->getArgument('id'));
		$this->validator = new Fb2Validator();
		$this->validateTexts($textIds);
		$this->validateBooks($bookIds);
	}

	private function parseInputIds($inputIds) {
		$ids = [
			'text' => [],
			'book' => [],
		];
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

	private function validateTexts($textIds) {
		$this->validateWorks($textIds, $this->getEntityManager()->getTextRepository(), 'Text');
	}

	private function validateBooks($bookIds) {
		$this->validateWorks($bookIds, $this->getEntityManager()->getBookRepository(), 'Book');
	}

	/**
	 * @param array $workIds
	 * @param \Doctrine\ORM\EntityRepository $repo
	 * @param string $entityName
	 */
	private function validateWorks($workIds, $repo, $entityName) {
		foreach ($workIds as $workId) {
			$work = $repo->find($workId);
			if (!$work) {
				continue;
			}
			$this->output->writeln("Validating $entityName $workId");
			$fb2 = $work->getContentAsFb2();
			if (!$fb2) {
				continue;
			}
			if (!$this->validator->isValid($fb2)) {
				$this->saveFileInTmpDir("$entityName-{$work->getId()}.fb2", $fb2);
				throw new \Exception($this->validator->getErrors());
			}
		}
	}

	/**
	 * @param string $filename
	 * @param string $contents
	 */
	private function saveFileInTmpDir($filename, $contents) {
		file_put_contents(sys_get_temp_dir().'/'.$filename, $contents);
	}
}
