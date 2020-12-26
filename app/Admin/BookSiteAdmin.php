<?php namespace App\Admin;

use App\Entity\BookSite;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookSiteAdmin extends Admin {
	protected $baseRoutePattern = 'book-site';
	protected $baseRouteName = 'admin_book_site';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('url')
			->add('mediaType')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('url')
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
		$formMapper->with('General attributes')
			->add('name')
			->add('url')
			->add('mediaType', 'choice', [
				'required' => false,
				'choices' => array_combine(BookSite::MEDIA_TYPES, BookSite::MEDIA_TYPES),
			])
			->end()
			->setHelps([
				'url' => $this->trans('help.booksite.url')
			]);

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('name')
			->add('url')
			->add('mediaType')
		;
	}

}
