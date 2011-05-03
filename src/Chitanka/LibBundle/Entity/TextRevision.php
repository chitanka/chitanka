<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\TextRevisionRepository")
* @orm:Table(name="text_revision",
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"}),
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class TextRevision
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var string $comment
	* @orm:Column(type="string", length=255)
	*/
	private $comment;

	/**
	* @var datetime $date
	* @orm:Column(type="datetime")
	*/
	private $date;

	/**
	* @var boolean
	* @orm:Column(type="boolean")
	*/
	private $first;


	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setFirst($first) { $this->first = $first; }
	public function getFirst() { return $this->first; }

}
