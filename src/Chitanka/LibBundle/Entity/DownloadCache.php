<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="download_cache")
*/
class DownloadCache
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string $file
	* @orm:Column(type="string", length=255)
	*/
	private $file;

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
	* Set file
	*
	* @param string $file
	*/
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	* Get file
	*
	* @return string $file
	*/
	public function getFile()
	{
		return $this->file;
	}
}
