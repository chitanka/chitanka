<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\UserTextContribRepository")
* @ORM\Table(name="user_text_contrib",
*	uniqueConstraints={
*		@ORM\UniqueConstraint(name="user_text_date_uniq", columns={"username", "text_id", "date"})},
*	indexes={
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class UserTextContrib extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	* @var integer $user
	* @ORM\ManyToOne(targetEntity="User")
	*/
	private $user;

	/**
	* @var string
	* @ORM\Column(type="string", length=100)
	*/
	private $username;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text", inversedBy="userContribs")
	*/
	private $text;

	/**
	* @var integer $size
	* @ORM\Column(type="integer")
	*/
	private $size;

	/**
	* @var integer $percent
	* @ORM\Column(type="smallint")
	*/
	private $percent;

	/**
	* @var string $comment
	* @ORM\Column(type="string", length=255)
	*/
	private $comment;

	/**
	* @var date
	* @ORM\Column(type="datetime")
	*/
	private $date;

	/**
	* @var string
	* @ORM\Column(type="string", length=30, nullable=true)
	*/
	private $humandate;

	public function getId() { return $this->id; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }

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

	public function setHumandate($humandate) { $this->humandate = $humandate; }
	public function getHumandate() { return $this->humandate; }

	public function __toString()
	{
		return sprintf('%s: %s (%s, %s%%)', $this->getComment(), $this->getUser(), $this->getHumandate(), $this->getPercent());
	}
}
