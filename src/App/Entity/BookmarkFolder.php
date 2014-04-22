<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Util\String;

/**
* @ORM\Entity(repositoryClass="App\Entity\BookmarkFolderRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="bookmark_folder",
*	uniqueConstraints={@ORM\UniqueConstraint(name="uniq_key", columns={"slug", "user_id"})},
*	indexes={
*		@ORM\Index(name="slug_idx", columns={"slug"})}
* )
*/
class BookmarkFolder extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=40)
	 */
	private $slug = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=80)
	 */
	private $name = '';

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


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

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
