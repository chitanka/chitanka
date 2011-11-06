<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class WikiSiteAdmin extends Admin
{
	protected $baseRoutePattern = 'wiki-site';
	protected $baseRouteName = 'admin_wiki_site';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('url')
			->add('_action', 'actions', array(
				'actions' => array(
					'delete' => array(),
					'edit' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->add('code')
			->add('name')
			->add('url')
			->add('intro', null, array('required' => false))
			->setHelps(array(
				'code' => $this->trans('admin.help.wikisite.code'),
				'url' => $this->trans('admin.help.wikisite.url'),
				'intro' => $this->trans('admin.help.wikisite.intro'),
			))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('url')
			->add('intro')
		;
	}

}
