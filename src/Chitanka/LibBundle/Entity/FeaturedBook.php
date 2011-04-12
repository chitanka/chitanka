<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\FeaturedBookRepository")
* @orm:Table(name="featured_book")
*/
class FeaturedBook
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $title;

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $author;

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $url;

	/**
	* @var string
	* @orm:Column(type="string", length=255)
	*/
	private $cover;

	public function getId() { return $this->id; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl() { return $this->url; }

	public function setCover($cover) { $this->cover = $cover; }
	public function getCover() { return $this->cover; }
}
