<?php namespace App\Admin;

use App\Entity\ForeignBook;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ForeignBookAdmin extends Admin {
	protected $baseRoutePattern = 'foreign-book';
	protected $baseRouteName = 'admin_foreign_book';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('author')
			->add('title')
			->add('sequence')
			->add('sequenceNo')
			->add('externalUrl')
			->add('cover')
			->add('annotation')
			->add('excerpt', 'html')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('cover', 'string', ['template' => 'App:ForeignBookAdmin:list_cover.html.twig'])
			->addIdentifier('title')
			->add('author')
			->add('publisher')
			->add('isActive')
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
		/** @var $book ForeignBook */
		$book = $this->getSubject();
		$coverFileOptions = ['label' => 'Cover', 'required' => false];
		if ($book && ($webPath = $book->getCoverPath())) {
			$fullPath = $this->getRequest()->getBasePath() .'/'. $webPath;
			$coverFileOptions['help'] = '<img src="'.$fullPath.'" class="admin-preview" width="90"/>';
		}
		$translation = $this->getTranslation();
		$formMapper->with('General attributes')
			->add('author')
			->add('title')
			->add('sequence')
			->add('sequenceNo')
			->add('publisher')
			->add('externalUrl', null, ['help' => 'help.foreign_book.external_url'])
			->add('coverFile', 'file', $coverFileOptions)
			->add('annotation')
			->add('category')
			//->add('labels')
			->add('formats', 'choice', [
				'choices' => $translation->getForeignBookFormatsChoices(),
				'multiple' => true,
				'expanded' => true,
				'attr' => ['class' => 'list-inline'],
			])
			->add('excerpt', null, ['attr' => ['class' => 'richhtml']])
			->add('publishedAt', 'sonata_type_date_picker')
			->add('isActive', null, ['help' => 'help.foreign_book.is_active'])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('author')
			->add('externalUrl')
		;
	}

	/**
	 * @param ForeignBook $book
	 */
	public function preUpdate($book) {
		$this->saveCover($book);
	}

	protected function saveCover(ForeignBook $book) {
		$book->upload($this->getRequest()->getBasePath());
	}
}
