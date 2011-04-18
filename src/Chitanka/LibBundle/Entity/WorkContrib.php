<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="work_contrib",
*	uniqueConstraints={@orm:UniqueConstraint(name="entry_user_uniq", columns={"entry_id", "user_id"})},
*	indexes={
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class WorkContrib
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $entry
	* @orm:ManyToOne(targetEntity="WorkEntry", cascade={"ALL"})
	*/
	private $entry;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var text $comment
	* @orm:Column(type="text")
	*/
	private $comment;

	/**
	* @var integer $progress
	* @orm:Column(type="smallint")
	*/
	private $progress;

	/**
	* @var boolean $is_frozen
	* @orm:Column(type="boolean")
	*/
	private $is_frozen;

	/**
	* @var datetime $date
	* @orm:Column(type="datetime")
	*/
	private $date;

	/**
	* @var string $uplfile
	* @orm:Column(type="string", length=255)
	*/
	private $uplfile;

	/**
	* @var datetime
	* @orm:Column(type="datetime", nullable=true)
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

	public function setDeletedAt($deleted_at) { $this->deleted_at = $deleted_at; }
	public function delete()
	{
		$this->setDeletedAt(new \DateTime);
	}
}
