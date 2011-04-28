<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

use Chitanka\LibBundle\Util\Language;

class BookAdmin extends Admin
{
	protected $baseRouteName = 'book';

	protected $list = array(
		'url' => array('type' => 'string', 'template' => 'LibBundle:BookAdmin:list_url.html.twig'),
		'title' => array('identifier' => true),
		'id',
		'type',
		'sfbg' => array('type' => 'string', 'template' => 'LibBundle:BookAdmin:list_sfbg.html.twig'),
		'puk' => array('type' => 'string', 'template' => 'LibBundle:BookAdmin:list_puk.html.twig'),
		//'sequence',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		//'sfbg' => array('type' => 'string', 'template' => 'LibBundle:BookAdmin:form_sfbg.html.twig'),
		'slug',
		'title',
		'subtitle',
		'title_extra',
		'orig_title',
		'lang',
		'orig_lang',
		'year',
		'trans_year',
		'type' => array(
			'type' => 'choice',
			'form_field_options' => array(
				'choices' => array(
					'book' => 'Обикновена книга',
					'collection' => 'Сборник',
					'poetry' => 'Стихосбирка',
					'anthology' => 'Антология',
					'pic' => 'Разкази в картинки',
					'djvu' => 'DjVu',
				)
			)
		),
		'sequence' => array('form_field_options' => array('required' => false)),
		'seqnr',
		'category',
		//'links',
	);

	protected $filter = array(
		'title',
		'subtitle',
		'type',
		'has_cover',
		'has_anno',
	);


	protected function configureFormFields(FormMapper $form)
	{
		$form->add('lang', array('choices' => Language::getLangs()), array('type' => 'choice'));
		$form->add('orig_lang', array('choices' => Language::getLangs()), array('type' => 'choice'));
	}
}
