<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY")
 * @ORM\Table(name="question")
 */
class Question extends Entity implements \JsonSerializable {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string $question
	 * @ORM\Column(type="string", length=255)
	 */
	private $question = '';

	/**
	 * @var string $answers
	 * @ORM\Column(type="string", length=255)
	 */
	private $answers;

	/**
	 * Get id
	 *
	 * @return integer $id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set question
	 *
	 * @param string $question
	 */
	public function setQuestion($question) {
		$this->question = $question;
	}

	/**
	 * Get question
	 *
	 * @return string $question
	 */
	public function getQuestion() {
		return $this->question;
	}

	/**
	 * Set answers
	 *
	 * @param string $answers
	 */
	public function setAnswers($answers) {
		$this->answers = $answers;
	}

	/**
	 * Get answers
	 *
	 * @return string $answers
	 */
	public function getAnswers() {
		return $this->answers;
	}

	public function __toString() {
		return $this->question;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'question' => $this->getQuestion(),
			'answers' => $this->getAnswers(),
		];
	}
}
