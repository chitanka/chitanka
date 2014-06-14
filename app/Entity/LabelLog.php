<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="label_log",
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"}),
*		@ORM\Index(name="user_idx", columns={"user_id"})}
* )
*/
class LabelLog extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
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
	 * @var string $title
	 * @ORM\Column(type="string", length=100)
	 */
	private $title;

	/**
	 * @var string $author
	 * @ORM\Column(type="string", length=200)
	 */
	private $author;

	/**
	 * @var string $action
	 * @ORM\Column(type="string", length=1)
	 */
	private $action;

	/**
	 * @var string $labels
	 * @ORM\Column(type="string", length=255)
	 */
	private $labels;

	/**
	 * @var \DateTime $time
	 * @ORM\Column(type="datetime")
	 */
	private $time;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setTitle($title) {
		$this->title = $title;
	}
	public function getTitle() {
		return $this->title;
	}

	public function setAuthor($author) {
		$this->author = $author;
	}
	public function getAuthor() {
		return $this->author;
	}

	public function setAction($action) {
		$this->action = $action;
	}
	public function getAction() {
		return $this->action;
	}

	public function setLabels($labels) {
		$this->labels = $labels;
	}
	public function getLabels() {
		return $this->labels;
	}

	public function setTime($time) {
		$this->time = $time;
	}
	public function getTime() {
		return $this->time;
	}

}
