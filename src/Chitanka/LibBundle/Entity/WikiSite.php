<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="wiki_site")
 * @UniqueEntity(fields="code")
 * @UniqueEntity(fields="name")
 */
class WikiSite extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=10, unique=true)
	 */
	private $code;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, unique=true)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $url;

	/**
	 * @var string
	 * @ORM\Column(type="text")
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
