<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="wiki_site")
*/
class WikiSite
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=10, unique=true)
	*/
	private $code;

	/**
	* @var string
	* @orm:Column(type="string", length=50, unique=true)
	*/
	private $name;

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $url;

	/**
	* @var string
	* @orm:Column(type="text")
	*/
	private $intro;

	public function getId() { return $this->id; }

	public function setCode($code) { $this->code = $code; }
	public function getCode() { return $this->code; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl($page = null)
	{
		if (is_null($page)) {
			return $this->url;
		}

		return strtr($this->url, array('$1' => urlencode(strtr($page, ' ', '_'))));
	}

	public function setIntro($intro) { $this->intro = $intro; }
	public function getIntro() { return $this->intro; }
}
