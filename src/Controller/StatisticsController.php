<?php namespace App\Controller;

use App\Persistence\BookRepository;
use App\Persistence\CategoryRepository;
use App\Persistence\CountryRepository;
use App\Persistence\LabelRepository;
use App\Persistence\PersonRepository;
use App\Persistence\SequenceRepository;
use App\Persistence\SeriesRepository;
use App\Persistence\TextCommentRepository;
use App\Persistence\TextRepository;
use App\Persistence\TextTypeRepository;

class StatisticsController extends Controller {

	public function indexAction(
		PersonRepository $personRepository,
		TextRepository $textRepository,
		BookRepository $bookRepository,
		LabelRepository $labelRepository,
		SeriesRepository $seriesRepository,
		SequenceRepository $sequenceRepository,
		CategoryRepository $categoryRepository,
		TextCommentRepository $textCommentRepository,
		CountryRepository $countryRepository,
		TextTypeRepository $textTypeRepository
	) {
		return [
			'count' => [
				'authors'       => $personRepository->asAuthor()->getCount(),
				'translators'   => $personRepository->asTranslator()->getCount(),
				'texts'         => $textRepository->getCount(),
				'series'        => $seriesRepository->getCount(),
				'labels'        => $labelRepository->getCount(),
				'books'         => $bookRepository->getCount(),
				'books_wo_cover'=> $bookRepository->getCountWithMissingCover(),
				'books_wo_biblioman'=> $bookRepository->getCountWithMissingBibliomanId(),
				'sequences'     => $sequenceRepository->getCount(),
				'categories'    => $categoryRepository->getCount(),
				'text_comments' => $textCommentRepository->getCount('e.is_shown = 1'),
				'users'         => $this->userRepository->getCount(),
			],
			'countries'  => $countryRepository->findAll(),
			'textTypes' => $textTypeRepository->findAll(),
		];
	}

}
