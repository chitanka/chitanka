<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="download_cache_text",
*	indexes={@orm:Index(name="text_idx", columns={"text_id"})}
* )
*/
class DownloadCacheText
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue(strategy="AUTO")
	*/
	private $id;

	/**
	* @var integer $dc
	* @orm:ManyToOne(targetEntity="DownloadCache", cascade={"ALL"})
	*/
	private $dc;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	public function getId() { return $this->id; }

	public function setDc($dc) { $this->dc = $dc; }
	public function getDc() { return $this->dc; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

}
