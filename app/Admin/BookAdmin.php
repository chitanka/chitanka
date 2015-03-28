<?php namespace App\Admin;

use App\Entity\Book;
use App\Entity\BookRevision;
use App\Entity\TextRepository;
use App\Util\Language;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvents;

class BookAdmin extends Admin {
	protected $baseRoutePattern = 'book';
	protected $baseRouteName = 'admin_book';

	public $extraActions = 'App:BookAdmin:extra_actions.html.twig';

	private $textRepository;

	public function setTextRepository(TextRepository $r) {
		$this->textRepository = $r;
	}

	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('create');
	}

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
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
			->add('url', 'string', ['template' => 'App:BookAdmin:list_url.html.twig'])
			->add('slug')
			->addIdentifier('title')
			->add('id')
			->add('type')
			->add('sfbg', 'string', ['template' => 'App:BookAdmin:list_sfbg.html.twig'])
			->add('puk', 'string', ['template' => 'App:BookAdmin:list_puk.html.twig'])
			->add('_action', 'actions', [
				'actions' => [
					'view' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	//public $preFormContent = 'App:BookAdmin:form_datafiles.html.twig';

	protected function configureFormFields(FormMapper $formMapper) {
			//->add('sfbg', 'string', array('template' => 'App:BookAdmin:form_sfbg.html.twig'))
			//->add('datafiles', 'string', array('template' => 'App:BookAdmin:form_datafiles.html.twig'))
		$formMapper->with('General attributes');
		$formMapper
			->add('slug')
			->add('title')
			->add('lang', 'choice', ['choices' => Language::getLangs()])
			->add('origLang', 'choice', ['required' => false, 'choices' => Language::getLangs()])
			->add('type', 'choice', ['choices' => Book::getTypeList()])
			->add('bookAuthors', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				'inline' => 'table',
			]);
		$formMapper->with('Extra attributes');
		$formMapper
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
			]);
		$formMapper->with('Textual content');
		$formMapper
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
			->add('revision_comment', 'text', ['required' => false])
			->add('removedNotice');
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

}
