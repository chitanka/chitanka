<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="label_log",
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"}),
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class LabelLog
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User", cascade={"ALL"})
	*/
	private $user;

	/**
	* @var string $title
	* @orm:Column(type="string", length=100)
	*/
	private $title;

	/**
	* @var string $author
	* @orm:Column(type="string", length=200)
	*/
	private $author;

	/**
	* @var string $action
	* @orm:Column(type="string", length=1)
	*/
	private $action;

	/**
	* @var string $labels
	* @orm:Column(type="string", length=255)
	*/
	private $labels;

	/**
	* @var datetime $time
	* @orm:Column(type="datetime")
	*/
	private $time;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setTitle($title)
	{
		$this->title = $title;
	}
	public function getTitle()
	{
		return $this->title;
	}

	public function setAuthor($author)
	{
		$this->author = $author;
	}
	public function getAuthor()
	{
		return $this->author;
	}

	public function setAction($action)
	{
		$this->action = $action;
	}
	public function getAction()
	{
		return $this->action;
	}

	public function setLabels($labels)
	{
		$this->labels = $labels;
	}
	public function getLabels()
	{
		return $this->labels;
	}

	public function setTime($time)
	{
		$this->time = $time;
	}
	public function getTime()
	{
		return $this->time;
	}

}
