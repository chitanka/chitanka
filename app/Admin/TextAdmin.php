<?php namespace App\Admin;

use App\Entity\Text;
use App\Service\ContentService;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
			->add('links', null, ['label' => 'Site Links'])
			->add('createdAt')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('title')
			->add('slug')

			->add('_action', 'actions', [
				'actions' => [
					'show' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$translation = $this->getTranslation();
		$formMapper->tab('General attributes')->with('')
			->add('slug')
			->add('title')
			->add('lang')
			->add('origLang')
			->add('type')
			->add('textAuthors', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'pos',
			])
			->add('textTranslators', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'pos',
			])
			->end()->end();
		$formMapper->tab('Extra attributes')->with('')
			->add('subtitle', null, ['required' => false])
			->add('origTitle', null, ['required' => false])
			->add('origSubtitle', null, ['required' => false])
			->add('year', null, ['required' => false])
			->add('year2', null, ['required' => false])
			->add('transYear', null, ['required' => false])
			->add('transYear2', null, ['required' => false])
			->add('origLicense', null, ['required' => false])
			->add('transLicense', null, ['required' => false])
			->add('series', 'sonata_type_model_list', ['required' => false])
			->add('sernr', null, ['required' => false])
			->add('note')
			->add('article')
			->add('links', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
				'label' => 'Site Links',
			], [
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'site_id'
			])
			->end()->end();
		$formMapper->setHelps([
			'article' => $this->trans('help.wiki_article')
		]);
		$formMapper->tab('Textual content')->with('')
			->add('annotation', 'textarea', [
				'required' => false,
				'trim' => false,
				'attr' => [
					'class' => 'span12',
				],
			])
			->add('extra_info', 'textarea', [
				'required' => false,
				'trim' => false,
				'attr' => [
					'class' => 'span12',
				],
			])
			->add('content_file', 'file', ['required' => false])
			->add('headlevel', null, ['required' => false])
			->add('revision_comment', 'text', ['required' => false])
			->add('source', null, ['required' => false])
			->add('removedNotice')
			->end()->end();
		$formMapper->tab('Contributions')->with('')
			->add('userContribs', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				//'inline' => 'table',
				'sortable' => 'date',
			])
			->end()->end();
		$formMapper->setHelps([
			'sernr' => $this->trans('help.text.sernr'),
		]);

		$builder = $formMapper->getFormBuilder();
		$builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'fixNewLines']);
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formMapper) {
			$text = $event->getData();
			if ($text instanceof Text) {
				$formMapper->setHelps([
					'content_file' => sprintf('(<a href="/%s">настоящ файл</a>)', ContentService::getContentFilePath('text', $text->getId())),
				]);
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

	/**
	 * @param Text $text
	 */
	public function preUpdate($text) {
		$this->fixRelationships($text);
		if ($text->getRevisionComment()) {
			$text->addNewRevision($text->getRevisionComment());
		}
	}

	/**
	 * @param Text $text
	 */
	public function postUpdate($text) {
		$text->persistAnnotation();
		$text->persistExtraInfo();
		$file = $text->getContentFile();
		if ($file !== null) {
			$filename = ContentService::getContentFilePath('text', $text->getId());
			$file->move(dirname($filename), basename($filename));
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
