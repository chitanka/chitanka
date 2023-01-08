<?php namespace App\Controller;

class SiteNoticeController extends Controller {

	public function stripeAction() {
		$siteNotice = null;
		if ( rand(0, 5) === 0 /*every fifth*/ ) {
			$siteNotice = $this->em()->getSiteNoticeRepository()->getGlobalRandom();
		}
		return $this->render('App:SiteNotice:stripe.html.twig', [
			'siteNotice' => $siteNotice,
		]);
	}

}
