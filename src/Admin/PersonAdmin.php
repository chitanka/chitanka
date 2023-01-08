<?php namespace App\Admin;

use App\Persistence\PersonRepository;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class PersonAdmin extends Admin {
	protected $baseRoutePattern = 'person';
	protected $baseRouteName = 'admin_person';

	public $extraActions = 'PersonAdmin/extra_actions.html.twig';

	/** @var PersonRepository */
	private $repository;

	public function setRepository(PersonRepository $r) {
		$this->repository = $r;
	}

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('name')
			->add('origName')
			->add('realName')
			->add('orealName')
			->add('country')
			->add('isAuthor')
			->add('isTranslator')
			->add('info')
			->add('series', null, ['label' => 'Series plural']);
		$showMapper->with($this->trans('Main Person'));
		$showMapper
			->add('type')
			->add('person', null, ['label' => 'Main Person']);
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('origName')
			->add('info')

			->add('_action', 'actions', [
				'actions' => [
					//'show' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$translation = $this->getTranslation();
		$formMapper->tab('General attributes')->with('')
			->add('slug', null, ['required' => false])
			->add('name')
			->add('origName', null, ['required' => false])
			->add('realName', null, ['required' => false])
			->add('orealName', null, ['required' => false])
			->add('country')
			->add('isAuthor', null, ['required' => false])
			->add('isTranslator', null, ['required' => false])
			->add('info', null, ['required' => false])
			->end()->end();
		$formMapper->tab('Main Person')->with('')
			->add('type', 'choice', [
				'choices' => $translation->getPersonTypeChoices(),
				//'expanded' => true,
				'required' => false,
				'label' => 'Person Type',
			])
			->add('person', 'sonata_type_model_list', ['required' => false, 'label' => 'Main Person'])
			->end()->end();
		$formMapper->setHelps([
			'info' => $this->trans('help.wiki_article')
		]);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name', null, [
				'show_filter' => true,
			])
			->add('origName')
			->add('realName')
			->add('orealName')
			->add('country')
		;
	}

}
