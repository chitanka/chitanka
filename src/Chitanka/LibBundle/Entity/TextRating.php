<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\TextRatingRepository")
* @orm:HasLifecycleCallbacks
* @orm:Table(name="text_rating",
*	uniqueConstraints={@orm:UniqueConstraint(name="text_user_uniq", columns={"text_id", "user_id"})},
*	indexes={
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class TextRating
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var integer $rating
	* @orm:Column(type="smallint")
	*/
	private $rating;

	/**
	* @var date $date
	* @orm:Column(type="datetime")
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

	/** @orm:PreUpdate */
	public function preUpdate()
	{
		$this->setDate(new \DateTime);
	}

}
