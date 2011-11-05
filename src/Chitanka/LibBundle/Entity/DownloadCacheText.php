<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="download_cache_text",
*	indexes={@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class DownloadCacheText
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO")
	*/
	private $id;

	/**
	* @var integer $dc
	* @ORM\ManyToOne(targetEntity="DownloadCache")
	*/
	private $dc;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text")
	*/
	private $text;

	public function getId() { return $this->id; }

	public function setDc($dc) { $this->dc = $dc; }
	public function getDc() { return $this->dc; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

}
