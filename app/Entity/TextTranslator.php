<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="text_translator",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextTranslator extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Person
	 * @ORM\ManyToOne(targetEntity="Person")
	 */
	private $person;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="textTranslators")
	 */
	private $text;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $pos;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $year;

	public function equals(TextTranslator $textTranslator) {
		return $this->getId() == $textTranslator->getId();
	}

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	/**
	 * Set pos
	 *
	 * @param int $pos
	 */
	public function setPos($pos) {
		$this->pos = $pos;
	}

	/**
	 * Get pos
	 *
	 * @return int
	 */
	public function getPos() {
		return $this->pos;
	}

	/**
	 * Set year
	 *
	 * @param int $year
	 */
	public function setYear($year) {
		$this->year = $year;
	}

	/**
	 * Get year
	 *
	 * @return int
	 */
	public function getYear() {
		return $this->year;
	}

}
