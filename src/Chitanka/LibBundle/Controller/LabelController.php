<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Form\LabelForm;

class LabelController extends Controller
{
	public function createAction()
	{
	}

	public function editAction($id)
	{
		$label = $this->getRepository('Label')->find($id);
		$form = new LabelForm('label', $label, $this->get('validator'));
		$form->setEm($this->getEntityManager())->setup();

		$this->view = array(
			'label' => $label,
			'form' => $form,
		);

		if ('POST' === $this->get('request')->getMethod()) {
			$form->bindAndProcess($this->get('request')->request);
		}

		return $this->display('edit');
	}

}
