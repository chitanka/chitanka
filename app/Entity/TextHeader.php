<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\TextHeaderRepository")
 * @ORM\Table(name="text_header")
 */
class TextHeader extends Entity {
	/**
	 * @var int
	 * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="headers")
	 */
	private $text;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $nr;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $level;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $name;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $fpos;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $linecnt;

	public function getId() { return $this->id; }

	/**
	 * @param Text $text
	 */
	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setNr($nr) { $this->nr = $nr; }
	public function getNr() { return $this->nr; }

	public function setLevel($level) { $this->level = $level; }
	public function getLevel() { return $this->level; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setFpos($fpos) { $this->fpos = $fpos; }
	public function getFpos() { return $this->fpos; }

	public function setLinecnt($linecnt) { $this->linecnt = $linecnt; }
	public function getLinecnt() { return $this->linecnt; }

	public function __toString() {
		return str_repeat('—', $this->getLevel()-1) .' '. $this->getName();
	}
}
