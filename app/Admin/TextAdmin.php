<?php namespace App\Admin;

use App\Util\Language;
use App\Util\File;
use App\Legacy\Legacy;
use App\Entity\Text;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class TextAdmin extends Admin {
	protected $baseRoutePattern = 'text';
	protected $baseRouteName = 'admin_text';

	public $extraActions = 'App:TextAdmin:extra_actions.html.twig';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('title')
			->add('authors')
			->add('translators')
			->add('books')
			->add('subtitle')
			->add('lang')
			->add('transYear')
			->add('transYear2')
			->add('origTitle')
			->add('origSubtitle')
			->add('origLang')
			->add('year')
			->add('year2')
			->add('origLicense')
			->add('transLicense')
			->add('type')
			->add('series')
			->add('sernr')
			->add('sernr2')
			->add('headlevel')
			->add('source')
			->add('removedNotice')
			->add('commentCount')
			->add('rating')
			->add('votes')
			->add('isCompilation')
			->add('labels')
			->add('headers')
			//->add('readers')
			->add('userContribs')
			->add('revisions')
			->add('links', null, array('label' => 'Site Links'))
			->add('createdAt')
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
		$formMapper->with('General attributes');
		$formMapper
			->add('slug')
			->add('title')
			->add('lang', 'choice', array('choices' => Language::getLangs()))
			->add('origLang', 'choice', array('choices' => Language::getLangs()))
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
			));
		$formMapper->with('Extra attributes');
		$formMapper
			->add('subtitle', null, array('required' => false))
			->add('origTitle', null, array('required' => false))
			->add('origSubtitle', null, array('required' => false))
			->add('year', null, array('required' => false))
			->add('year2', null, array('required' => false))
			->add('transYear', null, array('required' => false))
			->add('transYear2', null, array('required' => false))
			->add('origLicense', null, array('required' => false))
			->add('transLicense', null, array('required' => false))
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
			));
		$formMapper->with('Textual content');
		$formMapper
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
			->add('removedNotice');
		$formMapper->with('Contributions');
		$formMapper
			->add('userContribs', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				//'inline' => 'table',
				'sortable' => 'date',
			));
		$formMapper->setHelps(array(
			'sernr2' => $this->trans('help.text.sernr2'),
		));

		$builder = $formMapper->getFormBuilder();
		$builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'fixNewLines'));
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formMapper) {
			$text = $event->getData();
			if ($text instanceof Text) {
				$formMapper->setHelps(array(
					'content_file' => sprintf('(<a href="/%s">настоящ файл</a>)', File::getContentFilePath('text', $text->getId())),
				));
			}
		});
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('subtitle')
			->add('lang')
			->add('transYear')
			->add('transYear2')
			->add('origTitle')
			->add('origSubtitle')
			->add('origLang')
			->add('year')
			->add('year2')
			->add('origLicense')
			->add('transLicense')
			->add('type')
			->add('removedNotice')
		;
	}

	/** {@inheritdoc} */
	public function prePersist($text) {
		$this->fixRelationships($text);
		$text->addNewRevision();
	}

	/** {@inheritdoc} */
	public function preUpdate($text) {
		$this->fixRelationships($text);
		if ($text->getRevisionComment()) {
			$text->addNewRevision($text->getRevisionComment());
		}
	}

	private function fixRelationships(Text $text) {
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
	}
}
