<?php
namespace Chitanka\LibBundle\Controller;

class SiteNoticeController extends Controller {

	public function stripeAction() {
		if ( rand(0, 5) === 0 /*every fifth*/ ) {
			$this->view = array(
				'siteNotice' => $this->getSiteNoticeRepository()->getGlobalRandom(),
			);
		}

		return $this->render('LibBundle:SiteNotice:stripe.html.twig', $this->view);
	}

}
