<?php namespace App\Admin;

use App\Entity\ForeignBook;
use App\Entity\User;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ForeignBookForPublisherAdmin extends ForeignBookAdmin {
	protected $baseRoutePattern = 'foreign-book-for-publisher';
	protected $baseRouteName = 'admin_foreign_book_for_publisher';

	/**
	 * @var TokenStorage
	 */
	private $tokenStorage;

	public function setTokenStorage(TokenStorage $tokenStorage) {
		$this->tokenStorage = $tokenStorage;
	}

	public function configure() {
		$this->setTemplate('layout', 'App:Admin:publisher_layout.html.twig');
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('cover', 'string', ['template' => 'App:ForeignBookAdmin:list_cover.html.twig'])
			->addIdentifier('title')
			->add('author')
			->add('isActive')
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
		/** @var $book ForeignBook */
		$book = $this->getSubject();
		if ($book->getPublisher() && $book->getPublisher() != $this->getUser()->getPublisher()) {
			throw new \Exception("Unauthorized");
		}
		$coverFileOptions = ['label' => 'Cover', 'required' => false];
		if ($book && ($webPath = $book->getCoverPath())) {
			$fullPath = $this->getRequest()->getBasePath() .'/'. $webPath;
			$coverFileOptions['help'] = '<img src="'.$fullPath.'" class="admin-preview" width="90"/>';
		}
		$formMapper->with('General attributes')
			->add('author')
			->add('title')
			->add('externalUrl', null, ['help' => 'help.foreign_book.external_url'])
			->add('coverFile', 'file', $coverFileOptions)
			->add('annotation')
			->add('category')
			//->add('labels')
			->add('excerpt', null, ['attr' => ['class' => 'richhtml']])
			->add('publishedAt', 'sonata_type_date_picker')
			->add('isActive', null, ['help' => 'help.foreign_book.is_active'])
			->end();
	}

	/**
	 * @param ForeignBook $book
	 */
	public function prePersist($book) {
		$book->setPublisher($this->getUser()->getPublisher());
		$this->saveCover($book);
	}

	public function createQuery($context = 'list') {
		$query = parent::createQuery($context);
		$query->where($query->getRootAliases()[0].'.publisher = ?1');
		$query->setParameter(1, $this->getUser()->getPublisher());
		return $query;
	}

	/**
	 * @return User
	 */
	protected function getUser() {
		return $this->tokenStorage->getToken()->getUser();
	}
}
