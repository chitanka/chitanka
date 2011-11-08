<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Util\Language;
use Chitanka\LibBundle\Legacy\Legacy;

class TextAdmin extends Admin
{
	protected $baseRoutePattern = 'text';
	protected $baseRouteName = 'admin_text';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('title')
			->add('slug')

			->add('_action', 'actions', array(
				'actions' => array(
					'delete' => array(),
					'edit' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->add('slug')
			->add('title')
			->add('textAuthors', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'pos',
			))
			->add('textTranslators', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'pos',
			))
			->add('subtitle', null, array('required' => false))
			->add('lang', 'choice', array('choices' => Language::getLangs()))
			->add('trans_year', null, array('required' => false))
			->add('trans_year2', null, array('required' => false))
			->add('orig_title', null, array('required' => false))
			->add('orig_subtitle', null, array('required' => false))
			->add('orig_lang', 'choice', array('choices' => Language::getLangs()))
			->add('year', null, array('required' => false))
			->add('year2', null, array('required' => false))
			->add('orig_license', null, array('required' => false))
			->add('trans_license', null, array('required' => false))
			->add('type', 'choice', array('choices' => array('' => '') + Legacy::workTypes()))
			->add('series', 'sonata_type_model', array('required' => false), array('edit' => 'list'))
			->add('sernr', null, array('required' => false))
			->add('sernr2', null, array('required' => false))
			->add('headlevel', null, array('required' => false))
			->add('source', null, array('required' => false))
			->add('mode', 'choice', array('choices' => Text::getModeList()))
			->setHelps(array(
				'sernr2' => $this->trans('help.text.sernr2')
			))
		;
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('title')
			->add('subtitle')
			->add('lang')
			->add('trans_year')
			->add('trans_year2')
			->add('orig_title')
			->add('orig_subtitle')
			->add('orig_lang')
			->add('year')
			->add('year2')
			->add('orig_license')
			->add('trans_license')
			->add('type')
			->add('mode')
		;
	}

	public function preUpdate($text) {
		foreach ($text->getTextAuthors() as $textAuthor) {
			if ($textAuthor->getPerson()) {
				$textAuthor->setText($text);
			}
		}
		foreach ($text->getTextTranslators() as $textTranslator) {
			if ($textTranslator->getPerson()) {
				$textTranslator->setText($text);
			}
		}
	}
}
