<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Util\Language;
use Chitanka\LibBundle\Legacy\Legacy;

class TextAdmin extends Admin
{
	protected $baseRoutePattern = 'text';
	protected $baseRouteName = 'admin_text';
	protected $translationDomain = 'admin';

	public $extraActions = 'LibBundle:TextAdmin:extra_actions.html.twig';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('slug')
			->add('title')
			->add('authors')
			->add('translators')
			->add('books')
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
			->add('series')
			->add('sernr')
			->add('sernr2')
			->add('headlevel')
			->add('source')
			->add('removed_notice')
			->add('comment_count')
//			->add('dl_count')
//			->add('read_count')
			->add('rating')
			->add('votes')
			->add('is_compilation')
			->add('labels')
			->add('headers')
			//->add('readers')
			->add('userContribs')
			->add('revisions')
			->add('created_at')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('title')
			->add('slug')

			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
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
			->add('series', 'sonata_type_model_list', array('required' => false))
			->add('sernr', null, array('required' => false))
			->add('sernr2', null, array('required' => false))
			->add('headlevel', null, array('required' => false))
			->add('source', null, array('required' => false))
			->add('removed_notice')
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
			->add('removed_notice')
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
