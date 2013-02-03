<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/*
* Not an entity but needed for putting labels on texts.
* See Chitanka\LibBundle\Form\Type\TextLabelType.
*
* @ORM\Entity
* @ORM\Table(name="text_label",
*	indexes={
*		@ORM\Index(name="label_idx", columns={"label_id"})})
*/
class TextLabel extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="textLabels")
	 */
	private $text;

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Label")
	 */
	private $label;

	public function getId() { return $this->id; }

	public function getText() { return $this->text; }
	public function setText($text) { $this->text = $text; }

	public function getLabel() { return $this->label; }
	public function setLabel($label) { $this->label = $label; }
}
