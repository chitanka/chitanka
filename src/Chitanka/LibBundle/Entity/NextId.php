<?php
namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A helper model used for the custom IDs generation
 *
 * @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\NextIdRepository")
 * @ORM\Table(name="next_id")
 */
class NextId
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=255)
	 */
	private $id;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $value;

	public function __construct($id)
	{
		$this->id = $id;
	}
	public function getId() { return $this->id; }

	public function setValue($value) { $this->value = $value; }
	public function getValue() { return $this->value; }

	public function increment()
	{
		$this->value++;
	}
}
