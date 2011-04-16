<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\UserTextReadRepository")
* @orm:HasLifecycleCallbacks
* @orm:Table(name="user_text_read",
*	uniqueConstraints={@orm:UniqueConstraint(name="user_text_uniq", columns={"user_id", "text_id"})},
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"})}
* )
* TODO replace this entity with a new "read" bookmark folder
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
	* @var datetime
	* @orm:Column(type="datetime")
	*/
	private $created_at;


	public function getId() { return $this->id; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

	/** @orm:PrePersist */
	public function preInsert()
	{
		$this->setCreatedAt(new \DateTime);
	}
}
