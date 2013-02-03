<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\TextRatingRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="text_rating",
*	uniqueConstraints={@ORM\UniqueConstraint(name="text_user_uniq", columns={"text_id", "user_id"})},
*	indexes={
*		@ORM\Index(name="user_idx", columns={"user_id"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class TextRating extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer $text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var integer $user
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var integer $rating
	 * @ORM\Column(type="smallint")
	 */
	private $rating;

	/**
	 * @var date $date
	 * @ORM\Column(type="datetime")
	 */
	private $date;


	public function __construct($text, $user)
	{
		$this->setText($text);
		$this->setUser($user);
	}

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setRating($rating) { $this->rating = $rating; }
	public function getRating() { return $this->rating; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }
	public function setCurrentDate()
	{
		$this->setDate(new \DateTime);
	}

	/** @ORM\PreUpdate */
	public function preUpdate()
	{
		$this->setDate(new \DateTime);
	}

}
