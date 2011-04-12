<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\UserTextReadRepository")
* @orm:Table(name="user_text_read",
*	uniqueConstraints={@orm:UniqueConstraint(name="user_text_uniq", columns={"user_id", "text_id"})},
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"})}
* )
*/
class UserTextRead
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
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var date $date
	* @orm:Column(type="date")
	*/
	private $date;


	public function getId() { return $this->id; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setCurrentDate()
	{
		$this->setDate(new \DateTime);
	}

	/** @orm:PreUpdate */
	public function preUpdate()
	{
		$this->setCurrentDate();
	}
}
