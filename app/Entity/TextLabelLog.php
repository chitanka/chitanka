<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\TextLabelLogRepository")
 * @ORM\Table(
 *  indexes={
 *    @ORM\Index(name="text_idx", columns={"text_id"}),
 *    @ORM\Index(name="label_idx", columns={"label_id"}),
 *    @ORM\Index(name="user_idx", columns={"user_id"})
 *  }
 * )
 */
class TextLabelLog extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var Label
	 * @ORM\ManyToOne(targetEntity="Label")
	 */
	private $label;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50)
	 */
	private $action;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @param string $action
	 */
	public function __construct(Text $text, Label $label, User $user, $action) {
		$this->setText($text);
		$this->setLabel($label);
		$this->setUser($user);
		$this->setAction($action);
		$this->setDate();
	}

	public function getId() { return $this->id; }

	public function getText() { return $this->text; }
	public function setText(Text $text) { $this->text = $text; }

	public function getLabel() { return $this->label; }
	public function setLabel(Label $label) { $this->label = $label; }

	public function getUser() { return $this->user; }
	public function setUser(User $user) { $this->user = $user; }

	public function getAction() { return $this->action; }

	/**
	 * @param string $action
	 */
	public function setAction($action) { $this->action = $action; }

	public function getDate() { return $this->date; }
	public function setDate($date = null) {
		$this->date = $date ?: new \DateTime;
	}
}
