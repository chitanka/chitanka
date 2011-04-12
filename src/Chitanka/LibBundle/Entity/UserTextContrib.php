<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\UserTextContribRepository")
* @orm:Table(name="user_text_contrib",
*	uniqueConstraints={@orm:UniqueConstraint(name="text_user_uniq", columns={"text_id", "user_id"})},
*	indexes={
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class UserTextContrib
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", inversedBy="userContribs", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $size
	* @orm:Column(type="integer")
	*/
	private $size;

	/**
	* @var integer $percent
	* @orm:Column(type="smallint")
	*/
	private $percent;

	/**
	* @var string $comment
	* @orm:Column(type="string", length=255)
	*/
	private $comment;

	/**
	* @var date
	* @orm:Column(type="datetime")
	*/
	private $date;

	public function getId() { return $this->id; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setSize($size) { $this->size = $size; }
	public function getSize() { return $this->size; }

	public function setPercent($percent) { $this->percent = $percent; }
	public function getPercent() { return $this->percent; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

}
