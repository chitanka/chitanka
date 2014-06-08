<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\TextRatingRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="text_rating",
*	uniqueConstraints={@ORM\UniqueConstraint(name="text_user_uniq", columns={"text_id", "user_id"})},
*	indexes={
*		@ORM\Index(name="user_idx", columns={"user_id"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class TextRating extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $rating;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @param Text $text
	 * @param User $user
	 */
	public function __construct($text, $user) {
		$this->setText($text);
		$this->setUser($user);
	}

	public function getId() { return $this->id; }

	/**
	 * @param Text $text
	 */
	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	/**
	 * @param User $user
	 */
	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setRating($rating) { $this->rating = $rating; }
	public function getRating() { return $this->rating; }

	/**
	 * @param \DateTime $date
	 */
	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }
	public function setCurrentDate() {
		$this->setDate(new \DateTime);
	}

	/** @ORM\PreUpdate */
	public function preUpdate() {
		$this->setDate(new \DateTime);
	}

}
