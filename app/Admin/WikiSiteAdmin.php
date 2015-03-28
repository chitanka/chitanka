<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class WikiSiteAdmin extends Admin {
	protected $baseRoutePattern = 'wiki-site';
	protected $baseRouteName = 'admin_wiki_site';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('code')
			->add('name')
			->add('url')
			->add('intro')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('url')
			->add('_action', 'actions', [
				'actions' => [
					'view' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper->with('General attributes');
		$formMapper
			->add('code')
			->add('name')
			->add('url')
			->add('intro', null, ['required' => false])
			->setHelps([
				'code' => $this->trans('help.wikisite.code'),
				'url' => $this->trans('help.wikisite.url'),
				'intro' => $this->trans('help.wikisite.intro'),
			]);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('name')
			->add('url')
			->add('intro')
		;
	}

}
