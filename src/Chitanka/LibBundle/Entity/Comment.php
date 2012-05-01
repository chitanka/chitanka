<?php
namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Comment as BaseComment;
use FOS\CommentBundle\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="comment")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Comment extends BaseComment implements SignedCommentInterface
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\generatedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * Thread of this comment
	 *
	 * @ORM\ManyToOne(targetEntity="Chitanka\LibBundle\Entity\Thread")
	 * @var Thread
	 */
	protected $thread;

	/**
	 * Author of the comment
	 *
	 * @ORM\ManyToOne(targetEntity="Chitanka\LibBundle\Entity\User")
	 * @var User
	 */
	protected $author;

	public function setAuthor(UserInterface $author)
	{
		$this->author = $author;
	}

	public function getAuthor()
	{
		return $this->author;
	}

	public function getAuthorName()
	{
		if (null === $this->getAuthor()) {
			return 'Anonymous';
		}

		return $this->getAuthor()->getUsername();
	}
}
