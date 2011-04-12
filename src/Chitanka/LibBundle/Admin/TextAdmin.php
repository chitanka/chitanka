<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;

use Chitanka\LibBundle\Util\Language;
use Chitanka\LibBundle\Legacy\Legacy;

class TextAdmin extends Admin
{
	protected $list = array(
		'title' => array('identifier' => true),
		'slug',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'slug',
		'title',
		'subtitle',
		'lang',
		'trans_year',
		'trans_year2',
		'orig_title',
		'orig_subtitle',
		'orig_lang',
		'year',
		'year2',
		'orig_license',
		'trans_license',
		'type',
		'series',
		'sernr',
		'sernr2',
		'headlevel',
		//'size',
		//'zsize',
		'source',
		//'cur_rev',
		'mode' => array(
			'type' => 'choice',
			'form_field_options' => array(
				'choices' => array(
					'public' => 'Видим',
					'private' => 'Скрит',
				),
				'expanded' => true
			)
		),
	);

	protected $filter = array(
		'title',
	);

	protected function configureFormFields(FormMapper $form)
	{
		$form->add('lang', array('choices' => Language::getLangs()), array('type' => 'choice'));
		$form->add('orig_lang', array('choices' => Language::getLangs()), array('type' => 'choice'));
		$form->add('type', array('choices' => array('' => '') + Legacy::workTypes()), array('type' => 'choice'));
		$form->add('series', array(
			'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			},
			'required' => false
		));
	}
}
