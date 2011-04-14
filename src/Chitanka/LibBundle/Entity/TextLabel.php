<?php

namespace Chitanka\LibBundle\Entity;

/** FIXME doctrine:schema:create does not allow this entity table
* @orm:Entity
* @orm:Table(name="text_label",
*	indexes={
*		@orm:Index(name="label_idx", columns={"label_id"})})
*/
class TextLabel
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue(strategy="AUTO")
	*/
	private $id;

	/**
	* @var integer
	* @orm:Id
	* @orm:ManyToOne(targetEntity="Text", inversedBy="textLabels", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer
	* @orm:Id
	* @orm:ManyToOne(targetEntity="Label", cascade={"ALL"})
	*/
	private $label;

	public function getId() { return $this->id; }

	public function getText() { return $this->text; }
	public function setText($text) { $this->text = $text; }

	public function getLabel() { return $this->label; }
	public function setLabel($label) { $this->label = $label; }
}
