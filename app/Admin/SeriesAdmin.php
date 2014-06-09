<?php namespace App\Admin;

use App\Entity\Series;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SeriesAdmin extends Admin {
	protected $baseRoutePattern = 'series';
	protected $baseRouteName = 'admin_series';

	public $extraActions = 'App:SeriesAdmin:extra_actions.html.twig';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('name')
			->add('orig_name')
			->add('authors')
			->add('texts')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
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
		$formMapper->with('General attributes');
		$formMapper
			->add('slug')
			->add('name')
			->add('orig_name', null, array('required' => false))
			->add('seriesAuthors', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
			));
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
			->add('orig_name')
		;
	}

	/** {@inheritdoc} */
	public function prePersist($series) {
		$this->fixSeriesAuthorRelationship($series);
	}

	/** {@inheritdoc} */
	public function preUpdate($series) {
		$this->fixSeriesAuthorRelationship($series);
	}

	private function fixSeriesAuthorRelationship(Series $series) {
		foreach ($series->getSeriesAuthors() as $seriesAuthor) {
			if ($seriesAuthor->getPerson()) {
				$seriesAuthor->setSeries($series);
			}
		}
	}
}
