<?php namespace App\Controller\Admin;

use App\Entity\Text;
use App\Form\Type\TextAuthorType;
use App\Form\Type\TextLinkType;
use App\Form\Type\TextTranslatorType;
use App\Form\Type\UserTextContribType;
use App\Service\ContentService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TextCrudController extends CrudController {
	protected static $entityClass = Text::class;

	public function configureActions(Actions $actions): Actions {
		$actions->disable(Action::NEW, Action::DELETE);

		$viewInFrontend = $this->viewInFrontendAction('text_show', function(Text $text): array {
			return ['id' => $text->getId()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield FormField::addTab('General attributes');
		yield IdField::new('id')->hideOnForm();
		yield Field::new('title')->setColumns(6);
		yield SlugField::new('slug')->setTargetFieldName('title')->setColumns(6)->hideOnIndex();
		yield Field::new('subtitle')->hideOnIndex();
		// TODO Use a CRUD controller with autocomplete collections when EasyAdmin 4 is available
		yield $this->collectionField('textAuthors', TextAuthorType::class)->setColumns(6);
		yield $this->collectionField('textTranslators', TextTranslatorType::class)->setColumns(6);
		yield AssociationField::new('lang')->setColumns(6)->hideOnIndex();
		yield AssociationField::new('origLang')->setColumns(6)->hideOnIndex();
		yield AssociationField::new('type');

		yield FormField::addTab('Extra attributes');
		yield Field::new('origTitle')->setColumns(6)->hideOnIndex();
		yield Field::new('origSubtitle')->setColumns(6)->hideOnIndex();
		yield Field::new('year')->setColumns(6)->hideOnIndex();
		yield Field::new('year2')->setColumns(6)->hideOnIndex();
		yield Field::new('transYear')->setColumns(6)->hideOnIndex();
		yield Field::new('transYear2')->setColumns(6)->hideOnIndex();
		yield AssociationField::new('origLicense')->setColumns(6)->hideOnIndex();
		yield AssociationField::new('transLicense')->setColumns(6)->hideOnIndex();
		yield $this->associationFieldWithManyItems('series')->setColumns(6)->hideOnIndex();
		yield Field::new('sernr')->setHelp('help.text.sernr')->setColumns(6)->hideOnIndex();
		yield Field::new('note')->setHelp('help.text.note')->hideOnIndex();
		yield Field::new('article')->setHelp('help.wiki_article')->hideOnIndex();

		if ($pageName === Action::EDIT) {
			yield FormField::addTab('Textual content');
			yield CodeEditorField::new('annotation')->hideOnIndex();
			yield CodeEditorField::new('extraInfo')->hideOnIndex();
			yield $this->createContentFileField($this->getContext()->getEntity()->getInstance());
			yield Field::new('headlevel')->hideOnIndex();
			yield Field::new('revisionComment')->setHelp('help.text.revisionComment')->hideOnIndex();
			yield Field::new('source')->hideOnIndex();
			yield Field::new('removedNotice')->hideOnIndex();

			yield FormField::addTab('Contributions');
			yield $this->collectionField('userContribs', UserTextContribType::class)->hideOnIndex();

			yield FormField::addTab('Links');
			yield $this->collectionField('links', TextLinkType::class, 'Site Links')->hideOnIndex();
		}
	}

	private function createContentFileField(Text $text): FieldInterface {
		return TextField::new('contentFile')->setFormType(FileUploadType::class)
			->setFormTypeOption('upload_dir', 'var/uploads') // needed for the initial configuration, but not used afterward
			->setFormTypeOption('upload_new', function(UploadedFile $file, string $uploadDir, string $fileName) use ($text) {
				$filename = ContentService::getContentFilePath('text', $text->getId());
				$file->move(dirname($filename), basename($filename));
				$text->rebuildHeaders();
			})->setHelp(sprintf('(<a href="/%s">настоящ файл</a>)', ContentService::getContentFilePath('text', $text->getId())));
	}

	public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void {
		$this->prePersist($entityInstance);
		parent::persistEntity($entityManager, $entityInstance);
	}

	public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void {
		$this->preUpdate($entityInstance);
		parent::updateEntity($entityManager, $entityInstance);
		$this->postUpdate($entityInstance);
	}

	private function prePersist(Text $text) {
		$text->addNewRevision();
	}

	private function preUpdate(Text $text) {
		if ($text->getRevisionComment()) {
			$text->addNewRevision($text->getRevisionComment());
		}
	}

	private function postUpdate(Text $text) {
		$text->persistAnnotation();
		$text->persistExtraInfo();
	}
}
