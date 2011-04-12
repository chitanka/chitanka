<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\BookmarkFolderRepository")
* @orm:Table(name="bookmark_folder",
*	indexes={
*		@orm:Index(name="slug_idx", columns={"slug"})}
* )
*/
class BookmarkFolder
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=40)
	*/
	private $slug = '';

	/**
	* @var string
	* @orm:Column(type="string", length=80)
	*/
	private $name = '';

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var date
	* @orm:Column(type="datetime")
	*/
	private $created_at;


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

}
