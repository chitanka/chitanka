<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\WorkEntryRepository")
* @ORM\Table(name="work_entry",
*	indexes={
*		@ORM\Index(name="title_idx", columns={"title"}),
*		@ORM\Index(name="author_idx", columns={"author"}),
*		@ORM\Index(name="status_idx", columns={"status"})}
* )
*/
class WorkEntry extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $type
	* @ORM\Column(type="smallint")
	*/
	private $type;

	/**
	* @var string $title
	* @ORM\Column(type="string", length=100)
	*/
	private $title;

	/**
	* @var string $author
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $author;

	/**
	* @var integer $user
	* @ORM\ManyToOne(targetEntity="User")
	*/
	private $user;

	/**
	* @var text $comment
	* @ORM\Column(type="text")
	*/
	private $comment;

	/**
	* @var datetime $date
	* @ORM\Column(type="datetime")
	*/
	private $date;

	/**
	* @var integer $status
	* @ORM\Column(type="smallint")
	*/
	private $status = 0;

	/**
	* @var integer $progress
	* @ORM\Column(type="smallint")
	*/
	private $progress = 0;

	/**
	* @var boolean $is_frozen
	* @ORM\Column(type="boolean")
	*/
	private $is_frozen = false;

	/**
	* @var string $tmpfiles
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $tmpfiles;

	/**
	* @var integer $tfsize
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $tfsize;

	/**
	* @var string $uplfile
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $uplfile;

	/**
	* @var datetime
	* @ORM\Column(type="datetime", nullable=true)
	*/
	private $deleted_at;

	/**
	* @ORM\OneToMany(targetEntity="WorkContrib", mappedBy="entry")
	*/
	private $contribs;

	/**
	 * @ORM\OneToOne(targetEntity="Thread", inversedBy="workEntry")
	 */
	private $comment_thread;


	public function getId() { return $this->id; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setStatus($status) { $this->status = $status; }
	public function getStatus() { return $this->status; }

	public function setProgress($progress) { $this->progress = $progress; }
	public function getProgress() { return $this->progress; }

	public function setIsFrozen($isFrozen) { $this->is_frozen = $isFrozen; }
	public function getIsFrozen() { return $this->is_frozen; }

	public function setTmpfiles($tmpfiles) { $this->tmpfiles = $tmpfiles; }
	public function getTmpfiles() { return $this->tmpfiles; }

	public function setTfsize($tfsize) { $this->tfsize = $tfsize; }
	public function getTfsize() { return $this->tfsize; }

	public function setUplfile($uplfile) { $this->uplfile = $uplfile; }
	public function getUplfile() { return $this->uplfile; }

	public function setCommentThread(Thread $thread)
	{
		$this->comment_thread = $thread;
		return $this;
	}
	public function getCommentThread() { return $this->comment_thread; }

	public function getDeletedAt() { return $this->deleted_at; }
	public function setDeletedAt($deleted_at) { $this->deleted_at = $deleted_at; }
	public function delete()
	{
		$this->setDeletedAt(new \DateTime);
	}
}
