<?php namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\BookRevision;
use App\Form\Type\BookIsbnType;
use App\Form\Type\BookLinkType;
use App\Persistence\BookRepository;
use App\Persistence\TextRepository;
use App\Service\ContentService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use Symfony\Component\HttpFoundation\Request;

class BookCrudController extends CrudController {

	const ACTION_UPDATE_COVER = 'updateCover';

	protected static $entityClass = Book::class;

	private $textRepository;

	public function __construct(TextRepository $textRepository) {
		$this->textRepository = $textRepository;
	}

	public function configureActions(Actions $actions): Actions {
		$actions->disable(Action::NEW, Action::DELETE);

		$updateCover = Action::new(self::ACTION_UPDATE_COVER, 'Корица от БМ', 'fa fa-image')
			->linkToCrudAction(self::ACTION_UPDATE_COVER)
			->displayIf(function(Book $book): bool {
				return $book->getBibliomanId() !== null;
			});
		$viewInFrontend = $this->viewInFrontendAction('book_show', function(Book $book): array {
			return ['id' => $book->getId()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		$actions->add(Crud::PAGE_INDEX, $updateCover);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield FormField::addTab('General attributes');
		yield IdField::new('id')->hideOnForm();
		yield IdField::new('bibliomanId')->setTemplatePath('Admin/Book/list_biblioman.html.twig');
		yield Field::new('title')->setColumns(6);
		yield SlugField::new('slug')->setTargetFieldName('title')->setColumns(6)->hideOnIndex();
		yield Field::new('titleAuthor')->hideOnForm();
		yield $this->associationFieldWithManyItems('authors')->hideOnIndex();
		yield Field::new('year')->hideOnIndex();
		yield AssociationField::new('lang')->setColumns(6)->hideOnIndex();
		yield AssociationField::new('origLang')->setColumns(6)->hideOnIndex();
		yield ChoiceField::new('type')->setChoices($this->getTranslation()->getBookTypeChoices());

		yield FormField::addTab('Extra attributes');
		yield Field::new('subtitle')->setColumns(6)->hideOnIndex();
		yield Field::new('titleExtra')->setColumns(6)->hideOnIndex();
		yield Field::new('origTitle')->hideOnIndex();
		yield $this->associationFieldWithManyItems('sequence')->setColumns(6)->hideOnIndex();
		yield Field::new('seqnr')->setColumns(6)->hideOnIndex();
		yield $this->associationFieldWithManyItems('category')->hideOnIndex();
		yield $this->collectionField('isbns', BookIsbnType::class)->hideOnIndex();
		yield $this->collectionField('links', BookLinkType::class, 'Site Links')->hideOnIndex();

		yield FormField::addTab('Textual content');
		yield CodeEditorField::new('rawTemplate', 'Template')->hideOnIndex();
		yield CodeEditorField::new('annotation')->hideOnIndex();
		yield CodeEditorField::new('extraInfo')->hideOnIndex();
		yield ChoiceField::new('formats')
			->setChoices(array_combine(Book::FORMATS, Book::FORMATS))
			->allowMultipleChoices()
			->renderExpanded()
			->setFormTypeOptions(['choice_translation_domain' => false])
			->hideOnIndex();
		yield Field::new('revisionComment')->setHelp('help.book.revisionComment')->hideOnIndex();
		yield Field::new('removedNotice')->hideOnIndex();

		if ($pageName === Action::EDIT) {
			$coverHtml = $this->generateCoverElementsForEdit($this->getContext()->getEntity()->getInstance());
			if (!empty($coverHtml)) {
				yield FormField::addTab('Cover');
				yield BooleanField::new('hasCover', false)->setDisabled()->setHelp($coverHtml);
			}
		}
	}

	protected function generateCoverElementsForEdit(Book $book): string {
		$html = '';
		if ($book->hasCover()) {
			$html .= '<img src="/' . ContentService::getCover($book->getId(), 300) . '">';
		}
		if ($book->getBibliomanId()) {
			$updateCoverUrl = $this->adminUrlGenerator()
				->setAction(self::ACTION_UPDATE_COVER)
				->setEntityId($book->getId())
				->generateUrl();
			$html .= '<br><br><a href="'.$updateCoverUrl.'" class="btn btn-primary update_cover_link" title="Копиране на корицата от Библиоман"><span class="fa fa-image"></span> Корица от БМ</a>';
		}
		return $html;
	}

	public function updateCover(AdminContext $context, Request $request, BookRepository $bookRepository) {
		$book = $context->getEntity()->getInstance();/* @var $book Book */

		if (!$book) {
			throw $this->createNotFoundException('Unable to locate a book');
		}

		if ($request->isMethod(Request::METHOD_POST)) {
			ContentService::copyCoverFromBiblioman($book);
			$book->setHasCover(true);
			$bookRepository->save($book);

			$this->addFlash('success', "Корицата на „{$book}“ беше обновена.");

			return $this->redirectToIndex($context);
		}
		return $this->render('Admin/Book/update_cover.html.twig', [
			'book' => $book,
		]);
	}

	public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void {
		$this->preUpdate($entityInstance);
		parent::updateEntity($entityManager, $entityInstance);
		$this->postUpdate($entityInstance);
	}

	private function preUpdate(Book $book) {
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

	private function postUpdate(Book $book) {
		$book->persistAnnotation();
		$book->persistExtraInfo();
	}
}
