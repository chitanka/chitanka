<?php namespace App\Controller;

use App\Persistence\WorkEntryRepository;

class WorkEntryController extends Controller {

	public function latestAction(WorkEntryRepository $workEntryRepository, $limit = 10) {
		return [
			'entries' => $workEntryRepository->getLatest($limit),
		];
	}
}
