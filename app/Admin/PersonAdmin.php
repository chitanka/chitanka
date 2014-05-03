<?php namespace App\Admin;

use App\Entity\PersonRepository;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class PersonAdmin extends Admin {
	protected $baseRoutePattern = 'person';
	protected $baseRouteName = 'admin_person';

	public $extraActions = 'App:PersonAdmin:extra_actions.html.twig';

	private $repository;

	public function setRepository(PersonRepository $r) {
		$this->repository = $r;
	}

	protected function configureShowField(ShowMapper $showMapper) {
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
			->add('series', null, array('label' => 'Series plural'));
		$showMapper->with($this->trans('Main Person'));
		$showMapper
			->add('type')
			->add('person', null, array('label' => 'Main Person'));
	}

	protected function configureListFields(ListMapper $listMapper) {
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

	protected function configureFormFields(FormMapper $formMapper) {
		$countryList = array();
		foreach ($this->repository->getCountryList() as $countryCode) {
			$countryList[$countryCode] = "country.$countryCode";
		}
		$formMapper->with('General attributes');
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
			->add('info', null, array('required' => false));
		$formMapper->with('Main Person');
		$formMapper
			->add('type', 'choice', array(
				'choices' => $this->repository->getTypeList(),
				//'expanded' => true,
				'required' => false,
				'label' => 'Person Type',
			))
			->add('person', 'sonata_type_model_list', array('required' => false, 'label' => 'Main Person'));
		$formMapper->setHelps(array(
			'info' => $this->trans('help.person.info')
		));

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
			->add('orig_name')
			->add('real_name')
			->add('oreal_name')
			->add('country')
		;
	}

}
