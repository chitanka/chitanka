<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="work_contrib",
*	uniqueConstraints={@ORM\UniqueConstraint(name="entry_user_uniq", columns={"entry_id", "user_id"})},
*	indexes={
*		@ORM\Index(name="user_idx", columns={"user_id"})}
* )
*/
class WorkContrib extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $entry
	* @ORM\ManyToOne(targetEntity="WorkEntry")
	*/
	private $entry;

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
	* @var datetime $date
	* @ORM\Column(type="datetime")
	*/
	private $date;

	/**
	* @var string $uplfile
	* @ORM\Column(type="string", length=255, nullable=true)
	*/
	private $uplfile;

	/**
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $filesize;

	/**
	* @var datetime
	* @ORM\Column(type="datetime", nullable=true)
	*/
	private $deleted_at;


	public function getId() { return $this->id; }

	public function setEntry($entry) { $this->entry = $entry; }
	public function getEntry() { return $this->entry; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setProgress($progress) { $this->progress = $progress; }
	public function getProgress() { return $this->progress; }

	public function setIsFrozen($isFrozen) { $this->is_frozen = $isFrozen; }
	public function getIsFrozen() { return $this->is_frozen; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setUplfile($uplfile) { $this->uplfile = $uplfile; }
	public function getUplfile() { return $this->uplfile; }

	public function setFilesize($filesize) { $this->filesize = $filesize; }
	public function getFilesize() { return $this->filesize; }

	public function setDeletedAt($deleted_at) { $this->deleted_at = $deleted_at; }
	public function delete()
	{
		$this->setDeletedAt(new \DateTime);
	}
}
