<?php namespace App\Admin;

use App\Entity\Book;
use App\Entity\BookRevision;
use App\Persistence\TextRepository;
use App\Service\ContentService;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;

class BookAdmin extends Admin {
	protected $baseRoutePattern = 'book';
	protected $baseRouteName = 'admin_book';

	public $extraActions = 'BookAdmin/extra_actions.html.twig';

	private $textRepository;

	public function setTextRepository(TextRepository $r) {
		$this->textRepository = $r;
	}

	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('create');
		$collection->remove('delete');
		$collection->add('updateCover', $this->getRouterIdParameter().'/update-cover');
	}

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('bibliomanId')
			->add('slug')
			->add('title')
			->add('authors')
			->add('subtitle')
			->add('titleExtra')
			->add('origTitle')
			->add('lang')
			->add('origLang')
			->add('year')
			//->add('transYear')
			->add('type')
			->add('sequence')
			->add('seqnr')
			->add('category')
			->add('removedNotice')
			->add('texts')
			->add('isbns', null, ['label' => 'ISBN'])
			->add('links', null, ['label' => 'Site Links'])
			->add('createdAt')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('url', 'string', ['template' => 'BookAdmin/list_url.html.twig'])
			->add('slug')
			->addIdentifier('title')
			->add('id')
			->add('type')
			->add('bibliomanId', 'string', ['template' => 'BookAdmin/list_biblioman.html.twig'])
			->add('_action', 'actions', [
				'actions' => [
					//'show' => [],
					'edit' => [],
					'updateCover' => [
						'template' => 'BookAdmin/list__action_update_cover.html.twig',
					],
					//'delete' => [],
				]
			])
		;
	}

	//public $preFormContent = 'App:BookAdmin:form_datafiles.html.twig';

	protected function configureFormFields(FormMapper $formMapper) {
			//->add('sfbg', 'string', array('template' => 'App:BookAdmin:form_sfbg.html.twig'))
			//->add('datafiles', 'string', array('template' => 'App:BookAdmin:form_datafiles.html.twig'))
		$translation = $this->getTranslation();
		$formMapper->tab('General attributes')->with('')
			->add('bibliomanId', null, ['required' => false])
			->add('slug')
			->add('title')
			->add('lang')
			->add('origLang')
			->add('type', 'choice', ['choices' => $translation->getBookTypeChoices()])
			->add('bookAuthors', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				'inline' => 'table',
			])
			->end()->end();
		$formMapper->tab('Extra attributes')->with('')
			->add('subtitle', null, ['required' => false])
			->add('titleExtra', null, ['required' => false])
			->add('origTitle', null, ['required' => false])
			->add('year')
			//->add('transYear', null, array('required' => false))
			->add('sequence', null, ['required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}])
			->add('seqnr', null, ['required' => false])
			->add('category', null, ['required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}])
			->add('isbns', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
				'label' => 'ISBN',
			], [
				'edit' => 'inline',
				'inline' => 'table',
			])
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
		$formMapper->tab('Textual content')->with('')
			->add('raw_template', 'textarea', [
				'label' => 'Template',
				'required' => false,
				'trim' => false,
				'attr' => [
					'class' => 'span12',
				],
			])
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
			->add('formats', 'choice', [
				'choices' => array_combine(Book::FORMATS, Book::FORMATS),
				'multiple' => true,
				'expanded' => true,
				'attr' => ['class' => 'list-inline'],
			])
			->add('revision_comment', 'text', ['required' => false])
			->add('removedNotice')
			->end()->end();
		$book = $this->getSubject(); /* @var $book Book */
		$coverHtml = '';
		if ($book->hasCover()) {
			$coverHtml .= '<img src="/' . ContentService::getCover($book->getId(), 300) . '">';
		}
		if ($book->getBibliomanId()) {
			$coverHtml .= '<br><br><a href="'.$this->generateObjectUrl('updateCover', $book).'" class="btn btn btn-info update_cover_link" title="Копиране на корицата от Библиоман"><span class="fa fa-image"></span> Корица от БМ</a>';
		}
		if (!empty($coverHtml)) {
			$formMapper->tab('Cover')->with('')
				->add('cover', TextType::class, [
					'help' => $coverHtml,
					'disabled' => true,
				])
				->end()->end();
		}
		$formMapper->getFormBuilder()->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'fixNewLines']);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('subtitle')
			->add('type')
			->add('hasCover')
			->add('hasAnno')
		;
	}

	/**
	 * @param Book $book
	 */
	public function preUpdate($book) {
		foreach ($book->getIsbns() as $isbn) {
			$isbn->setBook($book);
		}
		foreach ($book->getLinks() as $link) {
			$link->setBook($book);
		}
		foreach ($book->getBookAuthors() as $bookAuthor) {
			if ($bookAuthor->getPerson()) {
				$bookAuthor->setBook($book);
			}
		}
		if ($book->textsNeedUpdate()) {
			$texts = $this->textRepository->findByIds($book->getTextIdsFromTemplate());
			$book->setTexts($texts);
		}
		if ($book->getRevisionComment()) {
			$revision = new BookRevision;
			$revision->setComment($book->getRevisionComment());
			$revision->setBook($book);
			$revision->setDate(new \DateTime);
			$revision->setFirst(false);
			$book->addRevision($revision);
		}
	}

	/**
	 * @param Book $book
	 */
	public function postUpdate($book) {
		$book->persistAnnotation();
		$book->persistExtraInfo();
	}
}
