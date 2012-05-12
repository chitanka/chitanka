<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class ForeignBookAdmin extends Admin
{
	protected $baseRoutePattern = 'foreign-book';
	protected $baseRouteName = 'admin_foreign_book';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('cover', 'string', array('template' => 'LibBundle:FeaturedBookAdmin:list_cover.html.twig'))
			->addIdentifier('title')
			->add('author')
			->add('url', 'string', array('template' => 'LibBundle:FeaturedBookAdmin:list_url.html.twig'))
			->add('isFree')
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
			->add('title')
			->add('author')
			->add('url')
			->add('cover')
			->add('description')
			->add('isFree')
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('title')
			->add('author')
			->add('url')
			->add('isFree')
		;
	}

}
