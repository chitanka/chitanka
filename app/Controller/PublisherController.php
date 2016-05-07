<?php namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/publishers")
 */
class PublisherController extends Controller {

	/**
	 * @Route("", name="publishers_index")
	 */
	public function indexAction() {
		$publishers = $this->em()->getPublisherRepository()->findAll();
		return [
			'publishers' => $publishers,
		];
	}

	/**
	 * @Route("/{slug}", name="publishers_show")
	 */
	public function showAction($slug) {
		$publisher = $this->em()->getPublisherRepository()->findOneBySlug($slug);
		return [
			'publisher' => $publisher,
		];
	}
}
