<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\BookmarkRepository")
* @orm:Table(name="bookmark"
* )
*/
class Bookmark
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="BookmarkFolder", cascade={"ALL"})
	*/
	private $folder;

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

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


	public function __construct($fields)
	{
		foreach (array('folder', 'text', 'user') as $field) {
			if (isset($fields[$field])) {
				$setter = 'set' . ucfirst($field);
				$this->$setter($fields[$field]);
			}
		}
	}

	public function getId() { return $this->id; }

	public function setFolder($folder) { $this->folder = $folder; }
	public function getFolder() { return $this->folder; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

}
