<?php namespace App\Admin;

use App\Entity\BookSite;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class TextLinkAdmin extends Admin {
	protected $baseRoutePattern = 'text-link';
	protected $baseRouteName = 'admin_text_link';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('text')
			->add('site')
			->add('code')
			->add('description')
			->add('mediaType')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('text')
			->add('site')
			->addIdentifier('code')
			->add('mediaType')
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
			//->add('text')
			->add('site')
			->add('code')
			->add('description')
			->add('mediaType', 'choice', [
				'required' => false,
				'choices' => array_combine(BookSite::MEDIA_TYPES, BookSite::MEDIA_TYPES),
			])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('site')
			->add('code')
			->add('mediaType')
		;
	}

}
