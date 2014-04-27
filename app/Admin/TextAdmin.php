<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use App\Util\Language;
use App\Legacy\Legacy;
use App\Entity\Text;
use App\Entity\TextRevision;

class TextAdmin extends Admin {
	protected $baseRoutePattern = 'text';
	protected $baseRouteName = 'admin_text';
	protected $translationDomain = 'admin';

	public $extraActions = 'App:TextAdmin:extra_actions.html.twig';

	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('create');
	}

	protected function configureShowField(ShowMapper $showMapper) {
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
			->add('links', null, array('label' => 'Site Links'))
			->add('created_at')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
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

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper
			->with('General attributes')
				->add('slug')
				->add('title')
				->add('lang', 'choice', array('choices' => Language::getLangs()))
				->add('orig_lang', 'choice', array('choices' => Language::getLangs()))
				->add('type', 'choice', array('choices' => array('' => '') + Legacy::workTypes()))
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
			->end()
			->with('Extra attributes')
				->add('subtitle', null, array('required' => false))
				->add('orig_title', null, array('required' => false))
				->add('orig_subtitle', null, array('required' => false))
				->add('year', null, array('required' => false))
				->add('year2', null, array('required' => false))
				->add('trans_year', null, array('required' => false))
				->add('trans_year2', null, array('required' => false))
				->add('orig_license', null, array('required' => false))
				->add('trans_license', null, array('required' => false))
				->add('series', 'sonata_type_model_list', array('required' => false))
				->add('sernr', null, array('required' => false))
				->add('sernr2', null, array('required' => false))
				->add('note')
				->add('links', 'sonata_type_collection', array(
					'by_reference' => false,
					'required' => false,
					'label' => 'Site Links',
				), array(
					'edit' => 'inline',
					'inline' => 'table',
					'sortable' => 'site_id'
				))
			->end()
			->with('Textual content')
				->add('annotation', 'textarea', array(
					'required' => false,
					'trim' => false,
					'attr' => array(
						'class' => 'span12',
					),
				))
				->add('extra_info', 'textarea', array(
					'required' => false,
					'trim' => false,
					'attr' => array(
						'class' => 'span12',
					),
				))
				->add('content_file', 'file', array('required' => false))
				->add('headlevel', null, array('required' => false))
				->add('revision_comment', 'text', array('required' => false))
				->add('source', null, array('required' => false))
				->add('removed_notice')
			->end()
			->with('Contributions')
				->add('userContribs', 'sonata_type_collection', array(
					'by_reference' => false,
					'required' => false,
				), array(
					'edit' => 'inline',
					//'inline' => 'table',
					'sortable' => 'date',
				))
			->end()
			->setHelps(array(
				'sernr2' => $this->trans('help.text.sernr2'),
			))
		;
		$builder = $formMapper->getFormBuilder();
		$builder->addEventListener(FormEvents::PRE_BIND, array($this, 'fixNewLines'));
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formMapper) {
			$text = $event->getData();
			if ($text instanceof Text) {
				$formMapper->setHelps(array(
					'content_file' => sprintf('(<a href="/%s">настоящ файл</a>)', Legacy::getContentFilePath('text', $text->getId())),
				));
			}
		});
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
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
		foreach ($text->getLinks() as $link) {
			$link->setText($text);
		}
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
		foreach ($text->getUserContribs() as $userContrib) {
			if (!$userContrib->getText()) {
				$userContrib->setText($text);
			}
		}
		if ($text->getRevisionComment()) {
			$revision = new TextRevision;
			$revision->setComment($text->getRevisionComment());
			$revision->setText($text);
			$revision->setDate(new \DateTime);
			$revision->setFirst(false);
			$text->addRevision($revision);
		}
	}
}
