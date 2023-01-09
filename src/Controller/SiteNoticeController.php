<?php namespace App\Controller;

use App\Persistence\SiteNoticeRepository;

class SiteNoticeController extends Controller {

	public function stripeAction(SiteNoticeRepository $siteNoticeRepository) {
		$siteNotice = null;
		if ( rand(0, 5) === 0 /*every fifth*/ ) {
			$siteNotice = $siteNoticeRepository->getGlobalRandom();
		}
		return $this->render('SiteNotice/stripe.html.twig', [
			'siteNotice' => $siteNotice,
		]);
	}

}
