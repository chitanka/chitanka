<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/*
* Not an entity but needed for putting labels on texts.
* See App\Form\Type\TextLabelType.
*
* @ORM\Entity
* @ORM\Table(name="text_label",
*	indexes={
*		@ORM\Index(name="label_idx", columns={"label_id"})})
*/
class TextLabel extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="textLabels")
	 */
	private $text;

	/**
	 * @var Label
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Label")
	 */
	private $label;

	public function getId() { return $this->id; }

	public function getText() { return $this->text; }

	/**
	 * @param Text $text
	 */
	public function setText($text) { $this->text = $text; }

	public function getLabel() { return $this->label; }
	public function setLabel($label) { $this->label = $label; }
}
