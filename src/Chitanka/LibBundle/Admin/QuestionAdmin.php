<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class QuestionAdmin extends Admin
{
	protected $baseRouteName = 'question';

	protected $list = array(
		'question' => array('identifier' => true),
		'answers',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'question',
		'answers',
	);
}
