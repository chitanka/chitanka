<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\TextService;

class UpdateTextAlikesCommand extends CommonDbCommand {

	protected function configure() {
		parent::configure();
		$this->setName('db:update-text-alikes')
			->setDescription('Update similar texts (alikes) of texts');
	}

	/** @inheritdoc */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$textService = new TextService($this->olddb());
		$maxAlikesCount = 50;
		$em = $this->getEntityManager();
		$dql = 'SELECT t FROM App:Text t WHERE t.id < 10';
		$iterableResult = $em->createQuery($dql)->iterate();
		$flushGuard = 0;
		foreach ($iterableResult AS $row) {
			$text = $row[0];
			$alikes = $textService->findTextAlikes($text, $maxAlikesCount);
			if ($text->getAlikes() != $alikes) {
				$text->setAlikes($alikes);
				$em->persist($text);
				if (++$flushGuard > 500) {
					$em->flush();
				}
				$output->write('.');
			}
		}
		$em->flush();
	}
}
