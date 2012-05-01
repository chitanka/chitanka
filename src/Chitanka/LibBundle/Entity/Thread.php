<?php
namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use FOS\CommentBundle\Entity\Thread as BaseThread;

/**
 * @ORM\Entity
 * @ORM\Table(name="thread")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Thread extends BaseThread
{
	/**
	 * @var string $id
	 *
	 * @ORM\Id
	 * @ORM\Column(type="string")
	 */
	protected $id;

	public function isForWorkEntry()
	{
		return strpos($this->id, 'WorkEntry:') === 0;
	}

	public function getTarget(EntityManager $em)
	{
		list($entity, $id) = explode(':', $this->id);
		$repo = $em->getRepository("LibBundle:$entity");
		return $repo ? $repo->find($id) : null;
	}
}
