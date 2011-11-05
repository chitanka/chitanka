<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\BookmarkRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="bookmark",
*	uniqueConstraints={@ORM\UniqueConstraint(name="uniq_key", columns={"folder_id", "text_id", "user_id"})}
* )
*/
class Bookmark
{
	/**
	* @var integer
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer
	* @ORM\ManyToOne(targetEntity="BookmarkFolder")
	*/
	private $folder;

	/**
	* @var integer
	* @ORM\ManyToOne(targetEntity="Text")
	*/
	private $text;

	/**
	* @var integer
	* @ORM\ManyToOne(targetEntity="User")
	*/
	private $user;

	/**
	* @var date
	* @ORM\Column(type="datetime")
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

	/** @ORM\PrePersist */
	public function preInsert()
	{
		$this->setCreatedAt(new \DateTime);
	}

}
