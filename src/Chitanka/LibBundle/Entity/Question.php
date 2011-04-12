<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="question")
*/
class Question
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string $question
	* @orm:Column(type="string", length=255)
	*/
	private $question;

	/**
	* @var string $answers
	* @orm:Column(type="string", length=255)
	*/
	private $answers;

	/**
	* Get id
	*
	* @return integer $id
	*/
	public function getId()
	{
		return $this->id;
	}

	/**
	* Set question
	*
	* @param string $question
	*/
	public function setQuestion($question)
	{
		$this->question = $question;
	}

	/**
	* Get question
	*
	* @return string $question
	*/
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	* Set answers
	*
	* @param string $answers
	*/
	public function setAnswers($answers)
	{
		$this->answers = $answers;
	}

	/**
	* Get answers
	*
	* @return string $answers
	*/
	public function getAnswers()
	{
		return $this->answers;
	}
}
