<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class WikiSiteAdmin extends Admin {
	protected $baseRoutePattern = 'wiki-site';
	protected $baseRouteName = 'admin_wiki_site';
	protected $translationDomain = 'admin';

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
		$formMapper
			->with('General attributes')
				->add('code')
				->add('name')
				->add('url')
				->add('intro', null, array('required' => false))
				->setHelps(array(
					'code' => $this->trans('help.wikisite.code'),
					'url' => $this->trans('help.wikisite.url'),
					'intro' => $this->trans('help.wikisite.intro'),
				))
			->end()
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('name')
			->add('url')
			->add('intro')
		;
	}

}
