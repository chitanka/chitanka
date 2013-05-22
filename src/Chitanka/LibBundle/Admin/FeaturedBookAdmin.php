<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class FeaturedBookAdmin extends Admin
{
	protected $baseRoutePattern = 'featured-book';
	protected $baseRouteName = 'admin_featured_book';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('title')
			->add('author')
			->add('url')
			->add('cover')
			->add('description')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('cover', 'string', array('template' => 'LibBundle:FeaturedBookAdmin:list_cover.html.twig'))
			->addIdentifier('title')
			->add('author')
			->add('url', 'string', array('template' => 'LibBundle:FeaturedBookAdmin:list_url.html.twig'))
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->with('General attributes')
				->add('title')
				->add('author')
				->add('url')
				->add('cover')
				->add('description')
			->end()
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('title')
			->add('author')
			->add('url')
		;
	}

}
