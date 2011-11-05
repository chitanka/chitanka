<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\UserTextReadRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="user_text_read",
*	uniqueConstraints={@ORM\UniqueConstraint(name="user_text_uniq", columns={"user_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
* TODO replace this entity with a new "read" bookmark folder
*/
class UserTextRead
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $user
	* @ORM\ManyToOne(targetEntity="User")
	*/
	private $user;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text")
	*/
	private $text;

	/**
	* @var datetime
	* @ORM\Column(type="datetime")
	*/
	private $created_at;


	public function getId() { return $this->id; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

	/** @ORM\PrePersist */
	public function preInsert()
	{
		$this->setCreatedAt(new \DateTime);
	}
}
