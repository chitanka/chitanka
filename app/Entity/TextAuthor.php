<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="text_author",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextAuthor extends Entity {
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
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="textAuthors")
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

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setPos($pos) { $this->pos = $pos; }
	public function getPos() { return $this->pos; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

}
