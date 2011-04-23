<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class TextCommentAdmin extends Admin
{
	protected $baseRouteName = 'textcomment';

	protected $list = array(
		'text',
		//'user',
		'rname',
		'time',
		'is_shown',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'rname',
		'content',
		'is_shown',
	);

	protected $filter = array(
		'rname',
		'is_shown',
	);
}
