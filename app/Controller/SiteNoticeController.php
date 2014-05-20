<?php namespace App\Controller;

class SiteNoticeController extends Controller {

	public function stripeAction() {
		if ( rand(0, 5) === 0 /*every fifth*/ ) {
			$this->view = array(
				'siteNotice' => $this->em()->getSiteNoticeRepository()->getGlobalRandom(),
			);
		}

		return $this->render('App:SiteNotice:stripe.html.twig', $this->view);
	}

}
