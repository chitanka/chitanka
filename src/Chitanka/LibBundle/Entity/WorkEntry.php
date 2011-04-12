<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\WorkEntryRepository")
* @orm:Table(name="work_entry",
*	indexes={
*		@orm:Index(name="title_idx", columns={"title"}),
*		@orm:Index(name="author_idx", columns={"author"}),
*		@orm:Index(name="status_idx", columns={"status"})}
* )
*/
class WorkEntry
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $type
	* @orm:Column(type="smallint")
	*/
	private $type;

	/**
	* @var string $title
	* @orm:Column(type="string", length=100)
	*/
	private $title;

	/**
	* @var string $author
	* @orm:Column(type="string", length=100)
	*/
	private $author;

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
	* @var datetime $date
	* @orm:Column(type="datetime")
	*/
	private $date;

	/**
	* @var integer $status
	* @orm:Column(type="smallint")
	*/
	private $status;

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
	* @var string $tmpfiles
	* @orm:Column(type="string", length=255)
	*/
	private $tmpfiles;

	/**
	* @var integer $tfsize
	* @orm:Column(type="smallint")
	*/
	private $tfsize;

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

	public function setDeletedAt($deleted_at) { $this->deleted_at = $deleted_at; }
	public function delete() 
	{ 
		$this->setDeletedAt(new \DateTime); 
	}
}
