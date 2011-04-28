<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="book_link",
*	uniqueConstraints={@orm:UniqueConstraint(name="book_site_uniq", columns={"book_id", "site_id"})}
* )
*/
class BookLink
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="Book", cascade={"ALL"})
	*/
	private $book;

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="BookSite", cascade={"ALL"})
	*/
	private $site;

	/**
	* @var string
	* @orm:Column(type="string", length=50)
	*/
	private $code;

	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setSite($site) { $this->site = $site; }
	public function getSite() { return $this->site; }

	public function getSiteName() { return $this->site->getName(); }

	public function setCode($code) { $this->code = $code; }
	public function getCode() { return $this->code; }

	public function getUrl()
	{
		return str_replace('BOOKID', $this->code, $this->site->getUrl());
	}

	public function __toString()
	{
		return $this->code;
	}

}
