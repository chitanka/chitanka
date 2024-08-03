<?php namespace App\Command;

use App\Entity\TextRevision;
use App\Persistence\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment as Twig;

class GenerateNewsletterCommand extends Command {

	/** @var Twig */private $twig;

	public function __construct(EntityManager $em, ParameterBagInterface $parameters, Twig $twig) {
		parent::__construct($em, $parameters);
		$this->twig = $twig;
	}

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
			'date' => 'Year-Month, e.g. 2011-3',
		];
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$date = new \DateTime($input->getArgument('date'));
		$newsletter = $this->generateNewsletter($date);
		$output->write($newsletter);
		return self::SUCCESS;
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function generateNewsletter(\DateTime $date) {
		$booksByCategory = $this->getBooksByCategory($date);
		ksort($booksByCategory);
		return $this->twig->render('Email/newsletter.html.twig', [
			'date' => $date,
			'booksByCategory' => $booksByCategory,
			'texts' => $this->getTexts($date),
		]);
	}

	private function getBooksByCategory(\DateTime $date) {
		$repo = $this->getEntityManager()->getBookRevisionRepository();
		$books = [];
		foreach ($repo->getByMonth($date) as $revision) {
			$book = $revision->getBook();
			$cat = $book->getCategory()->getName();
			$books[$cat][] = $book;
		}
		return $books;
	}

	private function getTexts(\DateTime $date) {
		$repo = $this->getEntityManager()->getTextRevisionRepository();
		return array_map(function(TextRevision $revision) {
			return $revision->getText();
		}, $repo->getByMonth($date));
	}
}
