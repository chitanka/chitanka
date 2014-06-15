<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Thread as BaseThread;

/**
 * @ORM\Entity(repositoryClass="App\Entity\ThreadRepository")
 * @ORM\Table(name="thread")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Thread extends BaseThread {
	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\Column(type="string")
	 */
	protected $id;

	/**
	 * @ORM\OneToOne(targetEntity="WorkEntry", mappedBy="commentThread")
	 */
	private $workEntry;
//	private $person;
//	private $book;
//	private $text;

	public function isForWorkEntry() {
		return strpos($this->id, 'WorkEntry:') === 0;
	}

	public function getTarget(EntityManager $em) {
		list($entity, $id) = explode(':', $this->id);
		$repo = $em->getRepository($entity);
		return $repo ? $repo->find($id) : null;
	}

	/** @return WorkEntry */
	public function getWorkEntry() { return $this->workEntry; }

	public function getPermalink() {
		return rawurldecode(parent::getPermalink());
	}
}
