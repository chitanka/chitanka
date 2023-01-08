<?php namespace App\Controller;

class WorkEntryController extends Controller {

	public function latestAction($limit = 10) {
		return [
			'entries' => $this->em()->getWorkEntryRepository()->getLatest($limit),
		];
	}
}
