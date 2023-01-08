<?php namespace App\Admin;

use App\Entity\Series;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SeriesAdmin extends Admin {
	protected $baseRoutePattern = 'series';
	protected $baseRouteName = 'admin_series';

	public $extraActions = 'App:SeriesAdmin:extra_actions.html.twig';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('name')
			->add('origName')
			->add('authors')
			->add('texts')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
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
			->add('slug')
			->add('name')
			->add('origName', null, ['required' => false])
			->add('seriesAuthors', 'sonata_type_collection', [
				'by_reference' => false,
				'required' => false,
			], [
				'edit' => 'inline',
				'inline' => 'table',
			])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
			->add('origName')
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
