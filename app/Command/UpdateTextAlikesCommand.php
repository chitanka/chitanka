<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\TextService;

class UpdateTextAlikesCommand extends Command {

	public function getName() {
		return 'db:update-text-alikes';
	}

	public function getDescription() {
		return 'Update similar texts (alikes) of texts';
	}

	/** @inheritdoc */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$textService = new TextService($this->olddb());
		$maxAlikesCount = 50;
		$repo = $this->getEntityManager()->getTextRepository();
		$dql = 'SELECT t FROM App:Text t WHERE t.id < 10';
		$iterableResult = $this->getEntityManager()->createQuery($dql)->iterate();
		foreach ($iterableResult AS $row) {
			$text = $row[0];
			$alikes = $textService->findTextAlikes($text, $maxAlikesCount);
			if ($text->getAlikes() != $alikes) {
				$text->setAlikes($alikes);
				$repo->save($text);
				$output->write('.');
			}
		}
	}
}
