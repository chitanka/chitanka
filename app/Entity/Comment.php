<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Comment as BaseComment;
use FOS\CommentBundle\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="comment")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Comment extends BaseComment implements SignedCommentInterface {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	protected $id;

	/**
	 * Thread of this comment
	 *
	 * @ORM\ManyToOne(targetEntity="App\Entity\Thread")
	 * @var Thread
	 */
	protected $thread;

	/**
	 * Author of the comment
	 *
	 * @ORM\ManyToOne(targetEntity="App\Entity\User")
	 * @var UserInterface
	 */
	protected $author;

	protected $cc;

	public function setAuthor(UserInterface $author) {
		$this->author = $author;
	}

	public function getAuthor() {
		return $this->author;
	}

	public function getAuthorName() {
		if (null === $this->getAuthor()) {
			return 'Anonymous';
		}

		return $this->getAuthor()->getUsername();
	}

	public function isForWorkEntry() {
		return $this->getThread()->isForWorkEntry();
	}

	/**
	 * @return WorkEntry
	 */
	public function getWorkEntry() {
		return $this->getThread()->getWorkEntry();
	}

	public function setCc($cc) {
		$this->cc = $cc;
	}

	/**
	 * @return string
	 */
	public function getCc() {
		return $this->cc;
	}

	public function hasParent() {
		return $this->getDepth() > 0;
	}

	public function isDeleted() {
		return $this->getState() == self::STATE_DELETED;
	}
}
