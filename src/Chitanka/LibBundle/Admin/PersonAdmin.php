<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chitanka\LibBundle\Entity\PersonRepository;

class PersonAdmin extends Admin
{
	protected $baseRoutePattern = 'person';
	protected $baseRouteName = 'admin_person';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('slug')
			->add('name')
			->add('orig_name')
			->add('real_name')
			->add('oreal_name')
			->add('country')
			->add('is_author')
			->add('is_translator')
			->add('info')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('orig_name')

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
		$countryList = array();
		foreach ($this->getRepository()->getCountryList() as $countryCode) {
			$countryList[$countryCode] = "country.$countryCode";
		}
		$formMapper
			->add('slug', null, array('required' => false))
			->add('name')
			->add('orig_name', null, array('required' => false))
			->add('real_name', null, array('required' => false))
			->add('oreal_name', null, array('required' => false))
			->add('country', 'choice', array(
				'choices' => $countryList,
			))
			->add('is_author', null, array('required' => false))
			->add('is_translator', null, array('required' => false))
			->add('info', null, array('required' => false))
			->with($this->trans('Main Person'), array('collapsed' => true))
				->add('type', 'choice', array(
					'choices' => $this->getRepository()->getTypeList(),
					//'expanded' => true,
					'required' => false,
					'label' => 'Person Type',
				))
				->add('person', 'sonata_type_model_list', array('required' => false, 'label' => 'Main Person'))
			->end()
			->setHelps(array(
				'info' => $this->trans('help.person.info')
			))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('orig_name')
			->add('country')
		;
	}

}
